<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\Echeancier;
use Modules\Finances\Entities\Frais;

class EcheancierController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:echeanciers-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:echeanciers-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:echeanciers-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:echeanciers-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Echeancier::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('numero_echeance', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $echeanciers = $query
                ->with(['frais.typeFrais', 'frais.apprenant'])
                ->orderBy('date_echeance')
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Finances::Echeanciers/Index', [
                'echeanciers' => $echeanciers,
                'filters'     => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "EcheancierController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Finances::Echeanciers/Create', [
                'frais' => Frais::with(['typeFrais', 'apprenant'])
                    ->orderByDesc('created_at')
                    ->get(['id', 'apprenant_id', 'type_frais_id', 'montant_cents', 'montant_paye_cents']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "EcheancierController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'frais_id'        => 'required|exists:frais,id',
                'numero_echeance' => 'required|integer|min:1',
                'montant_cents'   => 'required|integer|min:0',
                'date_echeance'   => 'required|date',
                'date_paiement'   => 'nullable|date',
                // Enum DB : en_attente, paye, retard
                'statut'          => 'required|in:en_attente,paye,retard',
            ]);

            Echeancier::create($validated);

            return redirect()->route('finances.echeanciers.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Finances", "EcheancierController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function show(Echeancier $echeancier)
    {
        try {
            $echeancier->load(['frais.typeFrais', 'frais.apprenant']);

            return Inertia::render('Finances::Echeanciers/Show', [
                'echeancier' => $echeancier,
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "EcheancierController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Echeancier $echeancier)
    {
        try {
            return Inertia::render('Finances::Echeanciers/Edit', [
                'echeancier' => $echeancier->load(['frais.typeFrais', 'frais.apprenant']),
                'frais'      => Frais::with(['typeFrais', 'apprenant'])
                    ->orderByDesc('created_at')
                    ->get(['id', 'apprenant_id', 'type_frais_id', 'montant_cents', 'montant_paye_cents']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "EcheancierController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Echeancier $echeancier)
    {
        try {
            $validated = $request->validate([
                'frais_id'        => 'required|exists:frais,id',
                'numero_echeance' => 'required|integer|min:1',
                'montant_cents'   => 'required|integer|min:0',
                'date_echeance'   => 'required|date',
                'date_paiement'   => 'nullable|date',
                'statut'          => 'required|in:en_attente,paye,retard',
            ]);

            $echeancier->update($validated);

            return redirect()->route('finances.echeanciers.show', $echeancier)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Finances", "EcheancierController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(Echeancier $echeancier)
    {
        try {
            $echeancier->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "EcheancierController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Echeancier $echeancier)
    {
        try {
            if ($echeancier->trashed()) {
                $echeancier->restore();
            } else {
                $echeancier->delete();
            }

            return redirect()->route('finances.echeanciers.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Finances", "EcheancierController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
