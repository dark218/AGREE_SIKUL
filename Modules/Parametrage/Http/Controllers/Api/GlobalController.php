<?php

namespace Modules\Parametrage\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Parametrage\Entities\Devises;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Resources\PaysResource;
use Modules\Parametrage\Resources\DevisesResource;

/**
 * Endpoints publics pour ressources globales (Pays, Devises).
 *
 * Les anciens endpoints commerciaux (fournisseurs de paiement, banques)
 * ont été retirés lors du recentrage AGREE SIKUL sur la gestion scolaire.
 */
class GlobalController extends Controller
{
    use ApiResponseTrait;

    /**
     * Lister tous les pays.
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
            \Log::error('GlobalController@getPays: ' . $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }

    /**
     * Lister toutes les devises disponibles.
     * (Plus de filtrage par pays via le pivot pays_devises qui a été supprimé.)
     */
    public function getDevises(): JsonResponse
    {
        try {
            $devises = Devises::orderBy('libelle')->get();

            return $this->successResponse(
                DevisesResource::collection($devises),
                'Liste des devises'
            );
        } catch (\Throwable $e) {
            \Log::error('GlobalController@getDevises: ' . $e->getMessage());
            return $this->errorResponse('Une erreur est survenue', 500);
        }
    }
}
