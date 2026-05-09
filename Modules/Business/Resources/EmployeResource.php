<?php

namespace Modules\Business\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Business\Entities\Employe;
use Modules\Parametrage\Resources\FichierResource;

class EmployeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    /**
     * @OA\Schema(
     *     schema="EmployeResource",
     *     title="Employé",
     *     description="Modèle d'employé",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="codeEmploye", type="string", example="EMP123"),
     *     @OA\Property(property="dateEmbauche", type="string", format="date", example="2022-01-01"),
     *     @OA\Property(property="typeEmploye", type="string", enum={"caissier", "manager"}, example="caissier"),
     *     @OA\Property(property="typeEmployeLibelle", type="string", example="Caissier", description="Libellé du type d'employé"),
     *     @OA\Property(property="shiftInfo", type="object", nullable=true, example={"debut": "08:00", "fin": "16:00"}),
     *     @OA\Property(property="validatedAt", type="string", format="date-time", nullable=true, example="2022-01-01T10:00:00Z"),
     *     @OA\Property(property="user", ref="#/components/schemas/UserResource", description="Informations de l'utilisateur associé"),
     *     @OA\Property(property="pointVente", type="object", nullable=true, description="Point de vente associé"),
     *     @OA\Property(property="marchand", type="object", nullable=true, description="Marchand associé"),
     *     @OA\Property(property="validatedBy", type="object", nullable=true, description="Utilisateur qui a validé l'employé"),
     *     @OA\Property(property="createdBy", type="object", nullable=true, description="Utilisateur qui a créé l'employé"),
     * )
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codeEmploye' => $this->code_employe,
            'dateEmbauche' => $this->date_embauche,
            'typeEmploye' => $this->type_employe,
            'typeEmployeLibelle' => Employe::getLibelleType($this->type_employe),
            'shiftInfo' => $this->shift_info,
            'validatedAt' => $this->validated_at,
            'user' => $this->when($this->user, function() {
                return [
                    'id' => $this->user->id,
                    'uuid' => $this->user->uuid,
                    'nom' => $this->user->nom,
                    'prenoms' => $this->user->prenoms,
                    'login' => $this->user->login,
                    'fullLogin' => $this->user->full_login,
                    'email' => $this->user->email,
                    'aliasSmil' => $this->user->alias_smil,
                    'kycStatus' => $this->user->kyc_status,
                    'statut' => $this->user->statut,
                    'typePiece' => $this->user->type_piece,
                    'numeroPiece' => $this->user->numero_piece,
                    'dateDelivrance' => $this->user->date_delivrance,
                    'dateNaissance' => $this->user->date_naissance,
                    'lieuDelivrance' => $this->user->lieu_delivrance,
                    'lieuNaissance' => $this->user->lieu_naissance,
                    'role' => $this->user->role,
                    'codeOwner' => $this->user->code_owner,
                    'codeParrain' => $this->user->code_parrain,
                    'provider' => $this->user->provider,
                    'photoProfile' => $this->when($this->user->photoprofile, function() {
                        return new FichierResource($this->user->photoprofile);
                    }),
                    'pieceRecto' => $this->when($this->user->piecerecto, function() {
                        return new FichierResource($this->user->piecerecto);
                    }),
                    'pieceVerso' => $this->when($this->user->pieceverso, function() {
                        return new FichierResource($this->user->pieceverso);
                    }),
                ];
            }),
            'pointVente' => $this->when($this->pointVente, function() {
                return [
                    'id' => $this->pointVente->id,
                    'nom' => $this->pointVente->nom,
                    'adresse' => $this->pointVente->adresse,
                    'telephone' => $this->pointVente->telephone,
                    'statut' => $this->pointVente->statut,
                ];
            }),
            'marchand' => $this->when($this->marchand, function() {
                return [
                    'id' => $this->marchand->id,
                    'raisonSociale' => $this->marchand->raison_sociale,
                    'type' => $this->marchand->type,
                    'identifiantFiscal' => $this->marchand->identifiant_fiscal,
                ];
            }),

        ];
    }
}
