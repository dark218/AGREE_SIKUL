<?php

namespace Modules\Personnel\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Parametrage\Resources\FichierResource;
use Modules\Parametrage\Resources\PaysResource;
use Modules\Parametrage\Resources\ZoneResource;

/**
 * @OA\Schema(
 *     schema="AgentResource",
 *     title="Agent",
 *     description="Resource pour l'entité Agent",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="nom", type="string", example="Doe"),
 *     @OA\Property(property="prenoms", type="string", example="John"),
 *     @OA\Property(property="login", type="string", example="0747780101"),
 *     @OA\Property(property="full_login", type="string", example="+2250747780101"),
 *     @OA\Property(property="alias_smil", type="string", nullable=true, example="john.doe"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="john@example.com"),
 *     @OA\Property(property="statut", type="string", enum={"non_actif", "actif", "suspendu", "bloque"}, example="actif"),
 *     @OA\Property(property="role", type="string", example="agent"),
 *     @OA\Property(property="code_owner", type="string", example="OWNER123"),
 *     @OA\Property(property="qr_data", type="string", nullable=true, example="QR_DATA_HERE"),
 *     @OA\Property(property="fcm_token", type="string", nullable=true, example="fcm_token_here"),
 *     @OA\Property(property="full_name", type="string", example="John Doe"),
 *     @OA\Property(
 *         property="pays",
 *         ref="#/components/schemas/PaysResource"
 *     ),
 *     @OA\Property(
 *         property="photoprofile",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="nom", type="string", example="photo.jpg"),
 *         @OA\Property(property="source", type="string", example="images/photo.jpg")
 *     ),
 *     @OA\Property(
 *         property="piecerecto",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=2),
 *         @OA\Property(property="nom", type="string", example="piece_recto.jpg"),
 *         @OA\Property(property="source", type="string", example="images/piece_recto.jpg")
 *     ),
 *     @OA\Property(
 *         property="pieceverso",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=3),
 *         @OA\Property(property="nom", type="string", example="piece_verso.jpg"),
 *         @OA\Property(property="source", type="string", example="images/piece_verso.jpg")
 *     ),
 *     @OA\Property(
 *         property="roles",
 *         type="array",
 *         @OA\Items(type="string", example={"agent"})
 *     ),
 *          @OA\Property(
 *          property="wallets",
 *          type="array",
 *          nullable=true,
 *          @OA\Items(
 *              type="object",
 *              @OA\Property(property="id", type="integer", example=1),
 *              @OA\Property(property="solde_cents", type="integer", example=100000),
 *              @OA\Property(property="solde", type="string", example="1 000,00 XOF"),
 *              @OA\Property(property="solde_bloque_cents", type="integer", example=50000),
 *              @OA\Property(property="solde_bloque", type="string", example="500,00 XOF"),
 *              @OA\Property(property="solde_commission_cents", type="integer", example=10000),
 *              @OA\Property(property="solde_commission", type="string", example="100,00 XOF"),
 *              @OA\Property(property="solde_attente_cents", type="integer", example=20000),
 *              @OA\Property(property="solde_attente", type="string", example="200,00 XOF"),
 *              @OA\Property(property="statut", type="string", example="actif"),
 *              @OA\Property(property="statut_label", type="string", example="Actif"),
 *              @OA\Property(property="devise", type="string", example="XOF")
 *          )
 *      ),
 *     @OA\Property(
 *         property="affectations_actives",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="zone", ref="#/components/schemas/ZoneResource"),
 *             @OA\Property(property="role_affectation", type="string", example="agent_commercial"),
 *             @OA\Property(property="role_affectation_label", type="string", example="Agent Commercial"),
 *             @OA\Property(property="date_affectation", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
 *             @OA\Property(property="actif", type="boolean", example=true)
 *         )
 *     ),
 *     @OA\Property(
 *         property="missions_en_cours",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="titre", type="string", example="Visite clients zone Nord"),
 *             @OA\Property(property="statut", type="string", example="en_cours"),
 *             @OA\Property(property="statut_label", type="string", example="En cours"),
 *             @OA\Property(property="zone", ref="#/components/schemas/ZoneResource"),
 *             @OA\Property(property="date_debut", type="string", format="date", example="2024-01-15"),
 *             @OA\Property(property="date_fin", type="string", format="date", nullable=true, example="2024-01-20")
 *         )
 *     ),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="statut_label", type="string", example="Actif"),
 *     @OA\Property(property="nombre_affectations_actives", type="integer", example=2),
 *     @OA\Property(property="nombre_missions_en_cours", type="integer", example=1)
 * )
 */
class AgentResource extends JsonResource
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
            'uuid' => $this->uuid,
            'nom' => $this->nom,
            'prenoms' => $this->prenoms,
            'login' => $this->login,
            'full_login' => $this->full_login,
            'alias_smil' => $this->alias_smil,
            'email' => $this->email,
            'statut' => $this->statut,
            'statut_label' => $this->getStatutLabel(),
            'role' => $this->role,
            'code_owner' => $this->code_owner,
            'qr_data' => $this->qr_data,
            'fcm_token' => $this->when($request->user()?->id === $this->id, $this->fcm_token), // Seulement pour l'utilisateur connecté
            'full_name' => $this->fullName(),
            'pays' => new PaysResource($this->pays),
            'photoprofile' => $this->when($this->photoprofile, function() {
                return [
                    'id' => $this->photoprofile->id,
                    'nom' => $this->photoprofile->nom,
                    'source' => $this->photoprofile->source,
                ];
            },null),
            'piecerecto' => $this->when($this->piecerecto, function() {
                return [
                    'id' => $this->piecerecto->id,
                    'nom' => $this->piecerecto->nom,
                    'source' => $this->piecerecto->source,
                ];
            },null),
            'pieceverso' => $this->when($this->pieceverso, function() {
                return [
                    'id' => $this->pieceverso->id,
                    'nom' => $this->pieceverso->nom,
                    'source' => $this->pieceverso->source,
                ];
            },null),
            'roles' => $this->roles->pluck('name'),
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
            'affectations_actives' => $this->when($this->affectationsActives, function() {
                return $this->affectationsActives->map(function($affectation) {
                    return [
                        'id' => $affectation->id,
                        'zone' => new ZoneResource($affectation->zone),
                        'role_affectation' => $affectation->role_affectation,
                        'role_affectation_label' => $affectation->getRoleAffectationLabel(),
                        'date_affectation' => $affectation->date_affectation,
                        'actif' => $affectation->actif,
                    ];
                });
            },[]),
            'missions_en_cours' => $this->when($this->missions, function() {
                return $this->missions()->enCours()->get()->map(function($mission) {
                    return [
                        'id' => $mission->id,
                        'titre' => $mission->titre,
                        'statut' => $mission->statut,
                        'statut_label' => $mission->getStatutLabel(),
                        'zone' => $mission->zone ? new ZoneResource($mission->zone) : null,
                        'date_debut' => $mission->date_debut,
                        'date_fin' => $mission->date_fin,
                    ];
                });
            },[]),
            'is_active' => $this->statut === 'actif',
            'nombre_affectations_actives' => $this->affectationsActives()->count(),
            'nombre_missions_en_cours' => $this->missions()->enCours()->count(),
        ];
    }

    /**
     * Obtenir le libellé du statut
     */
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
