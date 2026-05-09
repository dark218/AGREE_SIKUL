<?php

namespace Modules\Academique\Services;

use Modules\Academique\Entities\PlanificationExamen;
use Modules\Academique\Entities\PlanificationExamenPosteRecette;
use Modules\Finances\Entities\PosteRecette;
use Carbon\Carbon;
use DB;

class ExamFinanceService
{
    /**
     * Associate an exam with a revenue post (poste recette)
     */
    public function associateExamWithPoste(
        PlanificationExamen $exam,
        PosteRecette $poste,
        array $data = []
    ): PlanificationExamenPosteRecette {
        // Verify the exam is active
        if (!$exam->isActif()) {
            throw new \Exception('Exam doit être actif pour être associé à une recette');
        }

        // Verify the poste is active
        if (!$poste->isActif()) {
            throw new \Exception('Poste recette doit être actif pour être associé à un examen');
        }

        // Check if association already exists
        $existing = PlanificationExamenPosteRecette::where('exam_id', $exam->id)
            ->where('recette_id', $poste->id)
            ->first();

        if ($existing) {
            throw new \Exception('Cette association existe déjà');
        }

        // Create the association
        $association = PlanificationExamenPosteRecette::create([
            'exam_id' => $exam->id,
            'recette_id' => $poste->id,
            'montant_finance' => $data['montant_finance'] ?? null,
            'pourcentage_couverture' => $data['pourcentage_couverture'] ?? null,
            'date_facturation' => $data['date_facturation'] ?? null,
            'date_limite_paiement' => $data['date_limite_paiement'] ?? null,
            'etat_financement' => $data['etat_financement'] ?? 'en-attente',
            'notes' => $data['notes'] ?? null,
            'creation_username' => auth()->user()?->username,
        ]);

        // Log the action
        $association->logAction('created', $data['raison'] ?? null);

        return $association;
    }

    /**
     * Validate (approve) an exam-poste financing
     */
    public function validateFinancing(
        PlanificationExamenPosteRecette $financing,
        string $raison = null
    ): bool {
        // Check date coherence
        if (!$financing->estCoherent()) {
            throw new \Exception('Les dates ne sont pas cohérentes. Date facturation doit être avant exam.');
        }

        // Check if exam date has not passed
        if ($financing->estExpiree()) {
            throw new \Exception('L\'examen est déjà passé, impossible de le valider');
        }

        // Update state to active
        $financing->etat_financement = 'actif';
        $financing->modification_username = auth()->user()?->username;
        $financing->save();

        // Log the action
        $financing->logAction('validated', $raison);

        return true;
    }

    /**
     * Reject an exam-poste financing
     */
    public function rejectFinancing(
        PlanificationExamenPosteRecette $financing,
        string $raison
    ): bool {
        $previousState = $financing->etat_financement;

        $financing->etat_financement = 'en-attente';
        $financing->modification_username = auth()->user()?->username;
        $financing->save();

        // Log with previous state
        $financing->auditLogs()->create([
            'auteur_id' => auth()->user()?->id,
            'action' => 'rejected',
            'ancienne_valeur_etat' => $previousState,
            'nouvelle_valeur_etat' => 'en-attente',
            'raison_changement' => $raison,
        ]);

        return true;
    }

    /**
     * Close an exam-poste financing
     */
    public function closeFinancing(
        PlanificationExamenPosteRecette $financing,
        string $raison = null
    ): bool {
        $previousState = $financing->etat_financement;

        $financing->etat_financement = 'clôturé';
        $financing->modification_username = auth()->user()?->username;
        $financing->save();

        // Log the action
        $financing->auditLogs()->create([
            'auteur_id' => auth()->user()?->id,
            'action' => 'closed',
            'ancienne_valeur_etat' => $previousState,
            'nouvelle_valeur_etat' => 'clôturé',
            'raison_changement' => $raison,
        ]);

        return true;
    }

    /**
     * Update financing amount and details
     */
    public function updateFinancing(
        PlanificationExamenPosteRecette $financing,
        array $data
    ): PlanificationExamenPosteRecette {
        $previousState = $financing->etat_financement;
        $previousAmount = $financing->montant_finance;

        // Update fields
        if (isset($data['montant_finance'])) {
            $financing->montant_finance = $data['montant_finance'];
        }

        if (isset($data['pourcentage_couverture'])) {
            $financing->pourcentage_couverture = $data['pourcentage_couverture'];
        }

        if (isset($data['date_facturation'])) {
            $financing->date_facturation = $data['date_facturation'];
        }

        if (isset($data['date_limite_paiement'])) {
            $financing->date_limite_paiement = $data['date_limite_paiement'];
        }

        if (isset($data['notes'])) {
            $financing->notes = $data['notes'];
        }

        // Verify coherence before saving
        if (!$financing->estCoherent()) {
            throw new \Exception('Les dates ne sont pas cohérentes');
        }

        $financing->modification_username = auth()->user()?->username;
        $financing->save();

        // Log if amount changed
        if ($previousAmount !== $financing->montant_finance) {
            $financing->auditLogs()->create([
                'auteur_id' => auth()->user()?->id,
                'action' => 'updated',
                'ancienne_valeur_etat' => $previousState,
                'nouvelle_valeur_etat' => $financing->etat_financement,
                'montant_precedent' => $previousAmount,
                'montant_nouveau' => $financing->montant_finance,
                'raison_changement' => $data['raison'] ?? null,
            ]);
        }

        return $financing;
    }

    /**
     * Get exam financing status summary
     */
    public function getExamFinancingStatus(PlanificationExamen $exam): array
    {
        $financings = $exam->financingAssociations()->get();

        $actifs = $financings->where('etat_financement', 'actif')->count();
        $enAttente = $financings->where('etat_financement', 'en-attente')->count();
        $clotured = $financings->where('etat_financement', 'clôturé')->count();
        $total = $financings->count();

        $totalFinanced = $financings->where('etat_financement', 'actif')->sum('montant_finance');

        return [
            'total_associations' => $total,
            'financed_count' => $actifs,
            'pending_count' => $enAttente,
            'closed_count' => $clotured,
            'total_amount_financed' => (float) $totalFinanced,
            'is_fully_financed' => $actifs > 0 && $enAttente === 0,
            'has_pending' => $enAttente > 0,
            'status' => $this->determineExamStatus($exam, $financings),
        ];
    }

    /**
     * Determine overall exam financing status
     */
    private function determineExamStatus($exam, $financings): string
    {
        if ($financings->isEmpty()) {
            return 'non-finance';
        }

        if ($financings->where('etat_financement', 'actif')->isNotEmpty()) {
            return 'finance';
        }

        if ($financings->where('etat_financement', 'en-attente')->isNotEmpty()) {
            return 'en-attente';
        }

        return 'clôturé';
    }

    /**
     * Get exams pending validation (due within X days)
     */
    public function getExamsPendingValidation(int $jours = 7): array
    {
        return PlanificationExamenPosteRecette::enAttente()
            ->prochesExamen($jours)
            ->with(['planificationExamen', 'posteRecette'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Check if an exam can be scheduled (must be financed or clôturé state not allowed)
     */
    public function canExamBeScheduled(PlanificationExamen $exam): bool
    {
        // Check if exam has active financing
        $hasActiveFinancing = $exam->financingAssociations()
            ->where('etat_financement', 'actif')
            ->exists();

        // Check if exam has closed financing
        $hasClosedFinancing = $exam->financingAssociations()
            ->where('etat_financement', 'clôturé')
            ->exists();

        // Exam cannot be scheduled if it has closed financing
        if ($hasClosedFinancing) {
            return false;
        }

        // Exam can be scheduled if it has active financing or no financing at all
        return true;
    }

    /**
     * Calculate coverage rate for an exam
     */
    public function calculateCoverageRate(PlanificationExamen $exam): float
    {
        $totalFinanced = $exam->getMontantFinanceTotal();

        // TODO: Implement with business logic for estimated cost
        // For now return 0 as placeholder
        if ($totalFinanced === 0) {
            return 0;
        }

        return round(($totalFinanced / 1000) * 100, 2); // Placeholder calculation
    }

    /**
     * Get monthly report data
     */
    public function generateMonthlyReportData(Carbon $mois): array
    {
        $debut = $mois->copy()->startOfMonth();
        $fin = $mois->copy()->endOfMonth();

        // Get all exams in the period
        $exams = PlanificationExamen::whereBetween('date', [$debut, $fin])
            ->with('financingAssociations')
            ->get();

        $totalPlanified = $exams->count();
        $financed = 0;
        $pending = 0;
        $notFinanced = 0;
        $closed = 0;
        $totalAmountFinanced = 0;

        foreach ($exams as $exam) {
            $financing = $exam->financingAssociations()->first();

            if (!$financing) {
                $notFinanced++;
            } elseif ($financing->etat_financement === 'actif') {
                $financed++;
                $totalAmountFinanced += $financing->montant_finance ?? 0;
            } elseif ($financing->etat_financement === 'en-attente') {
                $pending++;
            } elseif ($financing->etat_financement === 'clôturé') {
                $closed++;
            }
        }

        $coverageRate = $totalPlanified > 0
            ? round(($financed / $totalPlanified) * 100, 2)
            : 0;

        return [
            'mois' => $debut,
            'total_examens_planifies' => $totalPlanified,
            'total_examens_finances' => $financed,
            'total_examens_en_attente' => $pending,
            'total_examens_non_finances' => $notFinanced,
            'total_examens_clotured' => $closed,
            'taux_couverture' => $coverageRate,
            'montant_total_facture' => 0, // TODO: Calculate from factures
            'montant_total_paye' => 0, // TODO: Calculate from payments
            'montant_total_impaye' => 0, // TODO: Calculate from outstanding
            'total_revenues' => $totalAmountFinanced,
            'total_expenses' => 0, // TODO: Calculate from expenses
            'solde_net' => $totalAmountFinanced, // TODO: Recettes - Dépenses
        ];
    }
}
