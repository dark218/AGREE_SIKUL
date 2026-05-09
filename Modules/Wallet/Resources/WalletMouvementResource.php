<?php

namespace Modules\Wallet\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use Modules\Business\Resources\PointVenteResource;

/**
 * @OA\Schema(
 *     schema="WalletMouvementResource",
 *     title="WalletMouvement",
 *     description="Resource pour l'entité WalletMouvement",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="wallet_id", type="integer", example=1),
 *     @OA\Property(
 *         property="wallet",
 *         ref="#/components/schemas/WalletResource"
 *     ),
 *     @OA\Property(property="users_id", type="integer", example=123),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/UserResource"
 *     ),
 *     @OA\Property(property="type_mouvement", type="string", enum={"credit", "debit", "blocage", "deblocage", "commission", "remboursement", "ajustement"}, example="credit"),
 *     @OA\Property(property="type_mouvement_label", type="string", example="Crédit"),
 *     @OA\Property(property="montant_cents", type="integer", example=50000),
 *     @OA\Property(property="montant", type="string", example="500,00 XOF"),
 *     @OA\Property(property="solde_avant_cents", type="integer", example=100000),
 *     @OA\Property(property="solde_avant", type="string", example="1 000,00 XOF"),
 *     @OA\Property(property="solde_apres_cents", type="integer", example=150000),
 *     @OA\Property(property="solde_apres", type="string", example="1 500,00 XOF"),
 *     @OA\Property(property="emplacement_id", type="integer", nullable=true, example=1),
 *     @OA\Property(
 *         property="emplacement",
 *         ref="#/components/schemas/PointVenteResource"
 *     ),
 *     @OA\Property(property="reference", type="string", nullable=true, example="TXN-2024-001"),
 *     @OA\Property(property="source_type", type="string", nullable=true, example="transaction"),
 *     @OA\Property(property="source_id", type="integer", nullable=true, example=456),
 *     @OA\Property(property="meta_json", type="object", example={"key": "value"}),
 *     @OA\Property(property="is_credit", type="boolean", example=true),
 *     @OA\Property(property="is_debit", type="boolean", example=false),
 *     @OA\Property(property="is_blocage", type="boolean", example=false),
 *     @OA\Property(property="is_deblocage", type="boolean", example=false)
 * )
 */
class WalletMouvementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'wallet' => $this->whenLoaded('wallet', function () {
                return new WalletResource($this->wallet);
            }),
            'users_id' => $this->users_id,
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
            'type_mouvement' => $this->type_mouvement,
            'type_mouvement_label' => $this->getTypeMouvementLabel(),
            'montant_cents' => $this->montant_cents,
            'montant' => $this->montant,
            'solde_avant_cents' => $this->solde_avant_cents,
            'solde_avant' => $this->getSoldeAvantAttribute(),
            'solde_apres_cents' => $this->solde_apres_cents,
            'solde_apres' => $this->getSoldeApresAttribute(),
            'emplacement_id' => $this->emplacement_id,
            'emplacement' => $this->whenLoaded('emplacement', function () {
                return new PointVenteResource($this->emplacement);
            }),
            'reference' => $this->reference,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'meta_json' => $this->meta_json,
            'is_credit' => $this->estCredit(),
            'is_debit' => $this->estDebit(),
            'is_blocage' => $this->estBlocage(),
            'is_deblocage' => $this->estDeblocage(),
        ];
    }

    /**
     * Obtenir le libellé du type de mouvement
     */
    private function getTypeMouvementLabel(): string
    {
        return match($this->type_mouvement) {
            'credit' => 'Crédit',
            'debit' => 'Débit',
            'blocage' => 'Blocage',
            'deblocage' => 'Déblocage',
            'commission' => 'Commission',
            'remboursement' => 'Remboursement',
            'ajustement' => 'Ajustement',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir le solde avant formaté
     */
    private function getSoldeAvantAttribute(): string
    {
        return \App\Services\MoneyService::toDisplay(
            $this->solde_avant_cents,
            $this->wallet?->paysDevise?->devise?->code ?? 'XOF'
        );
    }

    /**
     * Obtenir le solde après formaté
     */
    private function getSoldeApresAttribute(): string
    {
        return \App\Services\MoneyService::toDisplay(
            $this->solde_apres_cents,
            $this->wallet?->paysDevise?->devise?->code ?? 'XOF'
        );
    }
}
