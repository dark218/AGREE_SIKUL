<?php

namespace Modules\SuiviAnalyse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\SuiviAnalyse\Entities\ModeleRapport;
use Modules\SuiviAnalyse\Entities\Rapport;

class RapportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:rapports-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:rapports-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:rapports-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:rapports-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Rapport::query();

            if ($request->filled('search')) {
                $query->where('titre', 'like', '%' . $request->input('search') . '%');
            }

            if ($request->filled('modele_rapport_id')) {
                $query->where('modele_rapport_id', $request->input('modele_rapport_id'));
            }

            $rapports = $query
                ->with(['modeleRapport', 'generePar'])
                ->orderByDesc('date_generation')
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('SuiviAnalyse::Rapports/Index', [
                'rapports' => $rapports,
                'modeles'  => ModeleRapport::orderBy('titre')->get(['id', 'titre', 'code']),
                'filters'  => $request->only(['search', 'modele_rapport_id']),
            ]);
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('SuiviAnalyse::Rapports/Create', [
                'modeles' => ModeleRapport::orderBy('titre')->get(['id', 'titre', 'code', 'type']),
            ]);
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'modele_rapport_id'   => 'required|exists:modeles_rapports,id',
                'titre'               => 'required|string|max:255',
                'parametres_utilises' => 'nullable|array',
                'fichier_id'          => 'nullable|integer',
                'date_generation'     => 'nullable|date',
            ]);
            // genere_par = utilisateur courant.
            $validated['genere_par']      = auth()->id();
            $validated['date_generation'] = $validated['date_generation'] ?? now();

            Rapport::create($validated);

            return redirect()->route('suivi-analyse.rapports.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function show(Rapport $rapport)
    {
        try {
            $rapport->load(['modeleRapport', 'generePar']);

            return Inertia::render('SuiviAnalyse::Rapports/Show', [
                'rapport' => $rapport,
            ]);
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Rapport $rapport)
    {
        try {
            return Inertia::render('SuiviAnalyse::Rapports/Edit', [
                'rapport' => $rapport->load(['modeleRapport', 'generePar']),
                'modeles' => ModeleRapport::orderBy('titre')->get(['id', 'titre', 'code', 'type']),
            ]);
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Rapport $rapport)
    {
        try {
            $validated = $request->validate([
                'modele_rapport_id'   => 'required|exists:modeles_rapports,id',
                'titre'               => 'required|string|max:255',
                'parametres_utilises' => 'nullable|array',
                'fichier_id'          => 'nullable|integer',
                'date_generation'     => 'nullable|date',
            ]);

            $rapport->update($validated);

            return redirect()->route('suivi-analyse.rapports.show', $rapport)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(Rapport $rapport)
    {
        try {
            $rapport->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Rapport $rapport)
    {
        try {
            if ($rapport->trashed()) {
                $rapport->restore();
            } else {
                $rapport->delete();
            }

            return redirect()->route('suivi-analyse.rapports.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
