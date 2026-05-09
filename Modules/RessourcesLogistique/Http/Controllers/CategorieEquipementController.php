<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\CategorieEquipement;

class CategorieEquipementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:categories-equipements-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:categories-equipements-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:categories-equipements-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:categories-equipements-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = CategorieEquipement::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('libelle', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $categories = $query->paginate(10)->withQueryString()
                ->through(fn ($categorie) => [
                    'id' => $categorie->id,
                    'code' => $categorie->code,
                    'libelle' => $categorie->libelle,
                    'statut' => $categorie->statut,
                ]);

            return Inertia::render('RessourcesLogistique::CategoriesEquipements/Index', [
                'categories' => $categories,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "CategorieEquipementController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('RessourcesLogistique::CategoriesEquipements/Create');
        } catch (\Throwable $th) {
            log_error("Inventaire", "CategorieEquipementController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:categories_equipements',
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'statut' => 'required|in:actif,inactif',
            ]);

            CategorieEquipement::create($validated);

            return redirect()->route('categories-equipements.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "CategorieEquipementController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(CategorieEquipement $categorieEquipement)
    {
        try {
            $categorieEquipement->load('equipements');

            return Inertia::render('RessourcesLogistique::CategoriesEquipements/Show', [
                'categorie' => $categorieEquipement,
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "CategorieEquipementController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(CategorieEquipement $categorieEquipement)
    {
        try {
            return Inertia::render('RessourcesLogistique::CategoriesEquipements/Edit', [
                'categorie' => $categorieEquipement,
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "CategorieEquipementController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, CategorieEquipement $categorieEquipement)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:categories_equipements,code,' . $categorieEquipement->id,
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'statut' => 'required|in:actif,inactif',
            ]);

            $categorieEquipement->update($validated);

            return redirect()->route('categories-equipements.show', $categorieEquipement)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "CategorieEquipementController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(CategorieEquipement $categorieEquipement)
    {
        try {
            $categorieEquipement->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "CategorieEquipementController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(CategorieEquipement $categorieEquipement)
    {
        try {
            if ($categorieEquipement->trashed()) {
                $categorieEquipement->restore();
            } else {
                $categorieEquipement->delete();
            }

            return redirect()->route('categories-equipements.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "CategorieEquipementController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
