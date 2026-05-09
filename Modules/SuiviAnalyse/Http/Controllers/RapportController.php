<?php

namespace Modules\SuiviAnalyse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\SuiviAnalyse\Entities\Rapport;

class RapportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:rapports-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:rapports-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:rapports-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:rapports-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Rapport::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('titre', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $rapports = $query->with(['modele', 'auteur'])->paginate(10)->withQueryString();

            return Inertia::render('SuiviAnalyse::Rapports/Index', [
                'rapports' => $rapports,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('SuiviAnalyse::Rapports/Create');
        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'modele_rapport_id' => 'required|exists:modeles_rapports,id',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'auteur_id' => 'required|exists:users,id',
                'date_generation' => 'required|date',
                'periode_debut' => 'nullable|date',
                'periode_fin' => 'nullable|date|after_or_equal:periode_debut',
                'contenu' => 'nullable|string',
                'fichier_url' => 'nullable|string',
                'type' => 'required|string|max:100',
                'statut' => 'required|in:brouillon,genere,approuve,publie,archive',
            ]);

            Rapport::create($validated);

            return redirect()->route('rapports.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Rapport $rapport)
    {
        try {
            $rapport->load('modele', 'auteur');

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
                'rapport' => $rapport->load('modele', 'auteur'),
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
                'modele_rapport_id' => 'required|exists:modeles_rapports,id',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'auteur_id' => 'required|exists:users,id',
                'date_generation' => 'required|date',
                'periode_debut' => 'nullable|date',
                'periode_fin' => 'nullable|date|after_or_equal:periode_debut',
                'contenu' => 'nullable|string',
                'fichier_url' => 'nullable|string',
                'type' => 'required|string|max:100',
                'statut' => 'required|in:brouillon,genere,approuve,publie,archive',
            ]);

            $rapport->update($validated);

            return redirect()->route('rapports.show', $rapport)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
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

            return redirect()->route('rapports.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("SuiviAnalyse", "RapportController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
