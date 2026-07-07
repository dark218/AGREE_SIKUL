<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Finances\Entities\Versement;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;
use Modules\Academique\Entities\Apprenant;

class VersementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:versement-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:versement-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:versement-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:versement-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Versement::with(['apprenant.user', 'classe', 'ecole', 'anneeScolaire', 'niveau']);

            // Filtre apprenant (search sur nom + prenoms)
            if ($request->filled('apprenant')) {
                $search = $request->input('apprenant');
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('prenoms', 'like', "%{$search}%");
                });
            }

            if ($request->filled('ecole_id')) {
                $query->where('ecole_id', $request->input('ecole_id'));
            }

            if ($request->filled('annee_scolaire_id')) {
                $query->where('annee_scolaire_id', $request->input('annee_scolaire_id'));
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $versements = $query->paginate(10)
                ->withQueryString()
                ->through(function ($versement) {
                    return [
                        'id' => $versement->id,
                        'apprenant_id' => $versement->apprenant_id,
                        'apprenant' => $versement->apprenant ? [
                            'id' => $versement->apprenant->id,
                            'nom' => $versement->apprenant->user?->nom ?? '',
                            'prenoms' => $versement->apprenant->user?->prenoms ?? ''
                        ] : null,
                        'niveau_id' => $versement->niveau_id,
                        'niveau' => $versement->niveau ? ['id' => $versement->niveau->id, 'nom' => $versement->niveau->nom ?? $versement->niveau->libelle] : null,
                        'classe_id' => $versement->classe_id,
                        'classe' => $versement->classe ? ['id' => $versement->classe->id, 'nom' => $versement->classe->nom] : null,
                        'ecole_id' => $versement->ecole_id,
                        'ecole' => $versement->ecole ? ['id' => $versement->ecole->id, 'nom' => $versement->ecole->nom] : null,
                        'annee_scolaire_id' => $versement->annee_scolaire_id,
                        'annee_scolaire' => $versement->anneeScolaire ? ['id' => $versement->anneeScolaire->id, 'libelle' => $versement->anneeScolaire->libelle] : null,
                        'campus_id' => $versement->campus_id,
                        'frais_dossier' => $versement->frais_dossier,
                        'frais_inscription' => $versement->frais_inscription,
                        'frais_scolarite' => $versement->frais_scolarite,
                        'total_paye' => $versement->total_paye,
                        'restant_a_payer' => $versement->restant_a_payer,
                        'etat' => $versement->etat,
                        'created_at' => $versement->created_at,
                        'updated_at' => $versement->updated_at,
                    ];
                });

            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            $ecoles = Ecole::where('statut', 'actif')->get();

            return Inertia::render('Finances::Versements/Index', [
                'versements' => $versements,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'filters' => $request->only(['apprenant', 'ecole_id', 'annee_scolaire_id', 'etat']),
                'title' => 'Gestion des Versements',
            ]);
        } catch (\Throwable $th) {
            \Log::error("VersementController::index ERROR", [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            log_error("Finances", "VersementController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            \Log::info("=== VersementController::create called ===");

            // Load apprenants with user relationship (nom, prenoms stored in users table)
            $apprenants = Apprenant::where('statut', 'actif')
                ->with('user', 'classe')
                ->get()
                ->map(function ($apprenant) {
                    return [
                        'id' => $apprenant->id,
                        'nom' => $apprenant->user?->nom ?? '',
                        'prenoms' => $apprenant->user?->prenoms ?? '',
                        'nom_restituer' => $apprenant->user?->nom ?? '',
                        'sexe' => $apprenant->sexe,
                        'date_naissance' => $apprenant->date_naissance,
                        'classe_id' => $apprenant->classe_id,
                    ];
                });
            \Log::info("Apprenants loaded", ['count' => $apprenants->count()]);

            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            \Log::info("AnneeScolaires loaded", ['count' => $anneesScolaires->count()]);

            $niveaux = NiveauEtude::where('statut', 'actif')->get();
            \Log::info("Niveaux loaded", ['count' => $niveaux->count()]);

            $classes = Classe::where('statut', 'actif')->get();
            \Log::info("Classes loaded", ['count' => $classes->count()]);

            $ecoles = Ecole::where('statut', 'actif')->get();
            \Log::info("Ecoles loaded", ['count' => $ecoles->count()]);

            $campuses = Campus::where('statut', 'actif')->get();
            \Log::info("Campuses loaded", ['count' => $campuses->count()]);

            \Log::info("About to render Finances::Versements/Create");

            return Inertia::render('Finances::Versements/Create', [
                'apprenants' => $apprenants,
                'anneesScolaires' => $anneesScolaires,
                'niveaux' => $niveaux,
                'classes' => $classes,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'title' => 'Créer un Versement',
            ]);
        } catch (\Throwable $th) {
            \Log::error("VersementController::create ERROR", [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            log_error("Finances", "VersementController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'niveau_id' => 'nullable|exists:niveaux,id',
                'classe_id' => 'nullable|exists:classes,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'frais_dossier' => 'nullable|numeric|min:0',
                'frais_inscription' => 'nullable|numeric|min:0',
                'frais_scolarite' => 'nullable|numeric|min:0',
                'total_paye' => 'nullable|numeric|min:0',
                'restant_a_payer' => 'nullable|numeric|min:0',
                'nature_versement_1' => 'nullable|string',
                'montant_versement_1' => 'nullable|numeric|min:0',
                'nature_versement_2' => 'nullable|string',
                'montant_versement_2' => 'nullable|numeric|min:0',
                'nature_versement_3' => 'nullable|string',
                'montant_versement_3' => 'nullable|numeric|min:0',
                'nature_versement_4' => 'nullable|string',
                'montant_versement_4' => 'nullable|numeric|min:0',
                'nature_versement_5' => 'nullable|string',
                'montant_versement_5' => 'nullable|numeric|min:0',
                'nature_versement_6' => 'nullable|string',
                'montant_versement_6' => 'nullable|numeric|min:0',
                'nature_versement_7' => 'nullable|string',
                'montant_versement_7' => 'nullable|numeric|min:0',
                'nature_versement_8' => 'nullable|string',
                'montant_versement_8' => 'nullable|numeric|min:0',
                'nature_versement_9' => 'nullable|string',
                'montant_versement_9' => 'nullable|numeric|min:0',
                'nature_versement_10' => 'nullable|string',
                'montant_versement_10' => 'nullable|numeric|min:0',
                'nature_versement_11' => 'nullable|string',
                'montant_versement_11' => 'nullable|numeric|min:0',
                'nature_versement_12' => 'nullable|string',
                'montant_versement_12' => 'nullable|numeric|min:0',
                'etat' => 'required|in:actif,inactif',
            ]);

            $validated['creation_username'] = auth()->user()->name ?? 'system';
            Versement::create($validated);

            return redirect()->route('finances.versements.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "VersementController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Versement $versement)
    {
        try {
            $versement->load(['apprenant', 'classe', 'ecole', 'anneeScolaire', 'niveau']);

            $apprenants = Apprenant::where('statut', 'actif')
                ->with('user')
                ->get()
                ->map(function ($apprenant) {
                    return [
                        'id' => $apprenant->id,
                        'nom' => $apprenant->user?->nom ?? '',
                        'prenoms' => $apprenant->user?->prenoms ?? '',
                        'nom_restituer' => $apprenant->user?->nom ?? '',
                        'sexe' => $apprenant->sexe,
                        'date_naissance' => $apprenant->date_naissance,
                        'classe_id' => $apprenant->classe_id,
                    ];
                });
            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            $niveaux = NiveauEtude::where('statut', 'actif')->get();
            $classes = Classe::where('statut', 'actif')->get();
            $ecoles = Ecole::where('statut', 'actif')->get();
            $campuses = Campus::where('statut', 'actif')->get();

            return Inertia::render('Finances::Versements/Show', [
                'item' => $versement,
                'apprenants' => $apprenants,
                'anneesScolaires' => $anneesScolaires,
                'niveaux' => $niveaux,
                'classes' => $classes,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'title' => 'Détails du Versement',
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "VersementController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Versement $versement)
    {
        try {
            $versement->load(['apprenant', 'classe', 'ecole', 'anneeScolaire', 'niveau']);

            $apprenants = Apprenant::where('statut', 'actif')
                ->with('user')
                ->get()
                ->map(function ($apprenant) {
                    return [
                        'id' => $apprenant->id,
                        'nom' => $apprenant->user?->nom ?? '',
                        'prenoms' => $apprenant->user?->prenoms ?? '',
                        'nom_restituer' => $apprenant->user?->nom ?? '',
                        'sexe' => $apprenant->sexe,
                        'date_naissance' => $apprenant->date_naissance,
                        'classe_id' => $apprenant->classe_id,
                    ];
                });
            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            $niveaux = NiveauEtude::where('statut', 'actif')->get();
            $classes = Classe::where('statut', 'actif')->get();
            $ecoles = Ecole::where('statut', 'actif')->get();
            $campuses = Campus::where('statut', 'actif')->get();

            return Inertia::render('Finances::Versements/Edit', [
                'item' => $versement,
                'apprenants' => $apprenants,
                'anneesScolaires' => $anneesScolaires,
                'niveaux' => $niveaux,
                'classes' => $classes,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'title' => 'Modifier le Versement',
            ]);
        } catch (\Throwable $th) {
            log_error("Finances", "VersementController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Versement $versement)
    {
        try {
            $validated = $request->validate([
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'niveau_id' => 'nullable|exists:niveaux,id',
                'classe_id' => 'nullable|exists:classes,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'frais_dossier' => 'nullable|numeric|min:0',
                'frais_inscription' => 'nullable|numeric|min:0',
                'frais_scolarite' => 'nullable|numeric|min:0',
                'total_paye' => 'nullable|numeric|min:0',
                'restant_a_payer' => 'nullable|numeric|min:0',
                'nature_versement_1' => 'nullable|string',
                'montant_versement_1' => 'nullable|numeric|min:0',
                'nature_versement_2' => 'nullable|string',
                'montant_versement_2' => 'nullable|numeric|min:0',
                'nature_versement_3' => 'nullable|string',
                'montant_versement_3' => 'nullable|numeric|min:0',
                'nature_versement_4' => 'nullable|string',
                'montant_versement_4' => 'nullable|numeric|min:0',
                'nature_versement_5' => 'nullable|string',
                'montant_versement_5' => 'nullable|numeric|min:0',
                'nature_versement_6' => 'nullable|string',
                'montant_versement_6' => 'nullable|numeric|min:0',
                'nature_versement_7' => 'nullable|string',
                'montant_versement_7' => 'nullable|numeric|min:0',
                'nature_versement_8' => 'nullable|string',
                'montant_versement_8' => 'nullable|numeric|min:0',
                'nature_versement_9' => 'nullable|string',
                'montant_versement_9' => 'nullable|numeric|min:0',
                'nature_versement_10' => 'nullable|string',
                'montant_versement_10' => 'nullable|numeric|min:0',
                'nature_versement_11' => 'nullable|string',
                'montant_versement_11' => 'nullable|numeric|min:0',
                'nature_versement_12' => 'nullable|string',
                'montant_versement_12' => 'nullable|numeric|min:0',
                'etat' => 'required|in:actif,inactif',
            ]);

            $validated['modification_username'] = auth()->user()->name ?? 'system';
            $versement->update($validated);

            return redirect()->route('finances.versements.show', $versement)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "VersementController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Versement $versement)
    {
        try {
            $versement->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "VersementController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Versement $versement)
    {
        try {
            $versement->etat = $versement->etat === 'actif' ? 'inactif' : 'actif';
            $versement->modification_username = auth()->user()->name ?? 'system';
            $versement->save();

            return redirect()->route('finances.versements.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Finances", "VersementController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
