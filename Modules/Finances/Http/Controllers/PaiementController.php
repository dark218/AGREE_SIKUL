<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\Frais;
use Modules\Finances\Entities\Paiement;

class PaiementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:paiements-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:paiements-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:paiements-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:paiements-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Paiement::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('reference', 'like', "%$search%");
            }

            if ($request->filled('mode_paiement')) {
                $query->where('mode_paiement', $request->input('mode_paiement'));
            }

            $paiements = $query
                ->with(['frais.typeFrais', 'apprenant', 'recuPar'])
                ->orderByDesc('date_paiement')
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Finances::Paiements/Index', [
                'paiements' => $paiements,
                'filters'   => $request->only(['search', 'mode_paiement']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Finances::Paiements/Create', [
                'frais'      => Frais::with(['typeFrais', 'apprenant'])
                    ->orderByDesc('created_at')
                    ->get(['id', 'apprenant_id', 'type_frais_id', 'montant_cents', 'montant_paye_cents']),
                'apprenants' => User::role('apprenant')->orderBy('nom')->get(['id', 'nom', 'prenoms']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'frais_id'      => 'required|exists:frais,id',
                'apprenant_id'  => 'required|exists:users,id',
                'montant_cents' => 'required|integer|min:0',
                // Enum DB : espece, cheque, virement, mobile_money, carte
                'mode_paiement' => 'required|in:espece,cheque,virement,mobile_money,carte',
                'reference'     => 'nullable|string|max:255|unique:paiements,reference',
                'date_paiement' => 'required|date',
            ]);
            // recu_par = utilisateur courant.
            $validated['recu_par'] = auth()->id();

            Paiement::create($validated);

            return redirect()->route('finances.paiements.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function show(Paiement $paiement)
    {
        try {
            $paiement->load(['frais.typeFrais', 'apprenant', 'recuPar']);

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
                'paiement'   => $paiement->load(['frais.typeFrais', 'apprenant']),
                'frais'      => Frais::with(['typeFrais', 'apprenant'])
                    ->orderByDesc('created_at')
                    ->get(['id', 'apprenant_id', 'type_frais_id', 'montant_cents', 'montant_paye_cents']),
                'apprenants' => User::role('apprenant')->orderBy('nom')->get(['id', 'nom', 'prenoms']),
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
                'frais_id'      => 'required|exists:frais,id',
                'apprenant_id'  => 'required|exists:users,id',
                'montant_cents' => 'required|integer|min:0',
                'mode_paiement' => 'required|in:espece,cheque,virement,mobile_money,carte',
                'reference'     => 'nullable|string|max:255|unique:paiements,reference,' . $paiement->id,
                'date_paiement' => 'required|date',
            ]);

            $paiement->update($validated);

            return redirect()->route('finances.paiements.show', $paiement)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Finances", "PaiementController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
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
}
