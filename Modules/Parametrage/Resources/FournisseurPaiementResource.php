<?php

namespace Modules\Parametrage\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Parametrage\Entities\FournisseurPaiement;

/**
 * @OA\Schema(
 *     schema="FournisseurPaiementResource",
 *     title="FournisseurPaiement",
 *     description="Resource pour l'entité FournisseurPaiement",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Orange Money"),
 *     @OA\Property(property="code", type="string", example="ORANGE"),
 *     @OA\Property(property="type", type="string", example="mm"),
 *     @OA\Property(property="type_label", type="string", example="Mobile Money"),
 *     @OA\Property(property="pays_devise_id", type="integer", example=8),
 *     @OA\Property(
 *         property="pays_devise",
 *         ref="#/components/schemas/PaysDeviseResource",
 *         description="Pays et devise associés au fournisseur"
 *     ),
 *     @OA\Property(property="statut", type="string", example="actif"),
 *     @OA\Property(property="statut_label", type="string", example="Actif"),
 *     @OA\Property(property="metadata", type="object", example={"key": "value"}),
 *     @OA\Property(property="logo_id", type="integer", example=1, nullable=true)
 * )
 */
class FournisseurPaiementResource extends JsonResource
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
            'nom' => $this->nom,
            'code' => $this->code,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'pays_devise_id' => $this->pays_devise_id,
            'pays_devise' =>  new PaysDeviseResource($this->paysDevise),
            'statut' => $this->statut,
            'statut_label' => $this->getStatutLabel(),
            'metadata' => $this->metadata,
            'logo_id' => $this->logo_id,
        ];
    }

    /**
     * Obtenir le libellé du type de fournisseur
     */
    private function getTypeLabel(): string
    {
        return match($this->type) {
            FournisseurPaiement::TYPE_MOBILE_MONEY => 'Mobile Money',
            FournisseurPaiement::TYPE_BANQUE => 'Banque',
            FournisseurPaiement::TYPE_MICROFINANCE => 'Microfinance',
            FournisseurPaiement::TYPE_AGGREGATEUR => 'Agrégateur',
            FournisseurPaiement::TYPE_CARTE => 'Carte',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir le libellé du statut
     */
    private function getStatutLabel(): string
    {
        return match($this->statut) {
            FournisseurPaiement::STATUT_ACTIF => 'Actif',
            FournisseurPaiement::STATUT_INACTIF => 'Inactif',
            default => 'Inconnu',
        };
    }
}
