<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\Ecole;
use Illuminate\Foundation\Validation\ValidatesRequests;

class MatiereUniteController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-matiereunite-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-matiereunite-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-matiereunite-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-matiereunite-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-matiereunite-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = MatiereUnite::with(['niveau', 'section', 'cycle', 'ecole']);

            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('libelle', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $matiereUnites = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::MatiereUnites/Index', [
                'matiereUnites' => $matiereUnites,
                'filters' => $request->only(['search', 'etat']),
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $niveaux = NiveauEtude::all()->map(fn($n) => ['id' => $n->id, 'libelle' => $n->libelle])->values();
            $sections = Section::all()->map(fn($s) => ['id' => $s->id, 'libelle' => $s->libelle])->values();
            $cycles = CycleEnseignement::all()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->values();
            $ecoles = Ecole::actif()->orderBy('nom')->get(['id', 'nom', 'code'])->toArray();

            return Inertia::render('Parametrage::MatiereUnites/Create', [
                'niveaux' => $niveaux,
                'sections' => $sections,
                'cycles' => $cycles,
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:matieres_unites,code',
                'libelle' => 'required|string|max:255',
                'ecole_id' => 'required|exists:ecoles,id',
                'niveau_id' => 'nullable|exists:niveaux_etudes,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'note_max' => 'nullable|numeric|min:0|max:100',
                'coefficient' => 'nullable|numeric|min:0|max:10',
                'type_matiere' => 'nullable|in:theorique,pratique,tp,td,projet',
                'volume_horaire' => 'nullable|integer|min:0',
                'est_obligatoire' => 'nullable|boolean',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['est_obligatoire'] = $validated['est_obligatoire'] ?? false;
            $validated['created_by'] = auth()->id();

            MatiereUnite::create($validated);

            return redirect()
                ->route('parametrage.matiere_unites.index')
                ->with('success', 'Créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors will be automatically returned by Laravel
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('MatiereUnite Store Error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => auth()->id(),
                'request_data' => $request->except(['password', 'password_confirmation']),
            ]);
            return back()->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    public function show(MatiereUnite $matiereUnite)
    {
        try {
            $niveaux = NiveauEtude::all()->map(fn($n) => ['id' => $n->id, 'libelle' => $n->libelle])->values();
            $sections = Section::all()->map(fn($s) => ['id' => $s->id, 'libelle' => $s->libelle])->values();
            $cycles = CycleEnseignement::all()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->values();
            $ecoles = Ecole::actif()->orderBy('nom')->get(['id', 'nom', 'code'])->toArray();

            return Inertia::render('Parametrage::MatiereUnites/Show', [
                'matiereUnite' => $matiereUnite->load(['niveau', 'section', 'cycle', 'ecole']),
                'niveaux' => $niveaux,
                'sections' => $sections,
                'cycles' => $cycles,
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(MatiereUnite $matiereUnite)
    {
        try {
            $niveaux = NiveauEtude::all()->map(fn($n) => ['id' => $n->id, 'libelle' => $n->libelle])->values();
            $sections = Section::all()->map(fn($s) => ['id' => $s->id, 'libelle' => $s->libelle])->values();
            $cycles = CycleEnseignement::all()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->values();
            $ecoles = Ecole::actif()->orderBy('nom')->get(['id', 'nom', 'code'])->toArray();

            return Inertia::render('Parametrage::MatiereUnites/Edit', [
                'item' => $matiereUnite->load(['niveau', 'section', 'cycle', 'ecole']),
                'niveaux' => $niveaux,
                'sections' => $sections,
                'cycles' => $cycles,
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, MatiereUnite $matiereUnite)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:matieres_unites,code,' . $matiereUnite->id,
                'libelle' => 'required|string|max:255',
                'ecole_id' => 'required|exists:ecoles,id',
                'niveau_id' => 'nullable|exists:niveaux_etudes,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'note_max' => 'nullable|numeric|min:0|max:100',
                'coefficient' => 'nullable|numeric|min:0|max:10',
                'type_matiere' => 'nullable|in:theorique,pratique,tp,td,projet',
                'volume_horaire' => 'nullable|integer|min:0',
                'est_obligatoire' => 'nullable|boolean',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $matiereUnite->etat;
            $validated['est_obligatoire'] = $validated['est_obligatoire'] ?? $matiereUnite->est_obligatoire;
            $validated['updated_by'] = auth()->id();
            $matiereUnite->update($validated);

            return redirect()
                ->route('parametrage.matiere_unites.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(MatiereUnite $matiereUnite)
    {
        try {
            $matiereUnite->deleted_by = auth()->id();
            $matiereUnite->save();
            $matiereUnite->delete();

            return redirect()->route('parametrage.matiere_unites.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.matiere_unites.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(MatiereUnite $matiereUnite)
    {
        try {
            $newEtat = $matiereUnite->etat === 'actif' ? 'inactif' : 'actif';
            $matiereUnite->etat = $newEtat;
            $matiereUnite->updated_by = auth()->id();
            $matiereUnite->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.matiere_unites.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.matiere_unites.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
