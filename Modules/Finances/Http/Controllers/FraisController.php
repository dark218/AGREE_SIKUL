<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\Frais;
use Modules\Finances\Entities\TypeFrais;
use Modules\Parametrage\Entities\AnneeScolaire;

class FraisController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:frais-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:frais-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:frais-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:frais-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Frais::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                // La table `frais` n'a ni `libelle` ni `code` en base — recherche
                // via relations : typeFrais.libelle/code ou apprenant.nom.
                $query->whereHas('typeFrais', function ($q) use ($search) {
                    $q->where('libelle', 'like', "%$search%")
                      ->orWhere('code', 'like', "%$search%");
                });
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            if ($request->filled('annee_scolaire_id')) {
                $query->where('annee_scolaire_id', $request->input('annee_scolaire_id'));
            }

            $frais = $query
                ->with(['typeFrais', 'apprenant', 'anneeScolaire'])
                ->orderByDesc('created_at')
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Finances::Frais/Index', [
                'frais'           => $frais,
                'anneesScolaires' => AnneeScolaire::orderByDesc('libelle')->get(['id', 'libelle']),
                'filters'         => $request->only(['search', 'statut', 'annee_scolaire_id']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Finances::Frais/Create', [
                'typesFrais'      => TypeFrais::orderBy('libelle')->get(['id', 'code', 'libelle', 'montant_cents']),
                'apprenants'      => User::role('apprenant')->orderBy('nom')->get(['id', 'nom', 'prenoms']),
                'anneesScolaires' => AnneeScolaire::orderByDesc('libelle')->get(['id', 'libelle']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'apprenant_id'       => 'required|exists:users,id',
                'annee_scolaire_id'  => 'required|exists:annees_scolaires,id',
                'type_frais_id'      => 'required|exists:types_frais,id',
                'montant_cents'      => 'required|integer|min:0',
                'montant_paye_cents' => 'nullable|integer|min:0',
                'statut'             => 'required|in:non_paye,partiellement_paye,paye',
            ]);
            $validated['montant_paye_cents'] = $validated['montant_paye_cents'] ?? 0;

            Frais::create($validated);

            return redirect()->route('finances.frais.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function show(Frais $frai)
    {
        try {
            $frai->load(['typeFrais', 'apprenant', 'anneeScolaire', 'paiements', 'echeanciers']);

            return Inertia::render('Finances::Frais/Show', [
                'frais' => $frai,
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Frais $frai)
    {
        try {
            return Inertia::render('Finances::Frais/Edit', [
                'frais'           => $frai->load(['typeFrais', 'apprenant', 'anneeScolaire']),
                'typesFrais'      => TypeFrais::orderBy('libelle')->get(['id', 'code', 'libelle', 'montant_cents']),
                'apprenants'      => User::role('apprenant')->orderBy('nom')->get(['id', 'nom', 'prenoms']),
                'anneesScolaires' => AnneeScolaire::orderByDesc('libelle')->get(['id', 'libelle']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Frais $frai)
    {
        try {
            $validated = $request->validate([
                'apprenant_id'       => 'required|exists:users,id',
                'annee_scolaire_id'  => 'required|exists:annees_scolaires,id',
                'type_frais_id'      => 'required|exists:types_frais,id',
                'montant_cents'      => 'required|integer|min:0',
                'montant_paye_cents' => 'nullable|integer|min:0',
                'statut'             => 'required|in:non_paye,partiellement_paye,paye',
            ]);
            $validated['montant_paye_cents'] = $validated['montant_paye_cents'] ?? $frai->montant_paye_cents;

            $frai->update($validated);

            return redirect()->route('finances.frais.show', $frai)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(Frais $frai)
    {
        try {
            $frai->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Frais $frai)
    {
        try {
            if ($frai->trashed()) {
                $frai->restore();
            } else {
                $frai->delete();
            }

            return redirect()->route('finances.frais.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
