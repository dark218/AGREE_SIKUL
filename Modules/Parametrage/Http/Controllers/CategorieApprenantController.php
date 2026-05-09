<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\CategorieApprenant;
use Modules\Parametrage\Entities\Ecole;
use Illuminate\Foundation\Validation\ValidatesRequests;

class CategorieApprenantController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-categorieapprenant-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-categorieapprenant-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-categorieapprenant-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-categorieapprenant-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-categorieapprenant-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = CategorieApprenant::query()->with(['ecole']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $categorieApprenants = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::CategoriesApprenant/Index', [
                'categorieApprenants' => $categorieApprenants,
                'filters' => $request->only(['code', 'libelle']),
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        \Log::info('CategorieApprenantController@create: REQUEST RECEIVED');
        try {
            \Log::info('CategorieApprenantController@create: Loading écoles');

            $ecoles = Ecole::actif()->orderBy('nom')->get(['id', 'nom']);
            \Log::info('CategorieApprenantController@create: Found ' . count($ecoles) . ' écoles');

            $ecolesFormatted = $ecoles->map(function($ecole) {
                return ['id' => $ecole->id, 'libelle' => $ecole->nom];
            });

            \Log::info('CategorieApprenantController@create: About to render Inertia component');
            return Inertia::render('Parametrage::CategoriesApprenant/Create', [
                'title' => 'Créer une catégorie apprenant',
                'ecoles' => $ecolesFormatted,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in CategorieApprenantController@create: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:categorie_apprenants,code',
                'libelle' => 'required|string|max:255',
                'ecole_id' => 'required|exists:ecoles,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();

            CategorieApprenant::create($validated);

            return redirect()
                ->route('parametrage.categories_apprenant.index')
                ->with('success', 'Créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    public function show(CategorieApprenant $categorieApprenant)
    {
        try {
            $ecoles = Ecole::actif()->orderBy('nom')->get(['id', 'nom'])->map(function($ecole) {
                return ['id' => $ecole->id, 'libelle' => $ecole->nom];
            });

            return Inertia::render('Parametrage::CategoriesApprenant/Show', [
                'title' => 'Détails Catégorie Apprenant',
                'item' => $categorieApprenant->load(['ecole']),
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in CategorieApprenantController@show: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement: ' . $e->getMessage());
        }
    }

    public function edit(CategorieApprenant $categorieApprenant)
    {
        try {
            $ecoles = Ecole::actif()->orderBy('nom')->get(['id', 'nom'])->map(function($ecole) {
                return ['id' => $ecole->id, 'libelle' => $ecole->nom];
            });

            return Inertia::render('Parametrage::CategoriesApprenant/Edit', [
                'title' => 'Modifier Catégorie Apprenant',
                'item' => $categorieApprenant->load(['ecole']),
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in CategorieApprenantController@edit: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    public function update(Request $request, CategorieApprenant $categorieApprenant)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:categorie_apprenants,code,' . $categorieApprenant->id,
                'libelle' => 'required|string|max:255',
                'ecole_id' => 'required|exists:ecoles,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $categorieApprenant->etat;
            $validated['updated_by'] = auth()->id();
            $categorieApprenant->update($validated);

            return redirect()
                ->route('parametrage.categories_apprenant.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(CategorieApprenant $categorieApprenant)
    {
        try {
            $categorieApprenant->deleted_by = auth()->id();
            $categorieApprenant->save();
            $categorieApprenant->delete();

            return redirect()->route('parametrage.categories_apprenant.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.categories_apprenant.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(CategorieApprenant $categorieApprenant)
    {
        try {
            $newEtat = $categorieApprenant->etat === 'actif' ? 'inactif' : 'actif';
            $categorieApprenant->etat = $newEtat;
            $categorieApprenant->updated_by = auth()->id();
            $categorieApprenant->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.categories_apprenant.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.categories_apprenant.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
