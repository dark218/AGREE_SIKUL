<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\JustificatifAbsence;
use Modules\Academique\Entities\AbsenceApprenant;

class JustificatifAbsenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:justificatifs_absences-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:justificatifs_absences-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:justificatifs_absences-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:justificatifs_absences-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = JustificatifAbsence::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('absence.apprenant.user', function ($q) use ($search) {
                    $q->where('nom', 'like', "%$search%")
                      ->orWhere('prenoms', 'like', "%$search%");
                })->orWhere('commentaire', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $statut = $request->input('statut');
                if ($statut === 'valide') {
                    $query->whereNotNull('valide_at');
                } else {
                    $query->whereNull('valide_at');
                }
            }

            $justificatifs = $query->with(['absence.apprenant.user', 'validePar'])
                ->paginate(10)->withQueryString();

            return Inertia::render('Academique::JustificatifsAbsences/Index', [
                'title' => __('common.justificatifs'),
                'justificatifs' => $justificatifs,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "JustificatifAbsenceController::index", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            // Load student absences
            $studentAbsences = AbsenceApprenant::with('apprenant.user', 'justifications')
                ->get(['id', 'apprenant_id'])
                ->filter(fn($absence) => $absence->apprenant !== null && $absence->apprenant->user !== null)
                ->map(function ($absence) {
                    return [
                        'id' => $absence->id,
                        'type' => 'apprenant',
                        'libelle' => 'Apprenant: ' . $absence->apprenant->user->prenoms . ' ' . $absence->apprenant->user->nom,
                        'justifications' => $absence->justifications->map(fn($j) => [
                            'id' => $j->id,
                            'document' => $j->document,
                            'motif' => $j->motif,
                            'date_justification' => $j->date_justification,
                            'statut' => $j->statut,
                        ])->toArray(),
                    ];
                })->values()->toArray();

            // Load teacher absences
            $teacherAbsences = AbsenceEnseignant::with('enseignant.user', 'matiere')
                ->get(['id', 'enseignant_id'])
                ->filter(fn($absence) => $absence->enseignant !== null && $absence->enseignant->user !== null)
                ->map(function ($absence) {
                    return [
                        'id' => $absence->id,
                        'type' => 'enseignant',
                        'libelle' => 'Enseignant: ' . $absence->enseignant->user->prenoms . ' ' . $absence->enseignant->user->nom,
                        'justifications' => [],
                    ];
                })->values()->toArray();

            // Combine both types
            $absences = array_merge($studentAbsences, $teacherAbsences);

            return Inertia::render('Academique::JustificatifsAbsences/Create', [
                'title' => __('actions.create'),
                'absences' => $absences,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "JustificatifAbsenceController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'absence_id' => 'required|integer',
                'absence_type' => 'required|in:apprenant,enseignant',
                'commentaire' => 'nullable|string|max:500',
            ]);

            // Validate absence exists based on type
            $absenceType = $validated['absence_type'];
            $absenceId = $validated['absence_id'];

            if ($absenceType === 'apprenant') {
                if (!AbsenceApprenant::find($absenceId)) {
                    return back()->withErrors(['absence_id' => 'L\'absence sélectionnée n\'existe pas']);
                }
            } elseif ($absenceType === 'enseignant') {
                if (!AbsenceEnseignant::find($absenceId)) {
                    return back()->withErrors(['absence_id' => 'L\'absence sélectionnée n\'existe pas']);
                }
            }

            JustificatifAbsence::create($validated);

            return redirect()->route('academique.justificatifs_absences.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "JustificatifAbsenceController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(JustificatifAbsence $justificatif)
    {
        try {
            $justificatif->load(['absence', 'validePar']);

            // Load student absences
            $studentAbsences = AbsenceApprenant::with('apprenant.user')
                ->get(['id', 'apprenant_id'])
                ->filter(fn($absence) => $absence->apprenant !== null && $absence->apprenant->user !== null)
                ->map(function ($absence) {
                    return [
                        'id' => $absence->id,
                        'type' => 'apprenant',
                        'libelle' => 'Apprenant: ' . $absence->apprenant->user->prenoms . ' ' . $absence->apprenant->user->nom,
                    ];
                })->values()->toArray();

            // Load teacher absences
            $teacherAbsences = AbsenceEnseignant::with('enseignant.user')
                ->get(['id', 'enseignant_id'])
                ->filter(fn($absence) => $absence->enseignant !== null && $absence->enseignant->user !== null)
                ->map(function ($absence) {
                    return [
                        'id' => $absence->id,
                        'type' => 'enseignant',
                        'libelle' => 'Enseignant: ' . $absence->enseignant->user->prenoms . ' ' . $absence->enseignant->user->nom,
                    ];
                })->values()->toArray();

            // Combine both types
            $absences = array_merge($studentAbsences, $teacherAbsences);

            return Inertia::render('Academique::JustificatifsAbsences/Show', [
                'title' => __('actions.view'),
                'justificatif' => $justificatif,
                'absences' => $absences,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "JustificatifAbsenceController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit(JustificatifAbsence $justificatif)
    {
        try {
            $justificatif->load(['absence']);

            // Load student absences
            $studentAbsences = AbsenceApprenant::with('apprenant.user')
                ->get(['id', 'apprenant_id'])
                ->filter(fn($absence) => $absence->apprenant !== null && $absence->apprenant->user !== null)
                ->map(function ($absence) {
                    return [
                        'id' => $absence->id,
                        'type' => 'apprenant',
                        'libelle' => 'Apprenant: ' . $absence->apprenant->user->prenoms . ' ' . $absence->apprenant->user->nom,
                    ];
                })->values()->toArray();

            // Load teacher absences
            $teacherAbsences = AbsenceEnseignant::with('enseignant.user')
                ->get(['id', 'enseignant_id'])
                ->filter(fn($absence) => $absence->enseignant !== null && $absence->enseignant->user !== null)
                ->map(function ($absence) {
                    return [
                        'id' => $absence->id,
                        'type' => 'enseignant',
                        'libelle' => 'Enseignant: ' . $absence->enseignant->user->prenoms . ' ' . $absence->enseignant->user->nom,
                    ];
                })->values()->toArray();

            // Combine both types
            $absences = array_merge($studentAbsences, $teacherAbsences);

            return Inertia::render('Academique::JustificatifsAbsences/Edit', [
                'title' => __('actions.edit'),
                'justificatif' => $justificatif,
                'absences' => $absences,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "JustificatifAbsenceController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, JustificatifAbsence $justificatif)
    {
        try {
            $validated = $request->validate([
                'absence_id' => 'required|integer',
                'absence_type' => 'required|in:apprenant,enseignant',
                'commentaire' => 'nullable|string|max:500',
            ]);

            // Validate absence exists based on type
            $absenceType = $validated['absence_type'];
            $absenceId = $validated['absence_id'];

            if ($absenceType === 'apprenant') {
                if (!AbsenceApprenant::find($absenceId)) {
                    return back()->withErrors(['absence_id' => 'L\'absence sélectionnée n\'existe pas']);
                }
            } elseif ($absenceType === 'enseignant') {
                if (!AbsenceEnseignant::find($absenceId)) {
                    return back()->withErrors(['absence_id' => 'L\'absence sélectionnée n\'existe pas']);
                }
            }

            $justificatif->update($validated);

            return redirect()->route('academique.justificatifs_absences.show', $justificatif)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "JustificatifAbsenceController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(JustificatifAbsence $justificatif)
    {
        try {
            $justificatif->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "JustificatifAbsenceController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(JustificatifAbsence $justificatif)
    {
        try {
            if ($justificatif->trashed()) {
                $justificatif->restore();
            } else {
                $justificatif->delete();
            }

            return redirect()->route('academique.justificatifs_absences.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "JustificatifAbsenceController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
