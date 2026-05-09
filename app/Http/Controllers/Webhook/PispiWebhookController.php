<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Entities\Transactions;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Entities\WalletMouvement;
use Modules\Pos\Entities\VentePos;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PispiWebhookController extends Controller
{
    /**
     * Webhook PI-SPI
     * Source de vérité finale du paiement
     */
    public function handle(Request $request)
    {
        /**
         * =========================
         * 1. Sécurité / logs bruts
         * =========================
         */
        Log::info('PI-SPI webhook received', $request->all());

        // TODO (prod) : vérifier signature HMAC BCEAO
//         $this->verifySignature($request);

        /**
         * =========================
         * 2. Validation minimale
         * =========================
         */
        $data = $request->validate([
            'reference' => 'required|string',      // UUID transaction interne
            'status'    => 'required|string',      // SUCCESS | FAILED | CANCELLED
            'amount'    => 'required|integer',
            'currency'  => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            /**
             * =========================
             * 3. Charger la transaction
             * =========================
             */
            $transaction = Transactions::where('uuid', $data['reference'])
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                DB::rollBack();
                return response()->json(['error' => 'Transaction inconnue'], 404);
            }

            /**
             * =========================
             * 4. Idempotence STRICTE
             * =========================
             */
            if (in_array($transaction->statut, [
                Transactions::STATUT_REUSSIE,
                Transactions::STATUT_ECHOUEE,
                Transactions::STATUT_ANNULEE,
                Transactions::STATUT_REMBOURSEE,
            ])) {
                DB::rollBack();
                return response()->json(['status' => 'already_processed'], 200);
            }

            /**
             * =========================
             * 5. Mapping statut PI-SPI
             * =========================
             */
            switch ($data['status']) {

                /**
                 * =========================
                 * ✅ SUCCÈS
                 * =========================
                 */
                case 'SUCCESS':

                    $transaction->markAsReussie();

                    /**
                     * =========================
                     * 6. Crédit wallet marchand
                     * =========================
                     */
                    $wallet = Wallet::where([
                        'owner_type' => 'marchand',
                        'owner_id'   => $transaction->marchand_id,
                    ])->whereHas('paysDevise', fn ($q) =>
                        $q->where('code', $transaction->devise)
                    )->lockForUpdate()->first();

                    if (!$wallet) {
                        throw new \Exception('Wallet marchand introuvable');
                    }

                    $soldeAvant = $wallet->solde_cents;
                    $soldeApres = $soldeAvant + $transaction->montant_cents;

                    $wallet->update([
                        'solde_cents' => $soldeApres,
                    ]);

                    WalletMouvement::create([
                        'wallet_id'           => $wallet->id,
                        'type_mouvement'      => WalletMouvement::TYPE_CREDIT,
                        'montant_cents'       => $transaction->montant_cents,
                        'solde_avant_cents'   => $soldeAvant,
                        'solde_apres_cents'   => $soldeApres,
                        'emplacement_id'      => $transaction->points_vente_id,
                        'users_id'            => $transaction->payer_id,
                        'reference'           => $transaction->uuid,
                        'source_type'         => Transactions::SOURCE_VENTE_POS,
                        'source_id'           => $transaction->source_id,
                    ]);

                    /**
                     * =========================
                     * 7. Mise à jour vente POS
                     * =========================
                     */
                    if ($transaction->source_type === Transactions::SOURCE_VENTE_POS) {
                        VentePos::where('id', $transaction->source_id)
                            ->update([
                                'statut'  => config('appconstants.statut_vente_pos.validee'),
                                'paid_at' => now(),
                            ]);
                    }

                    break;

                /**
                 * =========================
                 * ❌ ÉCHEC
                 * =========================
                 */
                case 'FAILED':
                case 'CANCELLED':

                    $transaction->markAsEchouee();

                    if ($transaction->source_type === Transactions::SOURCE_VENTE_POS) {
                        VentePos::where('id', $transaction->source_id)
                            ->update([
                                'statut' => config('appconstants.statut_vente_pos.annulee'),
                            ]);
                    }

                    break;

                default:
                    throw new \Exception('Statut PI-SPI inconnu : ' . $data['status']);
            }

            DB::commit();

            return response()->json(['status' => 'ok'], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('PispiWebhookController','handle', (string)['message' => $e->getMessage(), 'payload' => $request->all()]);
            Log::error('PI-SPI webhook error', [
                'message' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'internal_error'], 500);
        }
    }

    /**
     * Vérifie la signature HMAC du webhook PI-SPI
     *
     * @throws UnauthorizedHttpException
     */
    protected function verifySignature(Request $request): void
    {
        $secret = config('services.pispi.webhook_secret');

        if (!$secret) {
            throw new UnauthorizedHttpException('', 'PI-SPI secret non configuré');
        }

        /**
         * Signature envoyée par PI-SPI
         */
        $signatureHeader = $request->header('X-PI-SPI-SIGNATURE');

        if (!$signatureHeader) {
            throw new UnauthorizedHttpException('', 'Signature manquante');
        }

        /**
         * Payload brut EXACT
         */
        $payload = $request->getContent();

        /**
         * Calcul HMAC local
         */
        $computedSignature = hash_hmac(
            'sha256',
            $payload,
            $secret
        );

        /**
         * Comparaison sécurisée (timing attack safe)
         */
        if (!hash_equals($computedSignature, $signatureHeader)) {

            Log::warning('PI-SPI signature invalide', [
                'received' => $signatureHeader,
                'computed' => $computedSignature,
                'payload'  => $payload,
            ]);

            log_error('PispiWebhookController', 'verifySignature', (string)['message' => 'Signature invalide', 'payload' => $request->all()]);

            throw new UnauthorizedHttpException('', 'Signature invalide');
        }
    }

}
