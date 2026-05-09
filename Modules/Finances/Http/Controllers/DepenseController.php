<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\Depense;

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
                $query->where('libelle', 'like', "%$search%")
                    ->orWhere('numero_facture', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $depenses = $query->with(['beneficiaire'])->paginate(10)->withQueryString();

            return Inertia::render('Finances::Depenses/Index', [
                'depenses' => $depenses,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Finances::Depenses/Create');
        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'montant_cents' => 'required|integer|min:0',
                'date_depense' => 'required|date',
                'numero_facture' => 'nullable|string|max:100',
                'beneficiaire_id' => 'nullable|exists:users,id',
                'categorie' => 'nullable|string|max:100',
                'statut' => 'required|in:en_attente,autorisee,rejetee,payee',
            ]);

            Depense::create($validated);

            return redirect()->route('finances.depenses.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Depense $depense)
    {
        try {
            $depense->load('beneficiaire');

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
                'depense' => $depense->load('beneficiaire'),
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
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'montant_cents' => 'required|integer|min:0',
                'date_depense' => 'required|date',
                'numero_facture' => 'nullable|string|max:100',
                'beneficiaire_id' => 'nullable|exists:users,id',
                'categorie' => 'nullable|string|max:100',
                'statut' => 'required|in:en_attente,autorisee,rejetee,payee',
            ]);

            $depense->update($validated);

            return redirect()->route('finances.depenses.show', $depense)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "DepenseController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
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
