<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\CategorieFourniture;

class CategorieFournitureController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:categories-fournitures-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:categories-fournitures-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:categories-fournitures-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:categories-fournitures-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = CategorieFourniture::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('libelle', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $categories = $query->paginate(10)
                ->withQueryString()
                ->through(fn ($categorie) => [
                    'id' => $categorie->id,
                    'libelle' => $categorie->libelle,
                    'code' => $categorie->code,
                    'description' => $categorie->description,
                    'nombre_fournitures' => $categorie->getNombreFournitures(),
                    'is_deleted' => $categorie->trashed(),
                    'statut' => $categorie->trashed() ? 'inactif' : 'actif',
                ]);

            return Inertia::render('RessourcesLogistique::CategoriesFournitures/Index', [
                'categories' => $categories,
                'filters' => $request->only(['search']),
            ]);
        } catch (\Throwable $th) {
            log_error("Logistique", "CategorieFournitureController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('RessourcesLogistique::CategoriesFournitures/Create', [
                'title' => __('actions.add'),
            ]);
        } catch (\Throwable $th) {
            log_error("Logistique", "CategorieFournitureController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        // §BUG-FIX : validate() jette une ValidationException qui DOIT remonter
        //   à Inertia pour peupler form.errors. Ne PAS l'attraper dans le
        //   catch générique qui affichait "Une erreur est survenue" au lieu
        //   des messages de champ précis (ex: "Le libellé existe déjà").
        $validated = $request->validate([
            'libelle'     => 'required|string|max:125|unique:categories_fournitures,libelle',
            'code'        => 'nullable|string|max:50|unique:categories_fournitures,code',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            CategorieFourniture::create($validated);
            return redirect()->route('categories-fournitures.index')
                ->with('success', __('messages.created_successfully'));
        } catch (\Throwable $th) {
            log_error("Logistique", "CategorieFournitureController::store", $th->getMessage());
            return back()->withInput()->with('error', 'Erreur: ' . $th->getMessage());
        }
    }

    public function show(CategorieFourniture $categorieFourniture)
    {
        try {
            $categorieFourniture->load('fournitures');

            return Inertia::render('RessourcesLogistique::CategoriesFournitures/Show', [
                'item' => $categorieFourniture,
            ]);
        } catch (\Throwable $th) {
            log_error("Logistique", "CategorieFournitureController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(CategorieFourniture $categorieFourniture)
    {
        try {
            return Inertia::render('RessourcesLogistique::CategoriesFournitures/Edit', [
                'item' => $categorieFourniture,
                'title' => __('actions.edit'),
            ]);
        } catch (\Throwable $th) {
            log_error("Logistique", "CategorieFournitureController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, CategorieFourniture $categorieFourniture)
    {
        $validated = $request->validate([
            'libelle'     => 'required|string|max:125|unique:categories_fournitures,libelle,' . $categorieFourniture->id,
            'code'        => 'nullable|string|max:50|unique:categories_fournitures,code,' . $categorieFourniture->id,
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $categorieFourniture->update($validated);
            return redirect()->route('categories-fournitures.show', $categorieFourniture)
                ->with('success', __('messages.updated_successfully'));
        } catch (\Throwable $th) {
            log_error("Logistique", "CategorieFournitureController::update", $th->getMessage());
            return back()->withInput()->with('error', 'Erreur: ' . $th->getMessage());
        }
    }

    public function destroy(CategorieFourniture $categorieFourniture)
    {
        try {
            $categorieFourniture->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Logistique", "CategorieFournitureController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(CategorieFourniture $categorieFourniture)
    {
        try {
            if ($categorieFourniture->trashed()) {
                $categorieFourniture->restore();
            } else {
                $categorieFourniture->delete();
            }

            return redirect()->route('categories-fournitures.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Logistique", "CategorieFournitureController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
