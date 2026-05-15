<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Parametrage\Entities\Commune;
use Modules\Parametrage\Entities\Departement;
use Modules\Parametrage\Entities\Quartier;
use Modules\Parametrage\Entities\Region;

/**
 * Endpoints AJAX de cascade pour les formulaires Parametrage.
 * Retourne la hiérarchie ascendante d'un nœud géographique.
 */
class LookupController extends Controller
{
    /**
     * GET /parametrage/api/quartier/{id}/hierarchy
     * Retourne commune_id, departement_id, region_id, pays_id et leurs libellés.
     */
    public function quartierHierarchy(int $id): JsonResponse
    {
        $quartier = Quartier::with(['commune.departement.region.pays'])->find($id);
        if (!$quartier) {
            return response()->json(['error' => 'Quartier non trouvé'], 404);
        }

        $commune = $quartier->commune;
        $departement = $commune?->departement;
        $region = $departement?->region;
        $pays = $region?->pays;

        return response()->json([
            'quartier_id' => $quartier->id,
            'quartier_libelle' => $quartier->libelle,
            'commune_id' => $commune?->id,
            'commune_libelle' => $commune?->libelle,
            'departement_id' => $departement?->id,
            'departement_libelle' => $departement?->libelle,
            'region_id' => $region?->id,
            'region_libelle' => $region?->libelle,
            'pays_id' => $pays?->id,
            'pays_libelle' => $pays?->libelle,
        ]);
    }

    /**
     * GET /parametrage/api/commune/{id}/hierarchy
     */
    public function communeHierarchy(int $id): JsonResponse
    {
        $commune = Commune::with(['departement.region.pays'])->find($id);
        if (!$commune) {
            return response()->json(['error' => 'Commune non trouvée'], 404);
        }

        $departement = $commune->departement;
        $region = $departement?->region;
        $pays = $region?->pays;

        return response()->json([
            'commune_id' => $commune->id,
            'commune_libelle' => $commune->libelle,
            'departement_id' => $departement?->id,
            'departement_libelle' => $departement?->libelle,
            'region_id' => $region?->id,
            'region_libelle' => $region?->libelle,
            'pays_id' => $pays?->id,
            'pays_libelle' => $pays?->libelle,
        ]);
    }

    /**
     * GET /parametrage/api/departement/{id}/hierarchy
     */
    public function departementHierarchy(int $id): JsonResponse
    {
        $departement = Departement::with('region.pays')->find($id);
        if (!$departement) {
            return response()->json(['error' => 'Département non trouvé'], 404);
        }

        $region = $departement->region;
        $pays = $region?->pays;

        return response()->json([
            'departement_id' => $departement->id,
            'departement_libelle' => $departement->libelle,
            'region_id' => $region?->id,
            'region_libelle' => $region?->libelle,
            'pays_id' => $pays?->id,
            'pays_libelle' => $pays?->libelle,
        ]);
    }

    /**
     * GET /parametrage/api/region/{id}/hierarchy
     */
    public function regionHierarchy(int $id): JsonResponse
    {
        $region = Region::with('pays')->find($id);
        if (!$region) {
            return response()->json(['error' => 'Région non trouvée'], 404);
        }

        return response()->json([
            'region_id' => $region->id,
            'region_libelle' => $region->libelle,
            'pays_id' => $region->pays?->id,
            'pays_libelle' => $region->pays?->libelle,
        ]);
    }
}
