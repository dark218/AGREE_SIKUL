<?php

namespace Modules\Finances\Services;

use Modules\Finances\Entities\ExamFinanceMonthlyReport;
use Modules\Academique\Entities\PlanificationExamen;
use Modules\Academique\Entities\PlanificationExamenPosteRecette;
use Carbon\Carbon;
use DB;

class ExamFinanceReportService
{
    /**
     * Generate monthly report for a specific month
     */
    public function generateMonthlyReport(Carbon $mois, $ecoleId = null): ExamFinanceMonthlyReport
    {
        $debut = $mois->copy()->startOfMonth();
        $fin = $mois->copy()->endOfMonth();

        // Get all exam-financing associations for the month
        $examsQuery = PlanificationExamen::whereBetween('date', [$debut, $fin]);

        if ($ecoleId) {
            // Filter by school if specified
            $examsQuery->whereHas('classe', function ($q) use ($ecoleId) {
                $q->where('ecole_id', $ecoleId);
            });
        }

        $exams = $examsQuery->with('financingAssociations.posteRecette')->get();

        // Calculate statistics
        $stats = $this->calculateStatistics($exams, $ecoleId);

        // Create or update the report
        $report = ExamFinanceMonthlyReport::updateOrCreate(
            [
                'mois' => $debut,
                'ecole_id' => $ecoleId,
            ],
            [
                'total_examens_planifies' => $stats['total_planified'],
                'total_examens_finances' => $stats['financed'],
                'total_examens_en_attente' => $stats['pending'],
                'total_examens_non_finances' => $stats['not_financed'],
                'total_examens_clotured' => $stats['closed'],
                'taux_couverture' => $stats['coverage_rate'],
                'montant_total_facture' => $stats['total_invoiced'],
                'montant_total_paye' => $stats['total_paid'],
                'montant_total_impaye' => $stats['total_unpaid'],
                'total_revenues' => $stats['total_revenues'],
                'total_expenses' => $stats['total_expenses'],
                'solde_net' => $stats['net_balance'],
                'generated_at' => now(),
                'generated_by' => auth()->user()?->username ?? 'system',
            ]
        );

        return $report;
    }

    /**
     * Calculate statistics for report
     */
    private function calculateStatistics($exams, $ecoleId = null): array
    {
        $totalPlanified = $exams->count();
        $financed = 0;
        $pending = 0;
        $notFinanced = 0;
        $closed = 0;
        $totalFinancingAmount = 0;
        $totalInvoiced = 0;
        $totalPaid = 0;
        $totalUnpaid = 0;

        foreach ($exams as $exam) {
            $hasFinancing = false;

            foreach ($exam->financingAssociations as $financing) {
                $hasFinancing = true;

                $montant = $financing->montant_finance ?? 0;

                if ($financing->etat_financement === 'actif') {
                    $financed++;
                    $totalFinancingAmount += $montant;
                    $totalInvoiced += $montant;
                } elseif ($financing->etat_financement === 'en-attente') {
                    $pending++;
                } elseif ($financing->etat_financement === 'clôturé') {
                    $closed++;
                }
            }

            if (!$hasFinancing) {
                $notFinanced++;
            }
        }

        // Calculate actual paid and unpaid amounts from versements
        $versementsQuery = DB::table('versements')
            ->whereNull('deleted_at');

        if ($ecoleId) {
            $versementsQuery->where('ecole_id', $ecoleId);
        }

        $totalPaid = $versementsQuery->sum('total_paye') ?? 0;
        $totalUnpaid = DB::table('versements')
            ->whereNull('deleted_at')
            ->when($ecoleId, fn($q) => $q->where('ecole_id', $ecoleId))
            ->sum('restant_a_payer') ?? 0;

        // Calculate total revenues (versements + autres revenus)
        $autresRevenus = DB::table('autres_revenus')
            ->when($ecoleId, fn($q) => $q->where('ecole_id', $ecoleId))
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(uniforme + tenue_mercredi + tenue_sport), 0) as total')
            ->value('total') ?? 0;

        $totalRevenues = $totalPaid + $autresRevenus;

        // Calculate total expenses (salaires + achats_depenses)
        $totalSalaires = DB::table('salaires')
            ->when($ecoleId, fn($q) => $q->where('ecole_id', $ecoleId))
            ->whereNull('deleted_at')
            ->sum('salaire_net') ?? 0;

        $totalAchats = DB::table('achats_depenses')
            ->when($ecoleId, fn($q) => $q->where('ecole_id', $ecoleId))
            ->whereNull('deleted_at')
            ->sum('montant') ?? 0;

        $totalExpenses = $totalSalaires + $totalAchats;

        $coverageRate = $totalPlanified > 0
            ? round(($financed / $totalPlanified) * 100, 2)
            : 0;

        $netBalance = $totalRevenues - $totalExpenses;

        return [
            'total_planified' => $totalPlanified,
            'financed' => $financed,
            'pending' => $pending,
            'not_financed' => $notFinanced,
            'closed' => $closed,
            'coverage_rate' => $coverageRate,
            'total_invoiced' => (float) $totalInvoiced,
            'total_paid' => (float) $totalPaid,
            'total_unpaid' => (float) $totalUnpaid,
            'total_revenues' => (float) $totalRevenues,
            'total_expenses' => (float) $totalExpenses,
            'net_balance' => (float) $netBalance,
        ];
    }

    /**
     * Get monthly report (generate if not exists)
     */
    public function getMonthlyReport(Carbon $mois, $ecoleId = null): ExamFinanceMonthlyReport
    {
        $debut = $mois->copy()->startOfMonth();

        $report = ExamFinanceMonthlyReport::where('mois', $debut);

        if ($ecoleId) {
            $report->where('ecole_id', $ecoleId);
        } else {
            $report->whereNull('ecole_id');
        }

        $existingReport = $report->first();

        // If report doesn't exist or is outdated, generate it
        if (!$existingReport || $existingReport->created_at->diffInHours(now()) > 1) {
            return $this->generateMonthlyReport($mois, $ecoleId);
        }

        return $existingReport;
    }

    /**
     * Get reports for a period
     */
    public function getReportsForPeriod(Carbon $debut, Carbon $fin, $ecoleId = null)
    {
        $query = ExamFinanceMonthlyReport::whereBetween('mois', [
            $debut->copy()->startOfMonth(),
            $fin->copy()->endOfMonth(),
        ]);

        if ($ecoleId) {
            $query->where('ecole_id', $ecoleId);
        }

        return $query->orderBy('mois', 'desc')->get();
    }

    /**
     * Get latest report (most recent)
     */
    public function getLatestReport($ecoleId = null)
    {
        $query = ExamFinanceMonthlyReport::whereNotNull('generated_at');

        if ($ecoleId) {
            $query->where('ecole_id', $ecoleId);
        }

        return $query->orderBy('mois', 'desc')->first();
    }

    /**
     * Generate comparison between two periods
     */
    public function compareMonths(Carbon $moisActuel, Carbon $moisPrecedent, $ecoleId = null): array
    {
        $reportActuel = $this->getMonthlyReport($moisActuel, $ecoleId);
        $reportPrecedent = $this->getMonthlyReport($moisPrecedent, $ecoleId);

        return [
            'current_month' => [
                'mois' => $reportActuel->mois,
                'total_examens' => $reportActuel->total_examens_planifies,
                'taux_couverture' => $reportActuel->taux_couverture,
                'solde_net' => $reportActuel->solde_net,
            ],
            'previous_month' => [
                'mois' => $reportPrecedent->mois,
                'total_examens' => $reportPrecedent->total_examens_planifies,
                'taux_couverture' => $reportPrecedent->taux_couverture,
                'solde_net' => $reportPrecedent->solde_net,
            ],
            'evolution' => [
                'examens_diff' => $reportActuel->total_examens_planifies - $reportPrecedent->total_examens_planifies,
                'taux_diff' => $reportActuel->taux_couverture - $reportPrecedent->taux_couverture,
                'solde_diff' => $reportActuel->solde_net - $reportPrecedent->solde_net,
            ],
        ];
    }

    /**
     * Check if system is in deficit
     */
    public function isSystemDeficitaire($ecoleId = null): bool
    {
        $latestReport = $this->getLatestReport($ecoleId);

        return $latestReport && $latestReport->solde_net < 0;
    }

    /**
     * Get deficit amount if any
     */
    public function getDeficitAmount($ecoleId = null): float
    {
        $latestReport = $this->getLatestReport($ecoleId);

        if (!$latestReport) {
            return 0;
        }

        return $latestReport->solde_net < 0 ? abs($latestReport->solde_net) : 0;
    }

    /**
     * Get well-financed exams (coverage rate >= 95%)
     */
    public function getWellFinancedExams(Carbon $mois, $ecoleId = null)
    {
        $debut = $mois->copy()->startOfMonth();
        $fin = $mois->copy()->endOfMonth();

        return PlanificationExamen::whereBetween('date', [$debut, $fin])
            ->with('financingAssociations')
            ->get()
            ->filter(function ($exam) {
                $totalFinanced = $exam->getMontantFinanceTotal();
                // TODO: Calculate with actual estimated cost
                return $totalFinanced > 0;
            });
    }
}
