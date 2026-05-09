<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\Ecolage;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Niveau;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;

class EcolageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:ecolage-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:ecolage-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:ecolage-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:ecolage-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            \Log::info("=== EcolageController::index called ===");

            $query = Ecolage::with(['anneeScolaire', 'niveau', 'ecole', 'campus']);
            \Log::info("Ecolage query initialized");

            if ($request->filled('annee_scolaire_id')) {
                $query->where('annee_scolaire_id', $request->input('annee_scolaire_id'));
            }

            if ($request->filled('niveau_id')) {
                $query->where('niveau_id', $request->input('niveau_id'));
            }

            if ($request->filled('ecole_id')) {
                $query->where('ecole_id', $request->input('ecole_id'));
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $ecolages = $query->paginate(10)->withQueryString()
                ->through(function ($ecolage) {
                    return [
                        'id' => $ecolage->id,
                        'annee_scolaire_id' => $ecolage->annee_scolaire_id,
                        'annee_scolaire' => $ecolage->anneeScolaire ? ['id' => $ecolage->anneeScolaire->id, 'libelle' => $ecolage->anneeScolaire->libelle] : null,
                        'niveau_id' => $ecolage->niveau_id,
                        'niveau' => $ecolage->niveau ? ['id' => $ecolage->niveau->id, 'nom' => $ecolage->niveau->nom ?? $ecolage->niveau->libelle] : null,
                        'ecole_id' => $ecolage->ecole_id,
                        'ecole' => $ecolage->ecole ? ['id' => $ecolage->ecole->id, 'nom' => $ecolage->ecole->nom] : null,
                        'campus_id' => $ecolage->campus_id,
                        'campus' => $ecolage->campus ? ['id' => $ecolage->campus->id, 'nom' => $ecolage->campus->nom] : null,
                        'frais_dossier' => $ecolage->frais_dossier,
                        'frais_inscription' => $ecolage->frais_inscription,
                        'frais_scolarite' => $ecolage->frais_scolarite,
                        'etat' => $ecolage->etat,
                        'created_at' => $ecolage->created_at,
                        'updated_at' => $ecolage->updated_at,
                    ];
                });
            \Log::info("Ecolages paginated", ['count' => $ecolages->count()]);

            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            \Log::info("AnneeScolaires loaded", ['count' => $anneesScolaires->count()]);

            $niveaux = Niveau::where('statut', 'actif')->get();
            \Log::info("Niveaux loaded", ['count' => $niveaux->count()]);

            $ecoles = Ecole::where('statut', 'actif')->get();
            \Log::info("Ecoles loaded", ['count' => $ecoles->count()]);

            \Log::info("About to render Finances::Ecolage/Index");

            return Inertia::render('Finances::Ecolage/Index', [
                'ecolages' => $ecolages,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'niveaux' => $niveaux,
                'filters' => $request->only(['annee_scolaire_id', 'niveau_id', 'ecole_id', 'etat']),
                'title' => 'Gestion des Écolages',
            ]);
        } catch (\Throwable $th) {
            \Log::error("EcolageController::index ERROR", [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            log_error("Finances", "EcolageController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            $niveaux = Niveau::where('statut', 'actif')->get();
            $ecoles = Ecole::where('statut', 'actif')->get();
            $campuses = Campus::where('statut', 'actif')->get();

            return Inertia::render('Finances::Ecolage/Create', [
                'anneesScolaires' => $anneesScolaires,
                'niveaux' => $niveaux,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'title' => 'Créer une Écolage',
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'niveau_id' => 'required|exists:niveaux,id',
                'ecole_id' => 'required|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'frais_dossier' => 'nullable|numeric|min:0',
                'frais_inscription' => 'nullable|numeric|min:0',
                'frais_scolarite' => 'nullable|numeric|min:0',
                'etat' => 'required|in:actif,inactif',
            ]);

            $validated['creation_username'] = auth()->user()->name ?? 'system';
            Ecolage::create($validated);

            return redirect()->route('finances.ecolage.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Ecolage $ecolage)
    {
        try {
            $ecolage->load(['anneeScolaire', 'niveau', 'ecole', 'campus']);

            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            $niveaux = Niveau::where('statut', 'actif')->get();
            $ecoles = Ecole::where('statut', 'actif')->get();
            $campuses = Campus::where('statut', 'actif')->get();

            return Inertia::render('Finances::Ecolage/Show', [
                'item' => $ecolage,
                'anneesScolaires' => $anneesScolaires,
                'niveaux' => $niveaux,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'title' => 'Détails de l\'Écolage',
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Ecolage $ecolage)
    {
        try {
            $ecolage->load(['anneeScolaire', 'niveau', 'ecole', 'campus']);

            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            $niveaux = Niveau::where('statut', 'actif')->get();
            $ecoles = Ecole::where('statut', 'actif')->get();
            $campuses = Campus::where('statut', 'actif')->get();

            return Inertia::render('Finances::Ecolage/Edit', [
                'item' => $ecolage,
                'anneesScolaires' => $anneesScolaires,
                'niveaux' => $niveaux,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'title' => 'Modifier l\'Écolage',
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Ecolage $ecolage)
    {
        try {
            $validated = $request->validate([
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'niveau_id' => 'required|exists:niveaux,id',
                'ecole_id' => 'required|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'frais_dossier' => 'nullable|numeric|min:0',
                'frais_inscription' => 'nullable|numeric|min:0',
                'frais_scolarite' => 'nullable|numeric|min:0',
                'etat' => 'required|in:actif,inactif',
            ]);

            $validated['modification_username'] = auth()->user()->name ?? 'system';
            $ecolage->update($validated);

            return redirect()->route('finances.ecolage.show', $ecolage)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Ecolage $ecolage)
    {
        try {
            $ecolage->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Ecolage $ecolage)
    {
        try {
            $ecolage->etat = $ecolage->etat === 'actif' ? 'inactif' : 'actif';
            $ecolage->modification_username = auth()->user()->name ?? 'system';
            $ecolage->save();

            return redirect()->route('finances.ecolage.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
