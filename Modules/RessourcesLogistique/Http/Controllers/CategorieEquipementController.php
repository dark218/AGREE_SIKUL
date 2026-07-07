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
                $query->where('libelle', 'like', '%' . $request->input('search') . '%');
            }

            $categories = $query->paginate(10)->withQueryString()
                ->through(fn ($categorie) => [
                    'id'          => $categorie->id,
                    'libelle'     => $categorie->libelle,
                    'description' => $categorie->description,
                ]);

            return Inertia::render('RessourcesLogistique::CategoriesEquipements/Index', [
                'categories' => $categories,
                'filters'    => $request->only(['search']),
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
                'libelle'     => 'required|string|max:255|unique:categories_equipements,libelle',
                'description' => 'nullable|string',
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
                'libelle'     => 'required|string|max:255|unique:categories_equipements,libelle,' . $categorieEquipement->id,
                'description' => 'nullable|string',
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
