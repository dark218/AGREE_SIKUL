<?php

namespace Modules\Finances\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finances\Services\FinanceControlsService;
use Modules\Academique\Entities\PlanificationExamen;
use Modules\Finances\Entities\PosteRecette;
use Carbon\Carbon;
use DB;
use Inertia\Inertia;

class RapportFinancierController extends Controller
{
    protected $controlsService;

    public function __construct(FinanceControlsService $controlsService)
    {
        $this->controlsService = $controlsService;
    }

    /**
     * Afficher le rapport financier consolidé (READ-ONLY)
     *
     * ARCHITECTURE DES CALCULS:
     * ─────────────────────────────
     * 1. TABLEAU PRINCIPAL (8 colonnes) = Vue par POSTE RECETTE (planned/invoiced)
     *    - montant_facture: SUM(exam_poste_recette.montant_finance) = exams linked to this revenue item
     *    - montant_paye: 0 (versements table has no direct FK to postes_recettes)
     *    - montant_restant: montant_facture - montant_paye = facturé - payé
     *    - depenses_associees: 0 (salaires/achats have no direct FK to postes_recettes)
     *    - solde_net: montant_facture - montant_paye - depenses_associees
     *
     * 2. TABLEAU CROISÉ = Vue par EXAMEN (planned exam-recette associations)
     *    - Shows which revenue items finance which exams
     *    - Each row = 1 exam, columns = revenue items associated with it
     *
     * 3. GLOBAL SOLDES (3 cards) = Vue GLOBALE (actual/realized)
     *    - total_recettes: SUM(versements.total_paye) = actual payments received
     *    - autres_revenus: SUM(autres_revenus) = other income
     *    - total_salaires: SUM(salaires.salaire_net) = actual salaries paid
     *    - total_achats: SUM(achats_depenses.montant) = actual purchases/expenses
     *    - solde_net = total_recettes + autres_revenus - total_salaires - total_achats
     *
     * ⚠️  LIMITATIONS ACTUELLES:
     * - Tableau principal montre les montants PLANIFIÉS (exam financing)
     * - Pas de mapping versements→postes_recettes pour montant_paye détaillé
     * - Pas de mapping salaires/achats→postes_recettes pour depenses par recette
     * - Les vraies valeurs de cash flow sont dans le tableau global (soldes cards)
     */
    public function index(Request $request)
    {
        // Déterminer le mois sélectionné
        $mois = $request->filled('mois')
            ? Carbon::createFromFormat('Y-m', $request->mois)->startOfMonth()
            : now()->startOfMonth();

        $ecoleId = $request->ecole_id;

        // ===== TABLEAU PRINCIPAL (8 colonnes) =====
        // NOTA: Ce tableau affiche les MONTANTS FACTURÉS (exam_poste_recette)
        // Les paiements réels et dépenses associées sont dans le tableau global (soldes) ci-dessous
        $lignesRapport = DB::table('postes_recettes as pr')
            ->leftJoin('lignes_recettes as lr', 'pr.ligne_recette_id', '=', 'lr.id')
            ->leftJoin('exam_poste_recette as epr', 'pr.id', '=', 'epr.recette_id')
            ->select(
                'pr.id',
                'pr.code as code_recette',
                'pr.libelle as libelle_recette',
                'pr.etat as etat_recette',
                // Montant facturé = total exam financing linked to this revenue item
                DB::raw('COALESCE(SUM(epr.montant_finance), 0) as montant_facture'),
                // NOTE: montant_paye requires mapping versements→postes_recettes which doesn't exist in schema
                // Currently showing 0, but could be calculated if versements table had poste_recette_id FK
                DB::raw('0 as montant_paye'),
                // Montant restant = facturé - payé (currently = facturé since paye = 0)
                DB::raw('COALESCE(SUM(epr.montant_finance), 0) as montant_restant'),
                // NOTE: depenses_associees requires mapping salaires/achats→postes_recettes which doesn't exist
                // Could be calculated proportionally based on versements allocation per recette
                DB::raw('0 as depenses_associees'),
                // Solde net per recette = facturé - payé - dépenses
                DB::raw('COALESCE(SUM(epr.montant_finance), 0) as solde_net')
            )
            ->whereNull('pr.deleted_at')
            ->groupBy('pr.id', 'pr.code', 'pr.libelle', 'pr.etat')
            ->orderBy('pr.code')
            ->get()
            ->map(function ($row) {
                // Calculate derived fields
                $montant_facture = (float)$row->montant_facture;
                $montant_paye = (float)$row->montant_paye;
                $montant_restant = $montant_facture - $montant_paye;
                $depenses_associees = (float)$row->depenses_associees;
                $solde_net = $montant_facture - $montant_paye - $depenses_associees;

                return [
                    'id' => $row->id,
                    'code_recette' => $row->code_recette,
                    'libelle_recette' => $row->libelle_recette,
                    'etat_recette' => $row->etat_recette,
                    'montant_facture' => $montant_facture,
                    'montant_paye' => $montant_paye,
                    'montant_restant' => $montant_restant,
                    'depenses_associees' => $depenses_associees,
                    'solde_net' => $solde_net
                ];
            });

        // Calcul des totaux des colonnes
        $totalsMontantFacture = $lignesRapport->sum('montant_facture');
        $totalsMontantPaye = $lignesRapport->sum('montant_paye');
        $totalsMontantRestant = $lignesRapport->sum('montant_restant');
        $totalsDépenses = $lignesRapport->sum('depenses_associees');
        $totalsSoldeNet = $totalsMontantFacture - $totalsDépenses;

        // ===== TABLEAU CROISÉ : Examens × Postes Recettes =====
        $tableauCroise = PlanificationExamen::with([
            'classe:id,nom',
            'matiere:id,nom',
            'financingAssociations' => function ($query) {
                $query->with('posteRecette:id,code,libelle');
            }
        ])
        ->whereNull('planifications_examens.deleted_at')
        ->whereBetween('date', [$mois->copy()->startOfMonth(), $mois->copy()->endOfMonth()])
        ->when($ecoleId, function ($query) use ($ecoleId) {
            return $query->whereHas('classe', fn($q) => $q->where('ecole_id', $ecoleId));
        })
        ->get()
        ->map(function ($exam) {
            return [
                'exam_id' => $exam->id,
                'classe' => $exam->classe?->nom,
                'matiere' => $exam->matiere?->nom,
                'date' => $exam->date?->format('Y-m-d'),
                'postes' => $exam->financingAssociations->map(function ($assoc) {
                    return [
                        'code' => $assoc->posteRecette?->code,
                        'libelle' => $assoc->posteRecette?->libelle,
                        'montant' => (float)$assoc->montant_finance,
                        'etat' => $assoc->etat_financement,
                        'date_facturation' => $assoc->date_facturation?->format('Y-m-d'),
                    ];
                })->toArray()
            ];
        })
        ->toArray();

        // ===== SOLDES GLOBAUX =====
        $versementsQuery = DB::table('versements')
            ->whereNull('deleted_at');
        if ($ecoleId) {
            $versementsQuery->where('ecole_id', $ecoleId);
        }

        $totalRecettes = $versementsQuery->sum('total_paye') ?? 0;

        $autresRevenusQuery = DB::table('autres_revenus')
            ->whereNull('deleted_at');
        if ($ecoleId) {
            $autresRevenusQuery->where('ecole_id', $ecoleId);
        }

        $autresRevenus = $autresRevenusQuery
            ->selectRaw('COALESCE(SUM(uniforme + tenue_mercredi + tenue_sport), 0) as total')
            ->value('total') ?? 0;

        $salairesQuery = DB::table('salaires')
            ->whereNull('deleted_at');
        if ($ecoleId) {
            $salairesQuery->where('ecole_id', $ecoleId);
        }

        $totalSalaires = $salairesQuery->sum('salaire_net') ?? 0;

        $achatsQuery = DB::table('achats_depenses')
            ->whereNull('deleted_at');
        if ($ecoleId) {
            $achatsQuery->where('ecole_id', $ecoleId);
        }

        $totalAchats = $achatsQuery->sum('montant') ?? 0;

        $totalRevenues = $totalRecettes + $autresRevenus;
        $totalExpenses = $totalSalaires + $totalAchats;
        $soldeNet = $totalRevenues - $totalExpenses;

        // ===== COUVERTURE DES EXAMENS =====
        $couverture = $this->controlsService->verifierCouvertureExamens($ecoleId, $mois);

        // ===== ALERTES =====
        $alertes = $this->controlsService->genererAlertes($ecoleId, $mois);

        return Inertia::render('Finances::RapportFinancier/Index', [
            'lignes_rapport' => $lignesRapport,
            'totals_rapport' => [
                'montant_facture' => (float)$totalsMontantFacture,
                'montant_paye' => (float)$totalsMontantPaye,
                'montant_restant' => (float)$totalsMontantRestant,
                'depenses_associees' => (float)$totalsDépenses,
                'solde_net' => (float)$totalsSoldeNet,
            ],
            'tableau_croise' => $tableauCroise,
            'soldes' => [
                'total_recettes' => (float)$totalRecettes,
                'autres_revenus' => (float)$autresRevenus,
                'total_revenues' => (float)$totalRevenues,
                'total_salaires' => (float)$totalSalaires,
                'total_achats' => (float)$totalAchats,
                'total_depenses' => (float)$totalExpenses,
                'solde_net' => (float)$soldeNet,
                'est_deficitaire' => $soldeNet < 0,
            ],
            'couverture_examens' => $couverture,
            'alertes' => $alertes,
            'mois_selectionne' => $mois->format('Y-m'),
            'mois_label' => $mois->translatedFormat('F Y', null, 'fr'),
            'filters' => [
                'mois' => $request->mois ?? $mois->format('Y-m'),
                'ecole_id' => $ecoleId
            ],
        ]);
    }
}
