<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\Depense;
use Modules\Parametrage\Entities\Ecole;

class DepenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:depenses-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:depenses-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:depenses-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:depenses-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Depense::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('libelle', 'like', "%$search%")
                      ->orWhere('categorie', 'like', "%$search%");
                });
            }

            if ($request->filled('categorie')) {
                $query->where('categorie', $request->input('categorie'));
            }

            if ($request->filled('ecole_id')) {
                $query->where('ecole_id', $request->input('ecole_id'));
            }

            $depenses = $query->with(['auteur', 'ecole', 'facture'])
                              ->orderByDesc('date_depense')
                              ->paginate(10)
                              ->withQueryString();

            return Inertia::render('Finances::Depenses/Index', [
                'depenses' => $depenses,
                'ecoles'   => Ecole::where('statut', 'actif')->get(['id', 'nom']),
                'filters'  => $request->only(['search', 'categorie', 'ecole_id']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Finances::Depenses/Create', [
                'ecoles' => Ecole::where('statut', 'actif')->get(['id', 'nom']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'ecole_id'      => 'required|exists:users,id',
                'libelle'       => 'required|string|max:255',
                'categorie'     => 'required|string|max:100',
                'montant_cents' => 'required|integer|min:0',
                'date_depense'  => 'required|date',
                'facture_id'    => 'nullable|exists:fichier,id',
            ]);

            // auteur_id = utilisateur courant (non modifiable par le form).
            $validated['auteur_id'] = auth()->id();

            Depense::create($validated);

            return redirect()->route('finances.depenses.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function show(Depense $depense)
    {
        try {
            $depense->load(['auteur', 'ecole', 'facture']);

            return Inertia::render('Finances::Depenses/Show', [
                'depense' => $depense,
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Depense $depense)
    {
        try {
            return Inertia::render('Finances::Depenses/Edit', [
                'item'   => $depense->load(['auteur', 'ecole', 'facture']),
                'ecoles' => Ecole::where('statut', 'actif')->get(['id', 'nom']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Depense $depense)
    {
        try {
            $validated = $request->validate([
                'ecole_id'      => 'required|exists:users,id',
                'libelle'       => 'required|string|max:255',
                'categorie'     => 'required|string|max:100',
                'montant_cents' => 'required|integer|min:0',
                'date_depense'  => 'required|date',
                'facture_id'    => 'nullable|exists:fichier,id',
            ]);

            $depense->update($validated);

            return redirect()->route('finances.depenses.show', $depense)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(Depense $depense)
    {
        try {
            $depense->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    // Restore soft-deleted (soft-delete est déjà géré par destroy() → un
    // second appel restaure). Pas de colonne "statut" en base pour Depense.
    public function statut(Depense $depense)
    {
        try {
            if ($depense->trashed()) {
                $depense->restore();
            } else {
                $depense->delete();
            }

            return redirect()->route('finances.depenses.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
