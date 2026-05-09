<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Matiere;

class MatiereController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:matieres-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:matieres-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:matieres-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:matieres-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Matiere::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('libelle', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $statut = $request->input('statut');
                if ($statut === 'actif') {
                    $query->where('statut', 'actif')->whereNull('deleted_at');
                } else {
                    $query->where('statut', 'inactif')->orWhereNotNull('deleted_at');
                }
            }

            $matieres = $query->with('ecole')->paginate(10)->withQueryString()->through(fn($matiere) => [
                'id' => $matiere->id,
                'code' => $matiere->code,
                'libelle' => $matiere->libelle,
                'description' => $matiere->description,
                'coefficient' => $matiere->coefficient,
                'note_max' => $matiere->note_max,
                'niveau_id' => $matiere->niveau_id,
                'section_id' => $matiere->section_id,
                'cycle_id' => $matiere->cycle_id,
                'annee_scolaire_id' => $matiere->annee_scolaire_id,
                'statut' => $matiere->statut,
                'ecole' => [
                    'id' => $matiere->ecole?->id,
                    'nom' => $matiere->ecole?->nom,
                ],
            ]);

            return Inertia::render('Academique::Matieres/Index', [
                'title' => __('common.matieres'),
                'matieres' => $matieres,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "MatiereController::index", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Academique::Matieres/Create', [
                'title' => __('actions.create'),
                'niveaux' => \Modules\Parametrage\Entities\Niveau::select('id', 'libelle')->get()->toArray(),
                'sections' => \Modules\Parametrage\Entities\Section::select('id', 'libelle')->get()->toArray(),
                'cycles' => \Modules\Parametrage\Entities\CycleEnseignement::select('id', 'libelle')->get()->toArray(),
                'anneesScolaires' => \Modules\Parametrage\Entities\AnneeScolaire::select('id', 'libelle')->get()->toArray(),
                'ecoles' => \Modules\Parametrage\Entities\Ecole::select('id', 'nom')->get()->map(fn($e) => ['id' => $e->id, 'libelle' => $e->nom])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "MatiereController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('🔍 [DEBUG] MatiereController::store - Incoming request:', [
                'all_data' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:matieres',
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'coefficient' => 'required|numeric|min:0',
                'note_max' => 'nullable|numeric|min:0',
                'niveau_id' => 'nullable|exists:niveaux,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'statut' => 'required|in:actif,inactif',
            ]);

            \Log::info('✅ [DEBUG] MatiereController::store - Validation passed:', [
                'validated_data' => $validated,
            ]);

            $matiere = Matiere::create($validated);

            \Log::info('✅ [DEBUG] MatiereController::store - Matiere created successfully:', [
                'matiere_id' => $matiere->id,
                'matiere_data' => $matiere->toArray(),
            ]);

            return redirect()->route('academique.matieres.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            \Log::warning('❌ [DEBUG] MatiereController::store - Validation failed:', [
                'errors' => $ve->errors(),
            ]);
            throw $ve;
        } catch (\Throwable $th) {
            \Log::error('❌ [DEBUG] MatiereController::store - Exception:', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
            log_error("Academique", "MatiereController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(Matiere $matiere)
    {
        try {
            $matiere->load('ecole', 'niveau', 'section', 'cycle', 'anneeScolaire');

            return Inertia::render('Academique::Matieres/Show', [
                'title' => __('actions.view'),
                'matiere' => $matiere,
                'niveaux' => \Modules\Parametrage\Entities\Niveau::select('id', 'libelle')->get()->toArray(),
                'sections' => \Modules\Parametrage\Entities\Section::select('id', 'libelle')->get()->toArray(),
                'cycles' => \Modules\Parametrage\Entities\CycleEnseignement::select('id', 'libelle')->get()->toArray(),
                'anneesScolaires' => \Modules\Parametrage\Entities\AnneeScolaire::select('id', 'libelle')->get()->toArray(),
                'ecoles' => \Modules\Parametrage\Entities\Ecole::select('id', 'nom')->get()->map(fn($e) => ['id' => $e->id, 'libelle' => $e->nom])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "MatiereController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit(Matiere $matiere)
    {
        try {
            $matiere->load('ecole', 'niveau', 'section', 'cycle', 'anneeScolaire');

            return Inertia::render('Academique::Matieres/Edit', [
                'title' => __('actions.edit'),
                'matiere' => $matiere,
                'niveaux' => \Modules\Parametrage\Entities\Niveau::select('id', 'libelle')->get()->toArray(),
                'sections' => \Modules\Parametrage\Entities\Section::select('id', 'libelle')->get()->toArray(),
                'cycles' => \Modules\Parametrage\Entities\CycleEnseignement::select('id', 'libelle')->get()->toArray(),
                'anneesScolaires' => \Modules\Parametrage\Entities\AnneeScolaire::select('id', 'libelle')->get()->toArray(),
                'ecoles' => \Modules\Parametrage\Entities\Ecole::select('id', 'nom')->get()->map(fn($e) => ['id' => $e->id, 'libelle' => $e->nom])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "MatiereController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, Matiere $matiere)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:matieres,code,' . $matiere->id,
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'coefficient' => 'required|numeric|min:0',
                'note_max' => 'nullable|numeric|min:0',
                'niveau_id' => 'nullable|exists:niveaux,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'statut' => 'required|in:actif,inactif',
            ]);

            $matiere->update($validated);

            return redirect()->route('academique.matieres.show', $matiere)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "MatiereController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(Matiere $matiere)
    {
        try {
            $matiere->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "MatiereController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(Matiere $matiere)
    {
        try {
            if ($matiere->trashed()) {
                $matiere->restore();
            } else {
                $matiere->delete();
            }

            return redirect()->route('academique.matieres.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "MatiereController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    /**
     * API endpoint to get matière data for auto-fill
     */
    public function apiShow($id)
    {
        try {
            $matiere = Matiere::findOrFail($id);
            return response()->json([
                'id' => $matiere->id,
                'libelle' => $matiere->libelle,
                'coefficient' => $matiere->coefficient,
            ]);
        } catch (\Exception $e) {
            \Log::error('MatiereController@apiShow: ' . $e->getMessage());
            return response()->json(['error' => 'Matière non trouvée'], 404);
        }
    }
}
