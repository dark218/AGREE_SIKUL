<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Wallet\Entities\Transactions;
use Modules\Parametrage\Entities\FournisseurPaiement;
use Modules\Wallet\Entities\MoyenPaiement;
use App\Models\User;
use Carbon\Carbon;
use RuntimeException;

class PaymentRoutingService
{
    /**
     * Initier un paiement électronique via PI-SPI
     */
    public function initiatePayment(array $context): Transactions
    {
        return DB::transaction(function () use ($context) {

            /**
             * =========================
             * 1. Résolution du moyen de paiement
             * =========================
             */
            $moyenPaiement = $this->resolveMoyenPaiement($context);

            /**
             * =========================
             * 2. Résolution du fournisseur PI-SPI
             * =========================
             */
            $fournisseur = $this->resolveFournisseur($moyenPaiement);

            /**
             * =========================
             * 3. Création de la transaction interne
             * =========================
             */
            $transaction = $this->createTransaction(
                $context,
                $moyenPaiement,
                $fournisseur
            );

            /**
             * =========================
             * 4. Construction payload PI-SPI
             * =========================
             */
            $payload = $this->buildPspiPayload(
                $transaction,
                $moyenPaiement,
                $fournisseur
            );

            /**
             * =========================
             * 5. Dispatch vers le fournisseur
             * =========================
             */
            $this->dispatchToProvider($fournisseur, $payload);

            $transaction->markAsEnAttente();

            return $transaction;
        });
    }

    /**
     * Résout le moyen de paiement à utiliser
     */
    protected function resolveMoyenPaiement(array $context): MoyenPaiement
    {
        if (!empty($context['moyen_paiement_id'])) {
            return MoyenPaiement::findOrFail($context['moyen_paiement_id']);
        }

        if (!empty($context['payer_id'])) {
            $moyen = MoyenPaiement::where('id_utilisateur', $context['payer_id'])
                ->where('is_default', true)
                ->first();

            if (!$moyen) {
                throw new RuntimeException('Aucun moyen de paiement par défaut');
            }

            return $moyen;
        }

        throw new RuntimeException('Impossible de déterminer le moyen de paiement');
    }

    /**
     * Résout le fournisseur PI-SPI
     */
    protected function resolveFournisseur(MoyenPaiement $moyen): FournisseurPaiement
    {
        $fournisseur = FournisseurPaiement::where('id', $moyen->fournisseur_paiement_id)
            ->where('statut', 'actif')
            ->first();

        if (!$fournisseur) {
            throw new RuntimeException('Fournisseur de paiement indisponible');
        }

        return $fournisseur;
    }

    /**
     * Crée la transaction interne
     */
    protected function createTransaction(
        array $context,
        MoyenPaiement $moyen,
        FournisseurPaiement $fournisseur
    ): Transactions {
        return Transactions::create([
            'uuid' => Str::uuid(),
            'payer_id' => $context['payer_id'] ?? null,
            'marchand_id' => $context['marchand_id'] ?? null,
            'points_vente_id' => $context['points_vente_id'] ?? null,
            'source_type' => $context['source_type'],
            'source_id' => $context['source_id'],
            'fournisseur_paiement_id' => $fournisseur->id,
            'montant_cents' => $context['montant_cents'],
            'devise' => $context['devise'],
            'statut' => Transactions::STATUT_INITIEE,
            'meta_json' => [
                'payer_identifier' => $moyen->identifiant_compte,
                'provider_code' => $fournisseur->code,
            ],
            'initiated_at' => Carbon::now(),
        ]);
    }

    /**
     * Construit le payload PI-SPI
     */
    protected function buildPspiPayload(
        Transactions $transaction,
        MoyenPaiement $moyen,
        FournisseurPaiement $fournisseur
    ): array {
        return [
            'reference' => $transaction->uuid,
            'amount' => $transaction->montant_cents / 100,
            'currency' => $transaction->devise,
            'payer' => [
                'type' => $this->resolvePayerType($fournisseur),
                'value' => $moyen->identifiant_compte,
            ],
            'provider' => $fournisseur->code,
            'callback_url' => route('pspi.webhook'),
        ];
    }

    /**
     * Envoi vers PI-SPI (abstraction)
     */
    protected function dispatchToProvider(
        FournisseurPaiement $fournisseur,
        array $payload
    ): void {
        // Ici tu brancheras :
        // - Client HTTP PI-SPI
        // - OAuth2 BCEAO
        // - Gestion des erreurs réseau

        // Pour l’instant : stub volontaire
    }

    /**
     * Résout le type de payeur PI-SPI
     */
    protected function resolvePayerType(FournisseurPaiement $fournisseur): string
    {
        return match ($fournisseur->type) {
            'mm' => 'MSISDN',
            'bank' => 'IBAN',
            default => 'UNKNOWN',
        };
    }
}
