<?php

namespace Modules\Finances\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finances\Entities\ExamFinanceMonthlyReport;
use Modules\Finances\Services\ExamFinanceReportService;
use Carbon\Carbon;
use Inertia\Inertia;

class ExamFinanceReportController extends Controller
{
    protected $reportService;

    public function __construct(ExamFinanceReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display list of monthly reports
     */
    public function index(Request $request)
    {
        $query = ExamFinanceMonthlyReport::whereNotNull('generated_at');

        // Filter by school
        if ($request->filled('ecole_id')) {
            $query->where('ecole_id', $request->ecole_id);
        } else {
            $query->whereNull('ecole_id');
        }

        // Filter by year
        if ($request->filled('annee')) {
            $query->whereYear('mois', $request->annee);
        }

        $reports = $query->orderBy('mois', 'desc')
            ->paginate(12)
            ->through(function ($report) {
                return [
                    'id' => $report->id,
                    'mois' => $report->mois?->format('F Y'),
                    'mois_raw' => $report->mois?->toDateString(),
                    'ecole_id' => $report->ecole_id,
                    'total_examens_planifies' => $report->total_examens_planifies,
                    'total_examens_finances' => $report->total_examens_finances,
                    'total_examens_en_attente' => $report->total_examens_en_attente,
                    'total_examens_non_finances' => $report->total_examens_non_finances,
                    'total_examens_clotured' => $report->total_examens_clotured,
                    'taux_couverture' => $report->taux_couverture,
                    'solde_net' => $report->solde_net,
                    'solde_net_formatted' => $report->getSoldeFormattedAttribute(),
                    'est_deficitaire' => $report->estDeficitaire(),
                    'est_bien_finance' => $report->estBienFinance(),
                    'generated_at' => $report->generated_at?->toDateTimeString(),
                    'generated_by' => $report->generated_by,
                ];
            });

        return Inertia::render('ExamFinanceReport/Index', [
            'reports' => $reports,
            'filters' => [
                'ecole_id' => $request->ecole_id,
                'annee' => $request->annee ?? now()->year,
            ],
        ]);
    }

    /**
     * Show single report
     */
    public function show(ExamFinanceMonthlyReport $report)
    {
        $report->load('ecole');

        return Inertia::render('ExamFinanceReport/Show', [
            'report' => [
                'id' => $report->id,
                'mois' => $report->mois?->format('F Y'),
                'mois_raw' => $report->mois?->toDateString(),
                'ecole' => $report->ecole?->nom,
                'total_examens_planifies' => $report->total_examens_planifies,
                'total_examens_finances' => $report->total_examens_finances,
                'taux_examens_finances' => $report->getTauxExamensFinancesAttribute(),
                'total_examens_en_attente' => $report->total_examens_en_attente,
                'taux_examens_en_attente' => $report->getTauxExamensEnAttenteAttribute(),
                'total_examens_non_finances' => $report->total_examens_non_finances,
                'taux_examens_non_finances' => $report->getTauxExamensNonFinancesAttribute(),
                'total_examens_clotured' => $report->total_examens_clotured,
                'taux_examens_clotured' => $report->getTauxExamensCloturesAttribute(),
                'taux_couverture' => $report->taux_couverture,
                'montant_total_facture' => $report->montant_total_facture,
                'montant_total_paye' => $report->montant_total_paye,
                'montant_total_impaye' => $report->montant_total_impaye,
                'total_revenues' => $report->total_revenues,
                'total_expenses' => $report->total_expenses,
                'solde_net' => $report->solde_net,
                'solde_net_formatted' => $report->getSoldeFormattedAttribute(),
                'notes_mois' => $report->notes_mois,
                'est_complet' => $report->estComplet(),
                'est_deficitaire' => $report->estDeficitaire(),
                'est_bien_finance' => $report->estBienFinance(),
                'generated_at' => $report->generated_at?->toDateTimeString(),
                'generated_by' => $report->generated_by,
                'created_at' => $report->created_at?->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Generate report for a specific month
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'mois' => 'required|date_format:Y-m',
            'ecole_id' => 'nullable|exists:ecoles,id',
        ]);

        $mois = Carbon::createFromFormat('Y-m', $validated['mois'])->startOfMonth();
        $ecoleId = $validated['ecole_id'] ?? null;

        try {
            $report = $this->reportService->generateMonthlyReport($mois, $ecoleId);

            return redirect()->route('finances.exam-finance-report.show', $report->id)
                ->with('success', 'Rapport généré avec succès');
        } catch (\Exception $e) {
            return back()->withError('Erreur lors de la génération: ' . $e->getMessage());
        }
    }

    /**
     * Get comparison between two months
     */
    public function compareMonths(Request $request)
    {
        $validated = $request->validate([
            'mois_actuel' => 'required|date_format:Y-m',
            'mois_precedent' => 'required|date_format:Y-m',
            'ecole_id' => 'nullable|exists:ecoles,id',
        ]);

        $moisActuel = Carbon::createFromFormat('Y-m', $validated['mois_actuel'])->startOfMonth();
        $moisPrecedent = Carbon::createFromFormat('Y-m', $validated['mois_precedent'])->startOfMonth();
        $ecoleId = $validated['ecole_id'] ?? null;

        $comparison = $this->reportService->compareMonths($moisActuel, $moisPrecedent, $ecoleId);

        return response()->json($comparison);
    }

    /**
     * Get dashboard summary
     */
    public function dashboard(Request $request)
    {
        $ecoleId = $request->query('ecole_id');

        // Get latest report
        $latestReport = $this->reportService->getLatestReport($ecoleId);

        // Get last 6 months
        $reports = $this->reportService->getReportsForPeriod(
            now()->subMonths(6),
            now(),
            $ecoleId
        );

        // Check deficit status
        $isDeficitaire = $this->reportService->isSystemDeficitaire($ecoleId);
        $deficitAmount = $this->reportService->getDeficitAmount($ecoleId);

        return Inertia::render('ExamFinanceReport/Dashboard', [
            'latest_report' => $latestReport ? [
                'mois' => $latestReport->mois?->format('F Y'),
                'total_examens' => $latestReport->total_examens_planifies,
                'taux_couverture' => $latestReport->taux_couverture,
                'solde_net' => $latestReport->solde_net,
                'solde_formatted' => $latestReport->getSoldeFormattedAttribute(),
                'est_deficitaire' => $latestReport->estDeficitaire(),
            ] : null,
            'recent_reports' => $reports->map(fn ($r) => [
                'mois' => $r->mois?->format('M Y'),
                'taux_couverture' => $r->taux_couverture,
                'solde_net' => $r->solde_net,
            ])->toArray(),
            'system_status' => [
                'is_deficitaire' => $isDeficitaire,
                'deficit_amount' => $deficitAmount,
                'last_check' => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Export report to PDF
     */
    public function exportPdf(ExamFinanceMonthlyReport $report)
    {
        // TODO: Implement PDF export
        return response()->json(['message' => 'PDF export not yet implemented']);
    }

    /**
     * Export reports to CSV
     */
    public function exportCsv(Request $request)
    {
        $validated = $request->validate([
            'debut' => 'required|date_format:Y-m',
            'fin' => 'required|date_format:Y-m',
            'ecole_id' => 'nullable|exists:ecoles,id',
        ]);

        $debut = Carbon::createFromFormat('Y-m', $validated['debut'])->startOfMonth();
        $fin = Carbon::createFromFormat('Y-m', $validated['fin'])->endOfMonth();
        $ecoleId = $validated['ecole_id'] ?? null;

        $reports = $this->reportService->getReportsForPeriod($debut, $fin, $ecoleId);

        // Create CSV
        $filename = 'exam-finance-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Mois',
                'Examens Planifiés',
                'Examens Financés',
                'Taux Couverture (%)',
                'Total Revenus',
                'Total Dépenses',
                'Solde Net',
            ]);

            // Data rows
            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->mois->format('F Y'),
                    $report->total_examens_planifies,
                    $report->total_examens_finances,
                    $report->taux_couverture,
                    $report->total_revenues,
                    $report->total_expenses,
                    $report->solde_net,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
