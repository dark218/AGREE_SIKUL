<?php

namespace Modules\Finances\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Finances\Entities\Ecolage;
use Modules\Finances\Entities\PosteRecette;
use Modules\Finances\Entities\PlanCompte;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\CycleEnseignement;

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

            $query = Ecolage::with(['anneeScolaire', 'niveau', 'ecole', 'campus', 'section', 'cycle', 'frais']);

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
                        'section' => $ecolage->section ? ['id' => $ecolage->section->id, 'libelle' => $ecolage->section->libelle] : null,
                        'cycle' => $ecolage->cycle ? ['id' => $ecolage->cycle->id, 'libelle' => $ecolage->cycle->libelle] : null,
                        'nb_frais' => $ecolage->frais->count(),
                        'montant_total' => $ecolage->frais->sum('montant'),
                        'etat' => $ecolage->etat,
                        'created_at' => $ecolage->created_at,
                        'updated_at' => $ecolage->updated_at,
                    ];
                });
            \Log::info("Ecolages paginated", ['count' => $ecolages->count()]);

            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            \Log::info("AnneeScolaires loaded", ['count' => $anneesScolaires->count()]);

            $niveaux = NiveauEtude::where('statut', 'actif')->get();
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
            return Inertia::render('Finances::Ecolage/Create', array_merge($this->formLookups(), [
                'title' => 'Créer une Écolage',
            ]));
        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->validationRules());

            DB::transaction(function () use ($validated, $request) {
                $header = collect($validated)->except('frais')->toArray();
                $header['creation_username'] = auth()->user()->name ?? 'system';
                $ecolage = Ecolage::create($header);
                $this->syncFrais($ecolage, $request->input('frais', []) ?? []);
            });

            return redirect()->route('finances.ecolage.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'))->withInput();
        }
    }

    public function show(Ecolage $ecolage)
    {
        try {
            $ecolage->load(['anneeScolaire', 'niveau', 'ecole', 'campus', 'section', 'cycle', 'frais.posteRecette', 'frais.planCompte']);

            return Inertia::render('Finances::Ecolage/Show', array_merge($this->formLookups(), [
                'item' => $ecolage,
                'title' => 'Détails de l\'Écolage',
            ]));
        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Ecolage $ecolage)
    {
        try {
            $ecolage->load(['anneeScolaire', 'niveau', 'ecole', 'campus', 'section', 'cycle', 'frais.posteRecette', 'frais.planCompte']);

            return Inertia::render('Finances::Ecolage/Edit', array_merge($this->formLookups(), [
                'item' => $ecolage,
                'title' => 'Modifier l\'Écolage',
            ]));
        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Ecolage $ecolage)
    {
        try {
            $validated = $request->validate($this->validationRules());

            DB::transaction(function () use ($validated, $request, $ecolage) {
                $header = collect($validated)->except('frais')->toArray();
                $header['modification_username'] = auth()->user()->name ?? 'system';
                $ecolage->update($header);
                $this->syncFrais($ecolage, $request->input('frais', []) ?? []);
            });

            return redirect()->route('finances.ecolage.show', $ecolage)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Finances", "EcolageController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'))->withInput();
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

    /**
     * Listes d'options communes aux formulaires (create/edit/show).
     */
    private function formLookups(): array
    {
        return [
            'anneesScolaires' => AnneeScolaire::where('etat', 'actif')->get(['id', 'libelle']),
            'niveaux' => NiveauEtude::where('statut', 'actif')->get(),
            'ecoles' => Ecole::where('statut', 'actif')->get(['id', 'nom']),
            'campuses' => Campus::where('statut', 'actif')->get(['id', 'nom']),
            'sections' => Section::whereNull('deleted_at')->orderBy('libelle')->get(['id', 'libelle']),
            'cycles' => CycleEnseignement::whereNull('deleted_at')->orderBy('libelle')->get(['id', 'libelle']),
            'postesRecettes' => PosteRecette::where('etat', 'actif')->orderBy('libelle')->get(['id', 'code', 'libelle']),
            'comptes' => PlanCompte::where('etat', 'actif')->orderBy('numero_compte')->get(['id', 'numero_compte', 'libelle_compte']),
        ];
    }

    /**
     * Règles de validation communes (en-tête + lignes de frais).
     */
    private function validationRules(): array
    {
        return [
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
            'niveau_id' => 'required|exists:niveaux,id',
            'ecole_id' => 'required|exists:ecoles,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'section_id' => 'nullable|exists:sections,id',
            'cycle_id' => 'nullable|exists:cycles_enseignement,id',
            'etat' => 'required|in:actif,inactif',
            'frais' => 'nullable|array',
            'frais.*.poste_recette_id' => 'nullable|exists:postes_recettes,id',
            'frais.*.plan_compte_id' => 'nullable|exists:plan_comptes,id',
            'frais.*.libelle' => 'nullable|string|max:255',
            'frais.*.montant' => 'nullable|numeric|min:0',
            'frais.*.date_limite' => 'nullable|date',
        ];
    }

    /**
     * Remplace les lignes de frais d'un écolage par celles fournies.
     */
    private function syncFrais(Ecolage $ecolage, array $frais): void
    {
        // On retire les anciennes lignes (remplacement complet).
        $ecolage->frais()->withTrashed()->forceDelete();

        foreach (array_values($frais) as $index => $ligne) {
            $montant = $ligne['montant'] ?? null;
            $hasContent = $montant !== null && $montant !== ''
                || !empty($ligne['poste_recette_id'])
                || !empty($ligne['libelle']);
            if (!$hasContent) {
                continue; // on ignore les lignes vides
            }

            $ecolage->frais()->create([
                'poste_recette_id' => $ligne['poste_recette_id'] ?? null,
                'plan_compte_id' => $ligne['plan_compte_id'] ?? null,
                'libelle' => $ligne['libelle'] ?? null,
                'montant' => $montant ?: 0,
                'date_limite' => $ligne['date_limite'] ?? null,
                'ordre' => $index,
                'etat' => 'actif',
            ]);
        }
    }
}
