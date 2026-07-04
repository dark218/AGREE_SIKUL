<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\Paiement;

class PaiementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:paiements-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:paiements-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:paiements-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:paiements-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Paiement::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('numero_recu', 'like', "%$search%")
                    ->orWhere('reference_transaction', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $paiements = $query->with(['frais', 'apprenant.user'])->paginate(10)->withQueryString();

            return Inertia::render('Finances::Paiements/Index', [
                'paiements' => $paiements,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Finances::Paiements/Create');
        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'frais_id' => 'required|exists:frais,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'montant_cents' => 'required|integer|min:0',
                'date_paiement' => 'required|date',
                'mode_paiement' => 'required|in:especes,cheque,virement,carte_bancaire,autre',
                'numero_recu' => 'nullable|string|max:100',
                'reference_transaction' => 'nullable|string|max:255',
                'observations' => 'nullable|string',
                'statut' => 'required|in:en_attente,confirmé,rejeté',
            ]);

            Paiement::create($validated);

            return redirect()->route('finances.paiements.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Paiement $paiement)
    {
        try {
            $paiement->load('frais', 'apprenant');

            return Inertia::render('Finances::Paiements/Show', [
                'paiement' => $paiement,
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Paiement $paiement)
    {
        try {
            return Inertia::render('Finances::Paiements/Edit', [
                'paiement' => $paiement->load('frais', 'apprenant'),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Paiement $paiement)
    {
        try {
            $validated = $request->validate([
                'frais_id' => 'required|exists:frais,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'montant_cents' => 'required|integer|min:0',
                'date_paiement' => 'required|date',
                'mode_paiement' => 'required|in:especes,cheque,virement,carte_bancaire,autre',
                'numero_recu' => 'nullable|string|max:100',
                'reference_transaction' => 'nullable|string|max:255',
                'observations' => 'nullable|string',
                'statut' => 'required|in:en_attente,confirmé,rejeté',
            ]);

            $paiement->update($validated);

            return redirect()->route('finances.paiements.show', $paiement)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Paiement $paiement)
    {
        try {
            $paiement->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Paiement $paiement)
    {
        try {
            if ($paiement->trashed()) {
                $paiement->restore();
            } else {
                $paiement->delete();
            }

            return redirect()->route('finances.paiements.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
