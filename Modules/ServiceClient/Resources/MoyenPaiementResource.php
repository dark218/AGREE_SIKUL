<?php

namespace Modules\ServiceClient\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Parametrage\Resources\FournisseurPaiementResource;
use App\Http\Resources\UserResource;

/**
 * @OA\Schema(
 *     schema="MoyenPaiementResource",
 *     title="MoyenPaiement",
 *     description="Resource pour l'entité MoyenPaiement",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="users_id", type="integer", example=123),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/UserResource",
 *         description="Utilisateur propriétaire du moyen de paiement"
 *     ),
 *     @OA\Property(property="fournisseur_id", type="integer", example=5, nullable=true),
 *     @OA\Property(
 *         property="fournisseur",
 *         ref="#/components/schemas/FournisseurPaiementResource",
 *         description="Fournisseur de paiement associé"
 *     ),
 *     @OA\Property(property="type", type="string", enum={"mm", "iban", "card", "wallet"}, example="mm"),
 *     @OA\Property(property="type_label", type="string", example="Mobile Money"),
 *     @OA\Property(property="label", type="string", example="MTN - 0701****1234", nullable=true),
 *     @OA\Property(property="external_id", type="string", example="ext_123456789", nullable=true),
 *     @OA\Property(property="identifiant_masque", type="string", example="0701****1234"),
 *     @OA\Property(property="libelle_complet", type="string", example="MTN Money - 0701****1234"),
 *     @OA\Property(property="is_defaut", type="boolean", example=false),
 *     @OA\Property(property="statut", type="string", enum={"actif", "desactive"}, example="actif"),
 *     @OA\Property(property="statut_label", type="string", example="Actif"),
 *     @OA\Property(property="is_utilisable", type="boolean", example=true),
 *     @OA\Property(property="metadata", type="object", example={"country_code": "CI", "currency": "XOF"}),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 * )
 */
class MoyenPaiementResource extends JsonResource
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
            'users_id' => $this->users_id,
            'user' => $this->when($this->user, function() {
                return [
                    'id' => $this->user->id,
                    'nom' => $this->user->nom,
                    'prenoms' => $this->user->prenoms,
                    'full_name' => $this->user->fullName(),
                    'login' => $this->user->login,
                    'full_login' => $this->user->full_login,
                    'email' => $this->user->email,
                    'statut' => $this->user->statut,
                    'kyc_status' => $this->user->kyc_status,
                ];
            }),
            'fournisseur_id' => $this->fournisseur_id,
            'fournisseur' => $this->when($this->fournisseur, function() {
                return [
                    'id' => $this->fournisseur->id,
                    'nom' => $this->fournisseur->nom,
                    'code' => $this->fournisseur->code,
                    'type' => $this->fournisseur->type,
                    'statut' => $this->fournisseur->statut,
                ];
            }),
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'label' => $this->label,
            'external_id' => $this->external_id,
            'identifiant_masque' => $this->identifiant_masque,
            'libelle_complet' => $this->libelle_complet,
            'is_defaut' => $this->is_defaut,
            'statut' => $this->statut,
            'statut_label' => $this->getStatutLabel(),
            'is_utilisable' => $this->isUtilisable(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Obtenir le libellé du type de moyen de paiement
     */
    private function getTypeLabel(): string
    {
        return match($this->type) {
            'mm' => 'Mobile Money',
            'iban' => 'IBAN',
            'card' => 'Carte',
            'wallet' => 'Wallet',
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
            'desactive' => 'Désactivé',
            default => 'Inconnu',
        };
    }
}
