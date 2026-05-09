<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Parametrage\Resources\PaysResource;

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     title="User",
 *     description="Resource pour l'entité User",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="nom", type="string", example="Doe"),
 *     @OA\Property(property="prenoms", type="string", example="John"),
 *     @OA\Property(property="login", type="string", example="0747780101"),
 *     @OA\Property(property="full_login", type="string", example="+2250747780101"),
 *     @OA\Property(property="alias_smil", type="string", nullable=true, example="john.doe"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="john@example.com"),
 *     @OA\Property(property="kyc_status", type="string", enum={"non_verifie", "en_attente", "verifie", "rejete"}, example="non_verifie"),
 *     @OA\Property(property="statut", type="string", enum={"non_actif", "actif", "suspendu", "bloque"}, example="actif"),
 *     @OA\Property(property="type_piece", type="string", enum={"passport", "cni", "pc", "ai"}, nullable=true, example="cni"),
 *     @OA\Property(property="numero_piece", type="string", nullable=true, example="123456789"),
 *     @OA\Property(property="date_delivrance", type="string", format="date", nullable=true, example="2020-01-15"),
 *     @OA\Property(property="date_naissance", type="string", format="date", nullable=true, example="1990-05-15"),
 *     @OA\Property(property="lieu_delivrance", type="string", nullable=true, example="Abidjan"),
 *     @OA\Property(property="lieu_naissance", type="string", nullable=true, example="Abidjan"),
 *     @OA\Property(property="code_owner", type="string", example="OWNER123"),
 *     @OA\Property(property="code_parrain", type="string", nullable=true, example="PARRAIN123"),
 *     @OA\Property(property="provider", type="string", nullable=true, example="google"),
 *     @OA\Property(property="provider_id", type="string", nullable=true, example="google_123"),
 *     @OA\Property(property="adresse", type="string", nullable=true, example="123 Rue Principale, Abidjan"),
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
 *         @OA\Items(type="string", example={"client"})
 *     ),
 *     @OA\Property(
 *         property="wallets",
 *         type="array",
 *         nullable=true,
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="solde_cents", type="integer", example=100000),
 *             @OA\Property(property="solde", type="string", example="1 000,00 XOF"),
 *             @OA\Property(property="solde_bloque_cents", type="integer", example=50000),
 *             @OA\Property(property="solde_bloque", type="string", example="500,00 XOF"),
 *             @OA\Property(property="solde_commission_cents", type="integer", example=10000),
 *             @OA\Property(property="solde_commission", type="string", example="100,00 XOF"),
 *             @OA\Property(property="solde_attente_cents", type="integer", example=20000),
 *             @OA\Property(property="solde_attente", type="string", example="200,00 XOF"),
 *             @OA\Property(property="statut", type="string", example="actif"),
 *             @OA\Property(property="statut_label", type="string", example="Actif"),
 *             @OA\Property(property="devise", type="string", example="XOF")
 *         )
 *     ),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="kyc_status_label", type="string", example="Non vérifié"),
 *     @OA\Property(property="statut_label", type="string", example="Actif")
 * )
 */
class UserResource extends JsonResource
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
            'kyc_status' => $this->kyc_status,
            'kyc_status_label' => $this->getKycStatusLabel(),
            'statut' => $this->statut,
            'statut_label' => $this->getStatutLabel(),
            'type_piece' => $this->type_piece,
            'numero_piece' => $this->numero_piece,
            'date_delivrance' => $this->date_delivrance,
            'date_naissance' => $this->date_naissance,
            'lieu_delivrance' => $this->lieu_delivrance,
            'lieu_naissance' => $this->lieu_naissance,
            'code_owner' => $this->code_owner,
            'code_parrain' => $this->code_parrain,
            'provider' => $this->provider,
            'provider_id' => $this->provider_id,
            'adresse' => $this->adresse,
            'qr_data' => $this->qr_data,
            'fcm_token' => $this->when($request->user()?->id === $this->id, $this->fcm_token), // Seulement pour l'utilisateur connecté
            'full_name' => $this->fullName(),
            'pays' =>  new PaysResource($this->pays),
            'photoprofile' => $this->when($this->photoprofile, function() {
                return [
                    'id' => $this->photoprofile->id,
                    'nom' => $this->photoprofile->nom,
                    'source' => $this->photoprofile->source,
                ];
            }),
            'piecerecto' => $this->when($this->piecerecto, function() {
                return [
                    'id' => $this->piecerecto->id,
                    'nom' => $this->piecerecto->nom,
                    'source' => $this->piecerecto->source,
                ];
            }),
            'pieceverso' => $this->when($this->pieceverso, function() {
                return [
                    'id' => $this->pieceverso->id,
                    'nom' => $this->pieceverso->nom,
                    'source' => $this->pieceverso->source,
                ];
            }),
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
            'is_active' => $this->statut === 'actif',
        ];
    }

    /**
     * Obtenir le libellé du statut KYC
     */
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
