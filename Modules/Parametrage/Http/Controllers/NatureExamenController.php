<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\NatureExamen;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\CycleEnseignement;
use Illuminate\Foundation\Validation\ValidatesRequests;

class NatureExamenController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-natureexamen-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-natureexamen-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-natureexamen-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-natureexamen-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-natureexamen-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = NatureExamen::query()->with(['section', 'niveau', 'cycle', 'pays']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            $natureExamens = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::NatureExamens/Index', [
                'natureExamens' => $natureExamens,
                'filters' => $request->only(['code', 'libelle']),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du chargement: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $pays = Pays::all()->map(fn($p) => ['id' => $p->id, 'libelle' => $p->libelle])->values();
            $sections = Section::all()->map(fn($s) => ['id' => $s->id, 'libelle' => $s->libelle])->values();
            $niveaux = NiveauEtude::all()->map(fn($n) => ['id' => $n->id, 'libelle' => $n->libelle])->values();
            $ecoles = Ecole::all()->map(fn($e) => ['id' => $e->id, 'nom' => $e->nom])->values();
            $cycles = CycleEnseignement::all()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->values();

            return Inertia::render('Parametrage::NatureExamens/Create', [
                'pays' => $pays,
                'sections' => $sections,
                'niveaux' => $niveaux,
                'ecoles' => $ecoles,
                'cycles' => $cycles,
            ]);
        } catch (\Exception $e) {
            \Log::error('NatureExamen create error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:natures_examens,code',
                'libelle' => 'required|string|max:255',
                'section_id' => 'required|exists:sections,id',
                'niveau_id' => 'required|exists:niveaux_etudes,id',
                'cycle_id' => 'required|exists:cycles_enseignement,id',
                'poids' => 'nullable|numeric|min:0|max:100',
                'pays_id' => 'nullable|exists:pays,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'note_eliminatoire' => 'nullable|numeric|min:0|max:20',
                'duree_minutes' => 'nullable|integer|min:0',
                'est_eliminatoire' => 'nullable|boolean',
                'est_rattrapage' => 'nullable|boolean',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();

            NatureExamen::create($validated);

            return redirect()
                ->route('parametrage.natures_examens.index')
                ->with('success', 'Créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    public function show(NatureExamen $natureExamen)
    {
        try {
            $pays = Pays::all()->map(fn($p) => ['id' => $p->id, 'libelle' => $p->libelle])->values();
            $sections = Section::all()->map(fn($s) => ['id' => $s->id, 'libelle' => $s->libelle])->values();
            $niveaux = NiveauEtude::all()->map(fn($n) => ['id' => $n->id, 'libelle' => $n->libelle])->values();
            $ecoles = Ecole::all()->map(fn($e) => ['id' => $e->id, 'nom' => $e->nom])->values();
            $cycles = CycleEnseignement::all()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->values();

            return Inertia::render('Parametrage::NatureExamens/Show', [
                'item' => $natureExamen,
                'pays' => $pays,
                'sections' => $sections,
                'niveaux' => $niveaux,
                'ecoles' => $ecoles,
                'cycles' => $cycles,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(NatureExamen $natureExamen)
    {
        try {
            $pays = Pays::all()->map(fn($p) => ['id' => $p->id, 'libelle' => $p->libelle])->values();
            $sections = Section::all()->map(fn($s) => ['id' => $s->id, 'libelle' => $s->libelle])->values();
            $niveaux = NiveauEtude::all()->map(fn($n) => ['id' => $n->id, 'libelle' => $n->libelle])->values();
            $ecoles = Ecole::all()->map(fn($e) => ['id' => $e->id, 'nom' => $e->nom])->values();
            $cycles = CycleEnseignement::all()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->values();

            return Inertia::render('Parametrage::NatureExamens/Edit', [
                'item' => $natureExamen,
                'pays' => $pays,
                'sections' => $sections,
                'niveaux' => $niveaux,
                'ecoles' => $ecoles,
                'cycles' => $cycles,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, NatureExamen $natureExamen)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:natures_examens,code,' . $natureExamen->id,
                'libelle' => 'required|string|max:255',
                'section_id' => 'required|exists:sections,id',
                'niveau_id' => 'required|exists:niveaux_etudes,id',
                'cycle_id' => 'required|exists:cycles_enseignement,id',
                'poids' => 'nullable|numeric|min:0|max:100',
                'pays_id' => 'nullable|exists:pays,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'note_eliminatoire' => 'nullable|numeric|min:0|max:20',
                'duree_minutes' => 'nullable|integer|min:0',
                'est_eliminatoire' => 'nullable|boolean',
                'est_rattrapage' => 'nullable|boolean',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $natureExamen->etat;
            $validated['updated_by'] = auth()->id();
            $natureExamen->update($validated);

            return redirect()
                ->route('parametrage.natures_examens.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(NatureExamen $natureExamen)
    {
        try {
            $natureExamen->deleted_by = auth()->id();
            $natureExamen->save();
            $natureExamen->delete();

            return redirect()->route('parametrage.natures_examens.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            return redirect()->route('parametrage.natures_examens.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(NatureExamen $natureExamen)
    {
        try {
            $newEtat = $natureExamen->etat === 'actif' ? 'inactif' : 'actif';
            $natureExamen->etat = $newEtat;
            $natureExamen->updated_by = auth()->id();
            $natureExamen->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.natures_examens.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            return redirect()->route('parametrage.natures_examens.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
