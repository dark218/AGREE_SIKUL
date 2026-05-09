<?php

namespace Modules\Finances\Services;

use Modules\Finances\Entities\PosteRecette;
use Modules\Academique\Entities\PlanificationExamen;
use Carbon\Carbon;
use DB;

class FinanceControlsService
{
    /**
     * Vérifier la cohérence des montants de versements
     * Montant facturé (frais_scolarite + frais_inscription + frais_dossier) = Montant payé + Montant restant
     */
    public function verifierCoherenceVersements($ecoleId = null): array
    {
        $incoherences = DB::table('versements')
            ->when($ecoleId, fn($q) => $q->where('ecole_id', $ecoleId))
            ->whereNull('deleted_at')
            ->selectRaw('id, apprenant_id, (frais_scolarite + frais_inscription + frais_dossier) as facture, (total_paye + restant_a_payer) as somme_paye_restant')
            ->get()
            ->filter(function ($versement) {
                $facture = $versement->facture ?? 0;
                $sommePayeRestant = $versement->somme_paye_restant ?? 0;
                return abs($facture - $sommePayeRestant) > 0.01; // Tolérance pour les arrondis
            })
            ->map(function ($v) {
                return [
                    'versement_id' => $v->id,
                    'apprenant_id' => $v->apprenant_id,
                    'difference' => abs(($v->facture ?? 0) - ($v->somme_paye_restant ?? 0))
                ];
            })
            ->values()
            ->toArray();

        return $incoherences;
    }

    /**
     * Détecter les dépenses sans recette active correspondante
     */
    public function detecterDepensesSansRecette($ecoleId = null, Carbon $mois = null): array
    {
        $depensesQuery = DB::table('achats_depenses')
            ->when($ecoleId, fn($q) => $q->where('ecole_id', $ecoleId))
            ->whereNull('deleted_at');

        if ($mois) {
            $depensesQuery->whereYear('date_depense', $mois->year)
                ->whereMonth('date_depense', $mois->month);
        }

        $depenses = $depensesQuery->get();

        // Vérifier si des postes recettes actifs existent
        $postesActifs = PosteRecette::where('etat', 'actif')
            ->when($ecoleId, function ($q) use ($ecoleId) {
                return $q->whereHas('ligneRecette', fn($sub) =>
                    $sub->whereHas('groupeCompte', fn($subSub) =>
                        $subSub->where('ecole_id', $ecoleId)
                    )
                );
            })
            ->count();

        $alertes = [];

        // S'il n'y a pas de postes recettes actifs, générer une alerte par dépense
        if ($postesActifs === 0 && $depenses->count() > 0) {
            foreach ($depenses as $depense) {
                $alertes[] = [
                    'type' => 'ALERTE_DEPENSE_SANS_RECETTE',
                    'depense_id' => $depense->id,
                    'montant' => $depense->montant,
                    'date' => $depense->date_depense,
                    'motif' => 'Aucune recette active pour cette période'
                ];
            }
        }

        return $alertes;
    }

    /**
     * Vérifier la couverture des examens par les recettes
     */
    public function verifierCouvertureExamens($ecoleId = null, Carbon $mois = null): array
    {
        $query = PlanificationExamen::query();

        if ($mois) {
            $query->whereBetween('date', [$mois->copy()->startOfMonth(), $mois->copy()->endOfMonth()]);
        }

        if ($ecoleId) {
            $query->whereHas('classe', fn($q) => $q->where('ecole_id', $ecoleId));
        }

        $exams = $query->with('financingAssociations')->get();

        $totalExams = $exams->count();
        $financedExams = $exams->filter(function ($exam) {
            return $exam->financingAssociations
                ->where('etat_financement', 'actif')
                ->count() > 0;
        })->count();

        $nonFinancedExams = $totalExams - $financedExams;

        $coverageRate = $totalExams > 0
            ? round(($financedExams / $totalExams) * 100, 1)
            : 0;

        return [
            'total_examens' => $totalExams,
            'examens_finances' => $financedExams,
            'examens_non_finances' => $nonFinancedExams,
            'taux_couverture' => $coverageRate,
            'status' => $coverageRate >= 95 ? 'EXCELLENT' : ($coverageRate >= 80 ? 'BON' : 'A_AMELIORER')
        ];
    }

    /**
     * Générer toutes les alertes pour une école
     */
    public function genererAlertes($ecoleId = null, Carbon $mois = null): array
    {
        $alertes = [];

        // Alerte 1 : Incoherence de montants
        $incoherences = $this->verifierCoherenceVersements($ecoleId);
        if (!empty($incoherences)) {
            $alertes[] = [
                'type' => 'ALERTE_INCOHERENCE_MONTANTS',
                'severite' => 'HAUTE',
                'nombre' => count($incoherences),
                'message' => 'Montants incohérents détectés dans ' . count($incoherences) . ' versement(s)',
                'details' => $incoherences
            ];
        }

        // Alerte 2 : Dépenses sans recette
        $depensesSansRecette = $this->detecterDepensesSansRecette($ecoleId, $mois);
        if (!empty($depensesSansRecette)) {
            $alertes[] = [
                'type' => 'ALERTE_DEPENSES_SANS_RECETTE',
                'severite' => 'MOYENNE',
                'nombre' => count($depensesSansRecette),
                'message' => count($depensesSansRecette) . ' dépense(s) sans recette active',
                'details' => $depensesSansRecette
            ];
        }

        // Alerte 3 : Couverture insuffisante des examens
        $couverture = $this->verifierCouvertureExamens($ecoleId, $mois);
        if ($couverture['taux_couverture'] < 80) {
            $alertes[] = [
                'type' => 'ALERTE_COUVERTURE_EXAMENS',
                'severite' => $couverture['taux_couverture'] < 50 ? 'CRITIQUE' : 'MOYENNE',
                'taux_couverture' => $couverture['taux_couverture'],
                'examens_non_finances' => $couverture['examens_non_finances'],
                'message' => 'Couverture des examens insuffisante (' . $couverture['taux_couverture'] . '%)',
                'details' => $couverture
            ];
        }

        return $alertes;
    }
}
