<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Evaluation;
use Modules\Parametrage\Entities\Classe;
use Modules\Academique\Entities\Matiere;

class EvaluationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:evaluations-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:evaluations-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:evaluations-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:evaluations-delete', ['only' => ['destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        try {
            \Log::info('🔵 EvaluationController::index - START', [
                'filters' => $request->only(['titre', 'statut']),
            ]);

            $query = Evaluation::query();

            if ($request->filled('titre')) {
                $search = $request->input('titre');
                $query->where('titre', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $evaluations = $query->with(['matiere', 'classe'])->paginate(10)->withQueryString()
                ->through(fn($evaluation) => [
                    'id' => $evaluation->id,
                    'code' => $evaluation->code,
                    'titre' => $evaluation->titre,
                    'type' => $evaluation->type,
                    'date' => $evaluation->date?->toDateString(),
                    'coefficient' => $evaluation->coefficient,
                    'sur' => $evaluation->sur,
                    'statut' => $evaluation->statut,
                    'matiere' => $evaluation->matiere ? [
                        'id' => $evaluation->matiere->id,
                        'libelle' => $evaluation->matiere->libelle,
                    ] : null,
                    'classe' => $evaluation->classe ? [
                        'id' => $evaluation->classe->id,
                        'nom' => $evaluation->classe->nom,
                    ] : null,
                ]);

            \Log::info('✅ EvaluationController::index - SUCCESS', [
                'count' => $evaluations->count(),
                'total' => $evaluations->total(),
            ]);

            return Inertia::render('Academique::Evaluations/Index', [
                'title' => __('common.evaluations'),
                'evaluations' => $evaluations,
                'filters' => $request->only(['titre', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "EvaluationController::index", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            $classes = Classe::select('id', 'nom')->get()->map(fn($c) => ['id' => $c->id, 'nom' => $c->nom])->toArray();
            $matieres = Matiere::select('id', 'libelle')->get()->map(fn($m) => ['id' => $m->id, 'libelle' => $m->libelle])->toArray();

            return Inertia::render('Academique::Evaluations/Create', [
                'title' => __('actions.create'),
                'classes' => $classes,
                'matieres' => $matieres,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "EvaluationController::create", $th->getMessage());
            return back()->withErrors(['_error' => 'Error: ' . $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'nullable|string|max:50',
                'titre' => 'required|string|max:255',
                'type' => 'nullable|string|max:50',
                'classe_id' => 'required|exists:classes,id',
                'matiere_id' => 'required|exists:matieres,id',
                'date' => 'nullable|date',
                'coefficient' => 'nullable|numeric|min:0',
                'sur' => 'nullable|numeric|min:0',
                'statut' => 'required|in:actif,inactif',
            ]);

            Evaluation::create($validated);

            return redirect()->route('academique.evaluations.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "EvaluationController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(Evaluation $evaluation)
    {
        try {
            $evaluation->load('matiere', 'classe');
            $classes = Classe::select('id', 'nom')->get()->map(fn($c) => ['id' => $c->id, 'nom' => $c->nom])->toArray();
            $matieres = Matiere::select('id', 'libelle')->get()->map(fn($m) => ['id' => $m->id, 'libelle' => $m->libelle])->toArray();

            return Inertia::render('Academique::Evaluations/Show', [
                'title' => __('actions.view'),
                'evaluation' => $evaluation,
                'classes' => $classes,
                'matieres' => $matieres,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "EvaluationController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit(Evaluation $evaluation)
    {
        try {
            $evaluation->load('matiere', 'classe');
            $classes = Classe::select('id', 'nom')->get()->map(fn($c) => ['id' => $c->id, 'nom' => $c->nom])->toArray();
            $matieres = Matiere::select('id', 'libelle')->get()->map(fn($m) => ['id' => $m->id, 'libelle' => $m->libelle])->toArray();

            return Inertia::render('Academique::Evaluations/Edit', [
                'title' => __('actions.edit'),
                'evaluation' => $evaluation,
                'classes' => $classes,
                'matieres' => $matieres,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "EvaluationController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, Evaluation $evaluation)
    {
        try {
            $validated = $request->validate([
                'code' => 'nullable|string|max:50',
                'titre' => 'required|string|max:255',
                'type' => 'nullable|string|max:50',
                'classe_id' => 'required|exists:classes,id',
                'matiere_id' => 'required|exists:matieres,id',
                'date' => 'nullable|date',
                'coefficient' => 'nullable|numeric|min:0',
                'sur' => 'nullable|numeric|min:0',
                'statut' => 'required|in:actif,inactif',
            ]);

            $evaluation->update($validated);

            return redirect()->route('academique.evaluations.show', $evaluation)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "EvaluationController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(Evaluation $evaluation)
    {
        try {
            $evaluation->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "EvaluationController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(Evaluation $evaluation)
    {
        try {
            if ($evaluation->trashed()) {
                $evaluation->restore();
            } else {
                $evaluation->delete();
            }

            return redirect()->route('academique.evaluations.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "EvaluationController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    /**
     * API endpoint to get evaluation data for auto-fill
     */
    public function apiShow($id)
    {
        try {
            $evaluation = Evaluation::findOrFail($id);
            return response()->json([
                'id' => $evaluation->id,
                'titre' => $evaluation->titre,
                'date' => $evaluation->date ? $evaluation->date->format('Y-m-d') : null,
            ]);
        } catch (\Exception $e) {
            \Log::error('EvaluationController@apiShow: ' . $e->getMessage());
            return response()->json(['error' => 'Évaluation non trouvée'], 404);
        }
    }
}
