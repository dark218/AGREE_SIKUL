<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\CategorieEnseignant;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Pays;
use Illuminate\Foundation\Validation\ValidatesRequests;

class CategorieEnseignantController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-categorieenseignant-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-categorieenseignant-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-categorieenseignant-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-categorieenseignant-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-categorieenseignant-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = CategorieEnseignant::query();

            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('libelle', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $categorieEnseignants = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::CategoriesEnseignant/Index', [
                'categories_enseignants' => $categorieEnseignants,
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
            $ecoles = Ecole::select('id', 'nom')->orderBy('nom')->get()->toArray();
            $pays = Pays::select('id', 'libelle')->orderBy('libelle')->get()->toArray();
            return Inertia::render('Parametrage::CategoriesEnseignant/Create', [
                'ecoles' => $ecoles,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('🔍 CategorieEnseignant store() called');
            \Log::info('Request data: ' . json_encode($request->all()));

            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:categorie_enseignants,code',
                'libelle' => 'required|string|max:255',
                'pays_id' => 'nullable|exists:pays,id',
                'niveau_qualification' => 'nullable|string|max:100',
                'charge_horaire_min' => 'nullable|integer|min:0',
                'charge_horaire_max' => 'nullable|integer|min:0',
                'taux_horaire_base' => 'nullable|numeric|min:0',
                'peut_etre_titulaire' => 'nullable|boolean',
                'anciennete_requise' => 'nullable|integer|min:0',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            \Log::info('✅ Validation passed');
            \Log::info('Validated data: ' . json_encode($validated));

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['peut_etre_titulaire'] = $validated['peut_etre_titulaire'] ?? false;
            $validated['created_by'] = auth()->id() ?? 1;

            \Log::info('Final data before create: ' . json_encode($validated));

            $created = CategorieEnseignant::create($validated);

            \Log::info('✅ Record created with ID: ' . $created->id);

            return redirect()
                ->route('parametrage.categories_enseignant.index')
                ->with('success', 'Créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ Validation error: ' . json_encode($e->errors()));
            throw $e;
        } catch (\Exception $e) {
            \Log::error('❌ Exception in store(): ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    public function show(CategorieEnseignant $categorieEnseignant)
    {
        try {
            $ecoles = Ecole::select('id', 'nom')->orderBy('nom')->get()->toArray();
            $pays = Pays::select('id', 'libelle')->orderBy('libelle')->get()->toArray();
            return Inertia::render('Parametrage::CategoriesEnseignant/Show', [
                'categorieEnseignant' => $categorieEnseignant->load(['ecole']),
                'ecoles' => $ecoles,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(CategorieEnseignant $categorieEnseignant)
    {
        try {
            $ecoles = Ecole::select('id', 'nom')->orderBy('nom')->get()->toArray();
            $pays = Pays::select('id', 'libelle')->orderBy('libelle')->get()->toArray();
            return Inertia::render('Parametrage::CategoriesEnseignant/Edit', [
                'item' => $categorieEnseignant->load(['ecole']),
                'ecoles' => $ecoles,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, CategorieEnseignant $categorieEnseignant)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:categorie_enseignants,code,' . $categorieEnseignant->id,
                'libelle' => 'required|string|max:255',
                'pays_id' => 'nullable|exists:pays,id',
                'niveau_qualification' => 'nullable|string|max:100',
                'charge_horaire_min' => 'nullable|integer|min:0',
                'charge_horaire_max' => 'nullable|integer|min:0',
                'taux_horaire_base' => 'nullable|numeric|min:0',
                'peut_etre_titulaire' => 'nullable|boolean',
                'anciennete_requise' => 'nullable|integer|min:0',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $categorieEnseignant->etat;
            $validated['peut_etre_titulaire'] = $validated['peut_etre_titulaire'] ?? false;
            $validated['updated_by'] = auth()->id();
            $categorieEnseignant->update($validated);

            return redirect()
                ->route('parametrage.categories_enseignant.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(CategorieEnseignant $categorieEnseignant)
    {
        try {
            $categorieEnseignant->deleted_by = auth()->id();
            $categorieEnseignant->save();
            $categorieEnseignant->delete();

            return redirect()->route('parametrage.categories_enseignant.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.categories_enseignant.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(CategorieEnseignant $categorieEnseignant)
    {
        try {
            $newEtat = $categorieEnseignant->etat === 'actif' ? 'inactif' : 'actif';
            $categorieEnseignant->etat = $newEtat;
            $categorieEnseignant->updated_by = auth()->id();
            $categorieEnseignant->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.categories_enseignant.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.categories_enseignant.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
