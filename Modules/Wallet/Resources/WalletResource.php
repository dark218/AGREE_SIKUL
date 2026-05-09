<?php

namespace Modules\Wallet\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Parametrage\Resources\PaysDeviseResource;
use App\Http\Resources\UserResource;
use Modules\Business\Resources\MarchandResource;

/**
 * @OA\Schema(
 *     schema="WalletResource",
 *     title="Wallet",
 *     description="Resource pour l'entité Wallet",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="owner_id", type="integer", example=123),
 *     @OA\Property(property="owner_type", type="string", enum={"utilisateur", "marchand", "agent", "plateforme", "client"}, example="client"),
 *     @OA\Property(property="owner_type_label", type="string", example="Client"),
 *     @OA\Property(property="pays_devise_id", type="integer", example=1),
 *     @OA\Property(
 *         property="pays_devise",
 *         ref="#/components/schemas/PaysDeviseResource"
 *     ),
 *     @OA\Property(property="solde_cents", type="integer", example=100000),
 *     @OA\Property(property="solde", type="string", example="1 000,00 XOF"),
 *     @OA\Property(property="solde_bloque_cents", type="integer", example=50000),
 *     @OA\Property(property="solde_bloque", type="string", example="500,00 XOF"),
 *     @OA\Property(property="solde_commission_cents", type="integer", example=10000),
 *     @OA\Property(property="solde_commission", type="string", example="100,00 XOF"),
 *     @OA\Property(property="solde_attente_cents", type="integer", example=20000),
 *     @OA\Property(property="solde_attente", type="string", example="200,00 XOF"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "suspendu", "ferme"}, example="actif"),
 *     @OA\Property(property="statut_label", type="string", example="Actif"),
 *     @OA\Property(property="is_actif", type="boolean", example=true),
 *     @OA\Property(property="is_suspendu", type="boolean", example=false),
 *     @OA\Property(property="is_ferme", type="boolean", example=false),
 *     @OA\Property(property="meta_json", type="object", example={"key": "value"}),
 *     @OA\Property(
 *         property="owner",
 *         oneOf={
 *             @OA\Schema(ref="#/components/schemas/UserResource"),
 *             @OA\Schema(ref="#/components/schemas/MarchandResource")
 *         }
 *     ),
 *     @OA\Property(
 *         property="mouvements_count",
 *         type="integer",
 *         example=25
 *     )
 * )
 */
class WalletResource extends JsonResource
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
            'owner_id' => $this->owner_id,
            'owner_type' => $this->owner_type,
            'owner_type_label' => $this->getOwnerTypeLabel(),
            'pays_devise_id' => $this->pays_devise_id,
            'pays_devise' =>new PaysDeviseResource($this->paysDevise),
            'solde_cents' => $this->solde_cents,
            'solde' => $this->getSoldeAttribute(), // Utilise l'accesseur du model
            'solde_bloque_cents' => $this->solde_bloque_cents,
            'solde_bloque' => $this->getSoldeBloqueAttribute(), // Utilise l'accesseur du model
            'solde_commission_cents' => $this->solde_commission_cents,
            'solde_commission' => $this->getSoldeCommissionAttribute(), // Ajout de l'accesseur manquant
            'solde_attente_cents' => $this->solde_attente_cents,
            'solde_attente' => $this->getSoldeAttenteAttribute(), // Ajout de l'accesseur manquant
            'statut' => $this->statut,
            'statut_label' => $this->getStatutLabel(),
            'is_actif' => $this->estActif(),
            'is_suspendu' => $this->estSuspendu(),
            'is_ferme' => $this->estFerme(),
            'meta_json' => $this->meta_json,
            'owner' => match($this->owner_type) {
                'marchand' => new MarchandResource($this->owner),
                default => new UserResource($this->owner),
            },
            'mouvements_count' => $this->when($this->mouvements_count, $this->mouvements_count),
        ];
    }

    /**
     * Obtenir le libellé du type de propriétaire
     */
    private function getOwnerTypeLabel(): string
    {
        return match($this->owner_type) {
            'utilisateur' => 'Utilisateur',
            'marchand' => 'Marchand',
            'agent' => 'Agent',
            'plateforme' => 'Plateforme',
            'client' => 'Client',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir le libellé du statut
     */
    private function getStatutLabel(): string
    {
        return match($this->statut) {
            'actif' => 'Actif',
            'suspendu' => 'Suspendu',
            'ferme' => 'Fermé',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir le solde commission formaté
     */
    private function getSoldeCommissionAttribute(): string
    {
        return \App\Services\MoneyService::toDisplay(
            $this->solde_commission_cents,
            $this->paysDevise?->devise?->code ?? 'XOF'
        );
    }

    /**
     * Obtenir le solde attente formaté
     */
    private function getSoldeAttenteAttribute(): string
    {
        return \App\Services\MoneyService::toDisplay(
            $this->solde_attente_cents,
            $this->paysDevise?->devise?->code ?? 'XOF'
        );
    }
}
