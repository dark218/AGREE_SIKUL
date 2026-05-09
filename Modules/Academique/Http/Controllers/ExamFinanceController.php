<?php

namespace Modules\Academique\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Academique\Entities\PlanificationExamen;
use Modules\Academique\Entities\PlanificationExamenPosteRecette;
use Modules\Finances\Entities\PosteRecette;
use Modules\Academique\Services\ExamFinanceService;
use Inertia\Inertia;

class ExamFinanceController extends Controller
{
    protected $examFinanceService;

    public function __construct(ExamFinanceService $examFinanceService)
    {
        $this->examFinanceService = $examFinanceService;
    }

    /**
     * Display list of exam-financing associations
     */
    public function index(Request $request)
    {
        $query = PlanificationExamenPosteRecette::with([
            'planificationExamen.classe.ecole',
            'planificationExamen.matiere',
            'posteRecette',
        ]);

        // Filter by status
        if ($request->filled('etat')) {
            $query->where('etat_financement', $request->etat);
        }

        // Filter by exam
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        // Filter by poste recette
        if ($request->filled('recette_id')) {
            $query->where('recette_id', $request->recette_id);
        }

        // Filter by school
        if ($request->filled('ecole_id')) {
            $query->whereHas('planificationExamen.classe', function ($q) {
                $q->where('ecole_id', request('ecole_id'));
            });
        }

        $financings = $query->paginate(10)->through(function ($item) {
            return [
                'id' => $item->id,
                'exam_id' => $item->exam_id,
                'recette_id' => $item->recette_id,
                'exam' => [
                    'id' => $item->planificationExamen->id,
                    'date' => $item->planificationExamen->date?->toDateString(),
                    'classe' => $item->planificationExamen->classe?->nom,
                    'ecole' => $item->planificationExamen->classe?->ecole?->nom,
                    'matiere' => $item->planificationExamen->matiere?->libelle,
                ],
                'poste' => [
                    'id' => $item->posteRecette->id,
                    'code' => $item->posteRecette->code,
                    'libelle' => $item->posteRecette->libelle,
                ],
                'montant_finance' => $item->montant_finance,
                'etat_financement' => $item->etat_financement,
                'date_facturation' => $item->date_facturation?->toDateString(),
                'date_limite_paiement' => $item->date_limite_paiement?->toDateString(),
                'jours_restants' => $item->getJoursRestantsAttribute(),
                'est_expirée' => $item->estExpiree(),
            ];
        });

        return Inertia::render('Academique::ExamFinance/Index', [
            'financings' => $financings,
            'filters' => [
                'etat' => $request->etat,
                'exam_id' => $request->exam_id,
                'recette_id' => $request->recette_id,
                'ecole_id' => $request->ecole_id,
            ],
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $exams = PlanificationExamen::where('etat', 'actif')
            ->with('classe.ecole', 'matiere')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'label' => "{$e->classe->nom} - {$e->matiere->libelle} ({$e->date?->format('d/m/Y')})",
            ])
            ->toArray();

        $postes = PosteRecette::where('etat', 'actif')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'label' => "{$p->code} - {$p->libelle}",
            ])
            ->toArray();

        return Inertia::render('Academique::ExamFinance/Create', [
            'exams' => $exams,
            'postes' => $postes,
        ]);
    }

    /**
     * Store new association
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:planifications_examens,id',
            'recette_id' => 'required|exists:postes_recettes,id',
            'montant_finance' => 'nullable|numeric|min:0',
            'pourcentage_couverture' => 'nullable|numeric|min:0|max:100',
            'date_facturation' => 'nullable|date',
            'date_limite_paiement' => 'nullable|date',
            'etat_financement' => 'in:en-attente,actif,clôturé',
            'notes' => 'nullable|string',
            'raison' => 'nullable|string',
        ]);

        $exam = PlanificationExamen::findOrFail($validated['exam_id']);
        $poste = PosteRecette::findOrFail($validated['recette_id']);

        try {
            $financing = $this->examFinanceService->associateExamWithPoste(
                $exam,
                $poste,
                $validated
            );

            return redirect()->route('academique.exam-finance.show', $financing->id)
                ->with('success', 'Association créée avec succès');
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    /**
     * Show single association
     */
    public function show(PlanificationExamenPosteRecette $examFinance)
    {
        $examFinance->load([
            'planificationExamen.classe.ecole',
            'planificationExamen.matiere',
            'posteRecette',
            'auditLogs.auteur',
        ]);

        return Inertia::render('Academique::ExamFinance/Show', [
            'financing' => [
                'id' => $examFinance->id,
                'exam_id' => $examFinance->exam_id,
                'recette_id' => $examFinance->recette_id,
                'exam' => [
                    'id' => $examFinance->planificationExamen->id,
                    'date' => $examFinance->planificationExamen->date?->toDateString(),
                    'classe' => $examFinance->planificationExamen->classe?->nom,
                    'ecole' => $examFinance->planificationExamen->classe?->ecole?->nom,
                    'matiere' => $examFinance->planificationExamen->matiere?->libelle,
                ],
                'poste' => [
                    'id' => $examFinance->posteRecette->id,
                    'code' => $examFinance->posteRecette->code,
                    'libelle' => $examFinance->posteRecette->libelle,
                    'compte_comptable' => $examFinance->posteRecette->compte_comptable,
                ],
                'montant_finance' => $examFinance->montant_finance,
                'pourcentage_couverture' => $examFinance->pourcentage_couverture,
                'etat_financement' => $examFinance->etat_financement,
                'date_facturation' => $examFinance->date_facturation?->toDateString(),
                'date_limite_paiement' => $examFinance->date_limite_paiement?->toDateString(),
                'notes' => $examFinance->notes,
                'jours_restants' => $examFinance->getJoursRestantsAttribute(),
                'est_expirée' => $examFinance->estExpiree(),
                'creation_username' => $examFinance->creation_username,
                'modification_username' => $examFinance->modification_username,
                'created_at' => $examFinance->created_at?->toDateTimeString(),
                'updated_at' => $examFinance->updated_at?->toDateTimeString(),
            ],
            'auditLogs' => $examFinance->auditLogs->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->getActionLabelAttribute(),
                'auteur' => $log->auteur?->username,
                'ancienne_valeur_etat' => $log->ancienne_valeur_etat,
                'nouvelle_valeur_etat' => $log->nouvelle_valeur_etat,
                'montant_precedent' => $log->montant_precedent,
                'montant_nouveau' => $log->montant_nouveau,
                'raison' => $log->raison_changement,
                'created_at' => $log->created_at?->toDateTimeString(),
            ])->toArray(),
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(PlanificationExamenPosteRecette $examFinance)
    {
        $examFinance->load('planificationExamen', 'posteRecette');

        return Inertia::render('Academique::ExamFinance/Edit', [
            'financing' => [
                'id' => $examFinance->id,
                'exam_id' => $examFinance->exam_id,
                'recette_id' => $examFinance->recette_id,
                'montant_finance' => $examFinance->montant_finance,
                'pourcentage_couverture' => $examFinance->pourcentage_couverture,
                'etat_financement' => $examFinance->etat_financement,
                'date_facturation' => $examFinance->date_facturation?->toDateString(),
                'date_limite_paiement' => $examFinance->date_limite_paiement?->toDateString(),
                'notes' => $examFinance->notes,
            ],
        ]);
    }

    /**
     * Update association
     */
    public function update(Request $request, PlanificationExamenPosteRecette $examFinance)
    {
        $validated = $request->validate([
            'montant_finance' => 'nullable|numeric|min:0',
            'pourcentage_couverture' => 'nullable|numeric|min:0|max:100',
            'date_facturation' => 'nullable|date',
            'date_limite_paiement' => 'nullable|date',
            'notes' => 'nullable|string',
            'raison' => 'nullable|string',
        ]);

        try {
            $this->examFinanceService->updateFinancing($examFinance, $validated);

            return redirect()->route('academique.exam-finance.show', $examFinance->id)
                ->with('success', 'Association mise à jour avec succès');
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    /**
     * Validate (approve) financing
     */
    public function validate(Request $request, PlanificationExamenPosteRecette $examFinance)
    {
        $validated = $request->validate([
            'raison' => 'nullable|string',
        ]);

        try {
            $this->examFinanceService->validateFinancing(
                $examFinance,
                $validated['raison'] ?? null
            );

            return redirect()->route('academique.exam-finance.show', $examFinance->id)
                ->with('success', 'Financement validé avec succès');
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    /**
     * Reject financing
     */
    public function reject(Request $request, PlanificationExamenPosteRecette $examFinance)
    {
        $validated = $request->validate([
            'raison' => 'required|string',
        ]);

        try {
            $this->examFinanceService->rejectFinancing(
                $examFinance,
                $validated['raison']
            );

            return redirect()->route('academique.exam-finance.show', $examFinance->id)
                ->with('success', 'Financement rejeté');
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    /**
     * Close financing
     */
    public function close(Request $request, PlanificationExamenPosteRecette $examFinance)
    {
        $validated = $request->validate([
            'raison' => 'nullable|string',
        ]);

        try {
            $this->examFinanceService->closeFinancing(
                $examFinance,
                $validated['raison'] ?? null
            );

            return redirect()->route('academique.exam-finance.show', $examFinance->id)
                ->with('success', 'Financement clôturé');
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    /**
     * Delete association
     */
    public function destroy(PlanificationExamenPosteRecette $examFinance)
    {
        $examFinance->delete();

        return redirect()->route('academique.exam-finance.index')
            ->with('success', 'Association supprimée');
    }

    /**
     * Get exams pending validation
     */
    public function pendingValidation()
    {
        $pending = $this->examFinanceService->getExamsPendingValidation(7);

        return Inertia::render('Academique::ExamFinance/PendingValidation', [
            'pending' => $pending,
        ]);
    }

    /**
     * Get exam financing status
     */
    public function getExamStatus(PlanificationExamen $exam)
    {
        $status = $this->examFinanceService->getExamFinancingStatus($exam);

        return response()->json($status);
    }
}
