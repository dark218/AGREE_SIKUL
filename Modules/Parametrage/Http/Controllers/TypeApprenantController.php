<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\TypeApprenant;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\Ecole;
use Illuminate\Foundation\Validation\ValidatesRequests;

class TypeApprenantController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-typeapprenant-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-typeapprenant-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-typeapprenant-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-typeapprenant-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-typeapprenant-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = TypeApprenant::with(['niveau', 'section', 'cycle', 'pays']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $typeApprenants = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::TypeApprenants/Index', [
                'typeApprenants' => $typeApprenants,
                'filters' => $request->only(['code', 'libelle']),
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $niveaux = NiveauEtude::all();
            $sections = Section::all();
            $cycles = CycleEnseignement::all();
            $pays = Pays::all()->map(function($country) {
                return ['id' => $country->id, 'libelle' => $country->libelle];
            });
            $ecoles = Ecole::actif()->orderBy('nom')->get(['id', 'nom', 'code'])->toArray();

            return Inertia::render('Parametrage::TypeApprenants/Create', [
                'niveaux' => $niveaux,
                'sections' => $sections,
                'cycles' => $cycles,
                'pays' => $pays,
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
                'code' => 'required|string|max:100|unique:type_apprenants,code',
                'libelle' => 'required|string|max:255',
                'ecole_id' => 'required|exists:ecoles,id',
                'niveau_id' => 'nullable|exists:niveaux_etudes,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'pays_id' => 'nullable|exists:pays,id',
                'age_min' => 'nullable|integer|min:0|max:100',
                'age_max' => 'nullable|integer|min:0|max:100',
                'necessite_tuteur' => 'nullable|boolean',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['necessite_tuteur'] = $validated['necessite_tuteur'] ?? false;
            $validated['created_by'] = auth()->id();
            TypeApprenant::create($validated);

            return redirect()
                ->route('parametrage.types_apprenants.index')
                ->with('success', 'Créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(TypeApprenant $typeApprenant)
    {
        try {
            $niveaux = NiveauEtude::all();
            $sections = Section::all();
            $cycles = CycleEnseignement::all();
            $pays = Pays::all()->map(function($country) {
                return ['id' => $country->id, 'libelle' => $country->libelle];
            });
            $ecoles = Ecole::actif()->orderBy('nom')->get(['id', 'nom', 'code'])->toArray();

            return Inertia::render('Parametrage::TypeApprenants/Show', [
                'typeApprenant' => $typeApprenant->load(['niveau', 'section', 'cycle', 'pays', 'ecole']),
                'niveaux' => $niveaux,
                'sections' => $sections,
                'cycles' => $cycles,
                'pays' => $pays,
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(TypeApprenant $typeApprenant)
    {
        try {
            $niveaux = NiveauEtude::all();
            $sections = Section::all();
            $cycles = CycleEnseignement::all();
            $pays = Pays::all()->map(function($country) {
                return ['id' => $country->id, 'libelle' => $country->libelle];
            });
            $ecoles = Ecole::actif()->orderBy('nom')->get(['id', 'nom', 'code'])->toArray();

            return Inertia::render('Parametrage::TypeApprenants/Edit', [
                'item' => $typeApprenant->load(['niveau', 'section', 'cycle', 'pays', 'ecole']),
                'niveaux' => $niveaux,
                'sections' => $sections,
                'cycles' => $cycles,
                'pays' => $pays,
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, TypeApprenant $typeApprenant)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:type_apprenants,code,' . $typeApprenant->id,
                'libelle' => 'required|string|max:255',
                'ecole_id' => 'required|exists:ecoles,id',
                'niveau_id' => 'nullable|exists:niveaux_etudes,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'pays_id' => 'nullable|exists:pays,id',
                'age_min' => 'nullable|integer|min:0|max:100',
                'age_max' => 'nullable|integer|min:0|max:100',
                'necessite_tuteur' => 'nullable|boolean',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $typeApprenant->etat;
            $validated['necessite_tuteur'] = $validated['necessite_tuteur'] ?? $typeApprenant->necessite_tuteur;
            $validated['updated_by'] = auth()->id();
            $typeApprenant->update($validated);

            return redirect()
                ->route('parametrage.types_apprenants.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(TypeApprenant $typeApprenant)
    {
        try {
            $typeApprenant->deleted_by = auth()->id();
            $typeApprenant->save();
            $typeApprenant->delete();

            return redirect()->route('parametrage.types_apprenants.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.types_apprenants.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(TypeApprenant $typeApprenant)
    {
        try {
            $newEtat = $typeApprenant->etat === 'actif' ? 'inactif' : 'actif';
            $typeApprenant->etat = $newEtat;
            $typeApprenant->updated_by = auth()->id();
            $typeApprenant->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.types_apprenants.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.types_apprenants.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
