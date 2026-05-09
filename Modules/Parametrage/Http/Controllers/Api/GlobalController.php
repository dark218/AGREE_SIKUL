<?php

namespace Modules\Parametrage\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Parametrage\Entities\Devises;
use Modules\Parametrage\Entities\FournisseurPaiement;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Resources\FournisseurPaiementResource;
use Modules\Parametrage\Resources\PaysResource;
use Modules\Parametrage\Resources\DevisesResource;

class GlobalController extends Controller
{
    use ApiResponseTrait;

    /**
     * List all countries.
     *
     * @OA\Get(
     *     path="/api/v1/global/pays",
     *     tags={"Global"},
     *     summary="Lister tous les pays",
     *     description="Retourne la liste de tous les pays disponibles",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des pays",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des pays"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/PaysResource")
     *             )
     *         )
     *     )
     * )
     */
    public function getPays(): JsonResponse
    {
        try {
            $pays = Pays::orderBy('libelle')->get();

            return $this->successResponse(
                PaysResource::collection($pays),
                'Liste des pays'
            );

        } catch (\Throwable $e) {
            \Log::error("Error");
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * List currencies for the authenticated user's country.
     *
     * @OA\Get(
     *     path="/api/v1/global/devises",
     *     tags={"Global"},
     *     summary="Lister les devises du pays de l'utilisateur",
     *     description="Retourne la liste des devises disponibles pour le pays de l'utilisateur connecté",
     *     security={{"bearer_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des devises du pays",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des devises du pays"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/DevisesResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=404, description="Pays non défini pour l'utilisateur")
     * )
     */
    public function getDevises(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->errorResponse('Utilisateur non authentifié', 401);
            }

            // Récupérer le pays de l'utilisateur
            $paysId = $user->pays_id;

            if (!$paysId) {
                return $this->errorResponse('Pays non défini pour cet utilisateur', 404);
            }

            // Récupérer les devises associées au pays via la table pivot pays_devises
            $devises = Devises::whereHas('paysDevises', function ($query) use ($paysId) {
                $query->where('pays_id', $paysId);
            })->orderBy('libelle')->get();
            return $this->successResponse(
                DevisesResource::collection($devises),
                'Liste des devises du pays'
            );

        } catch (\Throwable $e) {
            \Log::error("Error");
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Get payment providers by user's country.
     *
     * @OA\Get(
     *     path="/api/v1/global/fournisseurs-paiement",
     *     tags={"Global"},
     *     summary="Lister les fournisseurs de paiement du pays",
     *     description="Retourne la liste des fournisseurs de paiement disponibles pour le pays du client connecté",
     *     security={{"bearer_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fournisseurs de paiement disponibles",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fournisseurs de paiement disponibles"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/FournisseurPaiementResource"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function getFournisseursByPays(): JsonResponse
    {
        try {
            $client = auth()->user();

            // Vérifier si l'utilisateur est bien un client
            if (!$client->hasRole(config('appconstants.role.client'))) {
                return $this->errorResponse('Accès réservé aux clients', 403);
            }

            // Récupérer le pays du client
            $paysId = $client->pays_id;

            if (!$paysId) {
                return $this->errorResponse('Pays non défini pour ce client', 400);
            }
            $fournisseursQuery = FournisseurPaiement::where('statut', FournisseurPaiement::STATUT_ACTIF)
                ->whereIn('type', [FournisseurPaiement::TYPE_MOBILE_MONEY,FournisseurPaiement::TYPE_BANQUE])
                ->orderBy('nom');

            // Filtrer par pays si l'utilisateur a un pays_current
            if (!is_null($paysId)) {
                $fournisseursQuery->whereHas('paysDevise', function ($query) use ($paysId) {
                    $query->where('pays_id', $paysId);
                });
            }
            $fournisseurs = $fournisseursQuery->get();

            return $this->successResponse(
                FournisseurPaiementResource::collection($fournisseurs),
                'Fournisseurs de paiement disponibles'
            );

        } catch (\Throwable $e) {
            \Log::error("Error");
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }
}
