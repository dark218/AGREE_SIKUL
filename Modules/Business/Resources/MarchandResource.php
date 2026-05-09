<?php

namespace Modules\Business\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarchandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * Transform the resource into an array.
     *
     * @OA\Schema(
     *     schema="MarchandResource",
     *     title="Marchand",
     *     description="Modèle de marchand",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="raison_sociale", type="string", example="AUCHAN"),
     *     @OA\Property(property="identifiant_fiscal", type="string", example="AUCHAN_CI001"),
     *     @OA\Property(property="type", type="string", enum={"informel","boutique","grande_surface"}, example="grande_surface"),
     *     @OA\Property(property="type_label", type="string", example="Grande Surface"),
     *     @OA\Property(property="proprietaire_id", type="integer", example=1),
     *     @OA\Property(
     *         property="proprietaire",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="id", type="integer", example=8),
     *         @OA\Property(property="nom", type="string", example="TANOH"),
     *         @OA\Property(property="prenoms", type="string", example="VINCENT"),
     *         @OA\Property(property="login", type="string", example="0747780473"),
     *         @OA\Property(property="full_login", type="string", example="+2250747780473"),
     *         @OA\Property(property="email", type="string", format="email", example="mr.tanoh.vincent@gmail.com"),
     *         @OA\Property(property="full_name", type="string", example="TANOH VINCENT"),
     *         @OA\Property(
     *             property="photoprofile",
     *             type="object",
     *             nullable=true,
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nom", type="string", example="photo.jpg"),
     *             @OA\Property(property="source", type="string", example="images/photo.jpg")
     *         ),
     *         @OA\Property(
     *             property="piecerecto",
     *             type="object",
     *             nullable=true,
     *             @OA\Property(property="id", type="integer", example=2),
     *             @OA\Property(property="nom", type="string", example="piece_recto.jpg"),
     *             @OA\Property(property="source", type="string", example="images/piece_recto.jpg")
     *         ),
     *         @OA\Property(
     *             property="pieceverso",
     *             type="object",
     *             nullable=true,
     *             @OA\Property(property="id", type="integer", example=3),
     *             @OA\Property(property="nom", type="string", example="piece_verso.jpg"),
     *             @OA\Property(property="source", type="string", example="images/piece_verso.jpg")
     *         )
     *     ),
     *     @OA\Property(property="rccm_id", type="integer", example=1, nullable=true),
     *     @OA\Property(
     *         property="rccm",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="nom", type="string", example="rccm_auchan.pdf"),
     *         @OA\Property(property="source", type="string", example="images/rccm_auchan.pdf")
     *     ),
     *     @OA\Property(property="dfe_id", type="integer", example=2, nullable=true),
     *     @OA\Property(
     *         property="dfe",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="id", type="integer", example=2),
     *         @OA\Property(property="nom", type="string", example="dfe_auchan.pdf"),
     *         @OA\Property(property="source", type="string", example="images/dfe_auchan.pdf")
     *     ),
     *     @OA\Property(property="validated_at", type="string", format="date-time", nullable=true, example="2024-01-15T10:30:00Z"),
     *     @OA\Property(property="validated_by", type="integer", nullable=true, example=1),
     *     @OA\Property(
     *         property="validated_by_user",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="nom", type="string", example="ADMIN"),
     *         @OA\Property(property="prenoms", type="string", example="SYSTEM"),
     *         @OA\Property(property="full_name", type="string", example="ADMIN SYSTEM")
     *     ),
     *     @OA\Property(property="create_by", type="integer", nullable=true, example=1),
     *     @OA\Property(
     *         property="created_by_user",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="nom", type="string", example="ADMIN"),
     *         @OA\Property(property="prenoms", type="string", example="SYSTEM"),
     *         @OA\Property(property="full_name", type="string", example="ADMIN SYSTEM")
     *     ),
     *     @OA\Property(
     *         property="wallets",
     *         type="array",
     *         nullable=true,
     *         @OA\Items(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=4),
     *             @OA\Property(property="solde_cents", type="integer", example=0),
     *             @OA\Property(property="solde", type="string", example="0 XOF"),
     *             @OA\Property(property="solde_bloque_cents", type="integer", example=0),
     *             @OA\Property(property="solde_bloque", type="string", example="0 XOF"),
     *             @OA\Property(property="solde_commission_cents", type="integer", example=0),
     *             @OA\Property(property="solde_commission", type="string", example="0 XOF"),
     *             @OA\Property(property="solde_attente_cents", type="integer", example=0),
     *             @OA\Property(property="solde_attente", type="string", example="0 XOF"),
     *             @OA\Property(property="statut", type="string", example="actif"),
     *             @OA\Property(property="statut_label", type="string", example="Actif"),
     *             @OA\Property(property="devise", type="string", example="XOF")
     *         )
     *     ),
     *     @OA\Property(property="points_vente_count", type="integer", example=null),
     *     @OA\Property(property="employes_count", type="integer", example=null)
     * )
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'raison_sociale' => $this->raison_sociale,
            'identifiant_fiscal' => $this->identifiant_fiscal,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'proprietaire_id' => $this->proprietaire_id,
            'proprietaire' => $this->when($this->proprietaire, function() {
                return [
                    'id' => $this->proprietaire->id,
                    'nom' => $this->proprietaire->nom,
                    'prenoms' => $this->proprietaire->prenoms,
                    'login' => $this->proprietaire->login,
                    'full_login' => $this->proprietaire->full_login,
                    'email' => $this->proprietaire->email,
                    'full_name' => $this->proprietaire->fullName(),
                    'photoprofile' => $this->when($this->proprietaire->photoprofile, function() {
                        return [
                            'id' => $this->proprietaire->photoprofile->id,
                            'nom' => $this->proprietaire->photoprofile->nom,
                            'source' => $this->proprietaire->photoprofile->source,
                        ];
                    }),
                    'piecerecto' => $this->when($this->proprietaire->piecerecto, function() {
                        return [
                            'id' => $this->proprietaire->piecerecto->id,
                            'nom' => $this->proprietaire->piecerecto->nom,
                            'source' => $this->proprietaire->piecerecto->source,
                        ];
                    }),
                    'pieceverso' => $this->when($this->proprietaire->pieceverso, function() {
                        return [
                            'id' => $this->proprietaire->pieceverso->id,
                            'nom' => $this->proprietaire->pieceverso->nom,
                            'source' => $this->proprietaire->pieceverso->source,
                        ];
                    }),
                ];
            }),
            'rccm_id' => $this->rccm_id,
            'rccm' => $this->when($this->rccm, function() {
                return [
                    'id' => $this->rccm->id,
                    'nom' => $this->rccm->nom,
                    'source' => $this->rccm->source,
                ];
            }),
            'dfe_id' => $this->dfe_id,
            'dfe' => $this->when($this->dfe, function() {
                return [
                    'id' => $this->dfe->id,
                    'nom' => $this->dfe->nom,
                    'source' => $this->dfe->source,
                ];
            }),
            'validated_at' => $this->validated_at,
            'validated_by' => $this->validated_by,
            'validated_by_user' => $this->when($this->validatedByUser, function() {
                return [
                    'id' => $this->validatedByUser->id,
                    'nom' => $this->validatedByUser->nom,
                    'prenoms' => $this->validatedByUser->prenoms,
                    'full_name' => $this->validatedByUser->fullName(),
                ];
            }),
            'create_by' => $this->create_by,
            'created_by_user' => $this->when($this->createdByUser, function() {
                return [
                    'id' => $this->createdByUser->id,
                    'nom' => $this->createdByUser->nom,
                    'prenoms' => $this->createdByUser->prenoms,
                    'full_name' => $this->createdByUser->fullName(),
                ];
            }),
            'wallets' => $this->when($this->wallets, function() {
                return $this->wallets->map(function($wallet) {
                    return [
                        'id' => $wallet->id,
                        'solde_cents' => $wallet->solde_cents,
                        'solde' => $wallet->getSoldeAttribute(),
                        'solde_bloque_cents' => $wallet->solde_bloque_cents,
                        'solde_bloque' => $wallet->getSoldeBloqueAttribute(),
                        'solde_commission_cents' => $wallet->solde_commission_cents,
                        'solde_commission' => $wallet->getSoldeCommissionAttribute(),
                        'solde_attente_cents' => $wallet->solde_attente_cents,
                        'solde_attente' => $wallet->getSoldeAttenteAttribute(),
                        'statut' => $wallet->statut,
                        'statut_label' => $wallet->getStatutLabel(),
                        'devise' => $wallet->paysDevise->devise->code ?? 'XOF',
                    ];
                });
            }),
            // 'pointsVente' => PointVenteResource::collection($this->pointsVente),
            // 'employes' => EmployeResource::collection($this->employes),
            'points_vente_count' =>  $this->pointsVente_count,
            'employes_count' => $this->employes_count,
        ];
    }

    private function getKycStatusLabel(): string
    {
        return match($this->kyc_status) {
            'non_verifie' => 'Non vérifié',
            'en_attente' => 'En attente',
            'verifie' => 'Vérifié',
            'rejete' => 'Rejeté',
            default => 'Inconnu',
        };
    }
    private function getStatutLabel(): string
    {
        return match($this->statut) {
            'non_actif' => 'Non actif',
            'actif' => 'Actif',
            'suspendu' => 'Suspendu',
            'bloque' => 'Bloqué',
            default => 'Inconnu',
        };
    }
}
