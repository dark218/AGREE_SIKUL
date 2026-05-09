<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\Equipement;
use Modules\RessourcesLogistique\Entities\CategorieEquipement;

class EquipementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:equipements-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:equipements-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:equipements-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:equipements-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Equipement::query();

            if ($request->filled('categorie_id')) {
                $query->where('categorie_id', $request->input('categorie_id'));
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $equipements = $query->with('categorie', 'ecole')
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($equipement) => [
                    'id' => $equipement->id,
                    'nom' => $equipement->nom,
                    'reference' => $equipement->reference,
                    'etat' => $equipement->etat,
                    'localisation' => $equipement->localisation,
                    'prix_cents' => $equipement->prix_cents,
                    'date_acquisition' => $equipement->date_acquisition?->toDateString(),
                    'categorie' => $equipement->categorie?->libelle,
                    'ecole' => $equipement->ecole?->nom,
                ]);

            $categories = CategorieEquipement::all(['id', 'libelle'])->toArray();

            return Inertia::render('RessourcesLogistique::Equipements/Index', [
                'equipements' => $equipements,
                'categories' => $categories,
                'filters' => $request->only(['categorie_id', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "EquipementController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            $categories = CategorieEquipement::all(['id', 'libelle'])->map(fn($c) => ['id' => $c->id, 'nom' => $c->libelle])->toArray();
            $ecoles = \Modules\Parametrage\Entities\Ecole::all(['id', 'nom'])->toArray();

            return Inertia::render('RessourcesLogistique::Equipements/Create', [
                'categories' => $categories,
                'ecoles' => $ecoles,
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "EquipementController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'categorie_id' => 'required|exists:categories_equipements,id',
                'ecole_id' => 'required|exists:ecoles,id',
                'nom' => 'required|string|max:125',
                'reference' => 'nullable|string|max:125|unique:equipements',
                'etat' => 'required|in:excellent,bon,moyen,mauvais,non_fonctionnel',
                'localisation' => 'nullable|string|max:125',
                'date_acquisition' => 'nullable|date',
                'prix_cents' => 'nullable|integer|min:0',
            ]);

            Equipement::create($validated);

            return redirect()->route('equipements.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "EquipementController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Equipement $equipement)
    {
        try {
            $equipement->load('categorie', 'ecole', 'maintenances');
            $categories = CategorieEquipement::all(['id', 'libelle'])->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->toArray();
            $ecoles = \Modules\Parametrage\Entities\Ecole::all(['id', 'nom'])->toArray();

            return Inertia::render('RessourcesLogistique::Equipements/Show', [
                'item' => $equipement,
                'categories' => $categories,
                'ecoles' => $ecoles,
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "EquipementController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Equipement $equipement)
    {
        try {
            $categories = CategorieEquipement::all(['id', 'libelle'])->map(fn($c) => ['id' => $c->id, 'nom' => $c->libelle])->toArray();
            $ecoles = \Modules\Parametrage\Entities\Ecole::all(['id', 'nom'])->toArray();

            return Inertia::render('RessourcesLogistique::Equipements/Edit', [
                'item' => $equipement->load('categorie', 'ecole'),
                'categories' => $categories,
                'ecoles' => $ecoles,
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "EquipementController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Equipement $equipement)
    {
        try {
            $validated = $request->validate([
                'categorie_id' => 'required|exists:categories_equipements,id',
                'ecole_id' => 'required|exists:ecoles,id',
                'nom' => 'required|string|max:125',
                'reference' => 'nullable|string|max:125|unique:equipements,reference,' . $equipement->id,
                'etat' => 'required|in:excellent,bon,moyen,mauvais,non_fonctionnel',
                'localisation' => 'nullable|string|max:125',
                'date_acquisition' => 'nullable|date',
                'prix_cents' => 'nullable|integer|min:0',
            ]);

            $equipement->update($validated);

            return redirect()->route('equipements.show', $equipement)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "EquipementController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Equipement $equipement)
    {
        try {
            $equipement->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "EquipementController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Equipement $equipement)
    {
        try {
            if ($equipement->trashed()) {
                $equipement->restore();
            } else {
                $equipement->delete();
            }

            return redirect()->route('equipements.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "EquipementController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
