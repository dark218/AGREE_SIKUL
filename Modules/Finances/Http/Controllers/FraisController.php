<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\Frais;

class FraisController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:frais-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:frais-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:frais-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:frais-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Frais::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('libelle', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $frais = $query->with(['typeFrais', 'classe'])->paginate(10)->withQueryString();

            return Inertia::render('Finances::Frais/Index', [
                'frais' => $frais,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Finances::Frais/Create');
        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'type_frais_id' => 'required|exists:types_frais,id',
                'classe_id' => 'nullable|exists:classes,id',
                'code' => 'required|string|max:100|unique:frais',
                'libelle' => 'required|string|max:255',
                'montant_cents' => 'required|integer|min:0',
                'date_debut' => 'required|date',
                'date_fin' => 'nullable|date',
                'statut' => 'required|in:actif,inactif',
            ]);

            Frais::create($validated);

            return redirect()->route('finances.frais.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Frais $frai)
    {
        try {
            $frai->load('typeFrais', 'classe', 'paiements', 'echeanciers');

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
                'frais' => $frai->load('typeFrais', 'classe'),
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
                'type_frais_id' => 'required|exists:types_frais,id',
                'classe_id' => 'nullable|exists:classes,id',
                'code' => 'required|string|max:100|unique:frais,code,' . $frai->id,
                'libelle' => 'required|string|max:255',
                'montant_cents' => 'required|integer|min:0',
                'date_debut' => 'required|date',
                'date_fin' => 'nullable|date',
                'statut' => 'required|in:actif,inactif',
            ]);

            $frai->update($validated);

            return redirect()->route('finances.frais.show', $frai)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "FraisController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
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
