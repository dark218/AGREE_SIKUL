<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\Fourniture;
use Modules\RessourcesLogistique\Entities\CategorieFourniture;

class FournitureController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:fournitures-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:fournitures-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:fournitures-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:fournitures-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Fourniture::query();

            if ($request->filled('categorie_fourniture_id')) {
                $query->where('categorie_fourniture_id', $request->input('categorie_fourniture_id'));
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('libelle', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('fournisseur', 'like', "%{$search}%")
                        ->orWhere('localisation', 'like', "%{$search}%");
                });
            }

            $fournitures = $query->with('categorie')
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($fourniture) => [
                    'id' => $fourniture->id,
                    'libelle' => $fourniture->libelle,
                    'code' => $fourniture->code,
                    'quantite' => $fourniture->quantite,
                    'prix_unitaire_cents' => $fourniture->prix_unitaire_cents,
                    'prix_unitaire' => $fourniture->getPrixEnEuros(),
                    'valeur_total' => $fourniture->getValeurTotalEnEuros(),
                    'fournisseur' => $fourniture->fournisseur,
                    'date_acquisition' => $fourniture->date_acquisition?->toDateString(),
                    'statut' => $fourniture->statut,
                    'localisation' => $fourniture->localisation,
                    'categorie' => $fourniture->categorie?->libelle,
                ]);

            $categories = CategorieFourniture::all(['id', 'libelle'])->toArray();

            $statutOptions = [
                ['id' => 'disponible', 'libelle' => 'Disponible'],
                ['id' => 'rupture', 'libelle' => 'Rupture'],
                ['id' => 'commandee', 'libelle' => 'Commandée'],
            ];

            return Inertia::render('RessourcesLogistique::Fournitures/Index', [
                'fournitures' => $fournitures,
                'categories' => $categories,
                'statutOptions' => $statutOptions,
                'filters' => $request->only(['categorie_fourniture_id', 'statut', 'search']),
            ]);
        } catch (\Throwable $th) {
            log_error("Logistique", "FournitureController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            $categories = CategorieFourniture::all(['id', 'libelle'])->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->toArray();

            return Inertia::render('RessourcesLogistique::Fournitures/Create', [
                'categories' => $categories,
                'title' => __('actions.add'),
            ]);
        } catch (\Throwable $th) {
            log_error("Logistique", "FournitureController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'categorie_fourniture_id' => 'required|exists:categories_fournitures,id',
                'libelle' => 'required|string|max:125',
                'code' => 'nullable|string|max:50|unique:fournitures',
                'quantite' => 'required|integer|min:0',
                'prix_unitaire_cents' => 'nullable|integer|min:0',
                'fournisseur' => 'nullable|string|max:125',
                'date_acquisition' => 'nullable|date',
                'statut' => 'required|in:disponible,rupture,commandee',
                'localisation' => 'nullable|string|max:125',
            ]);

            Fourniture::create($validated);

            return redirect()->route('fournitures.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Logistique", "FournitureController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Fourniture $fourniture)
    {
        try {
            $fourniture->load('categorie');
            $categories = CategorieFourniture::all(['id', 'libelle'])->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->toArray();

            return Inertia::render('RessourcesLogistique::Fournitures/Show', [
                'item' => $fourniture,
                'categories' => $categories,
            ]);
        } catch (\Throwable $th) {
            log_error("Logistique", "FournitureController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Fourniture $fourniture)
    {
        try {
            $categories = CategorieFourniture::all(['id', 'libelle'])->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->toArray();

            return Inertia::render('RessourcesLogistique::Fournitures/Edit', [
                'item' => $fourniture->load('categorie'),
                'categories' => $categories,
                'title' => __('actions.edit'),
            ]);
        } catch (\Throwable $th) {
            log_error("Logistique", "FournitureController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Fourniture $fourniture)
    {
        try {
            $validated = $request->validate([
                'categorie_fourniture_id' => 'required|exists:categories_fournitures,id',
                'libelle' => 'required|string|max:125',
                'code' => 'nullable|string|max:50|unique:fournitures,code,' . $fourniture->id,
                'quantite' => 'required|integer|min:0',
                'prix_unitaire_cents' => 'nullable|integer|min:0',
                'fournisseur' => 'nullable|string|max:125',
                'date_acquisition' => 'nullable|date',
                'statut' => 'required|in:disponible,rupture,commandee',
                'localisation' => 'nullable|string|max:125',
            ]);

            $fourniture->update($validated);

            return redirect()->route('fournitures.show', $fourniture)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Logistique", "FournitureController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Fourniture $fourniture)
    {
        try {
            $fourniture->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Logistique", "FournitureController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Fourniture $fourniture)
    {
        try {
            if ($fourniture->trashed()) {
                $fourniture->restore();
            } else {
                $fourniture->delete();
            }

            return redirect()->route('fournitures.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Logistique", "FournitureController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
