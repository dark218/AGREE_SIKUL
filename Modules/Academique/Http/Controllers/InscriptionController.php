<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Inscription;
use Modules\Academique\Entities\Apprenant;
use Modules\Parametrage\Entities\{AnneeScolaire, Classe, Ecole, Campus, Institution};

class InscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:inscriptions-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:inscriptions-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:inscriptions-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:inscriptions-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Inscription::query();

            // Filtres
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('apprenant.user', function ($q) use ($search) {
                    $q->where('nom', 'like', "%$search%")
                      ->orWhere('prenoms', 'like', "%$search%");
                })->orWhere('numero_inscription', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $inscriptions = $query->with(['apprenant.user', 'classe', 'anneeScolaire'])
                ->paginate(10)->withQueryString()
                ->through(function($inscription) {
                    $data = $inscription->toArray();

                    // Format apprenant name for display
                    if ($inscription->apprenant) {
                        if ($inscription->apprenant->user) {
                            $data['apprenant_display'] = $inscription->apprenant->user->prenoms . ' ' . $inscription->apprenant->user->nom;
                        } else {
                            $data['apprenant_display'] = ($inscription->apprenant->prenoms ? $inscription->apprenant->prenoms . ' ' : '') . ($inscription->apprenant->nom ?: 'Sans nom');
                        }
                    }

                    // Format date_inscription
                    if ($inscription->date_inscription) {
                        $data['date_inscription'] = $inscription->date_inscription->format('Y-m-d');
                    }

                    return $data;
                });

            return Inertia::render('Academique::Inscriptions/Index', [
                'title' => __('common.inscriptions'),
                'inscriptions' => $inscriptions,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "InscriptionController::index", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create(Request $request)
    {
        try {
            \Log::info('InscriptionController::create - Starting');

            $apprenants = Apprenant::with('user')
                ->select('id', 'user_id', 'matricule', 'numero_inscription', 'nom', 'prenoms', 'classe_id', 'ecole_id', 'campus_id', 'section_id', 'cycle_id', 'pays_residence_id')
                ->get()
                ->map(function ($apprenant) {
                    if ($apprenant->user) {
                        $libelle = $apprenant->user->prenoms . ' ' . $apprenant->user->nom . ' (' . $apprenant->matricule . ')';
                    } else {
                        $libelle = ($apprenant->prenoms ? $apprenant->prenoms . ' ' : '') . ($apprenant->nom ?: 'Sans nom') . ' (' . $apprenant->matricule . ')';
                    }

                    return [
                        'id' => $apprenant->id,
                        'libelle' => $libelle,
                        'numero_inscription' => $apprenant->numero_inscription,
                        'classe_id' => $apprenant->classe_id,
                        'ecole_id' => $apprenant->ecole_id,
                        'campus_id' => $apprenant->campus_id,
                        'section_id' => $apprenant->section_id,
                        'cycle_id' => $apprenant->cycle_id,
                        'pays_id' => $apprenant->pays_residence_id,
                    ];
                })->values()->toArray();

            \Log::info('InscriptionController::create - Apprenants loaded: ' . count($apprenants));

            $classes = Classe::with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
                ->select('id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'ecole_id' => $c->ecole_id, 'ecole_nom' => $c->ecole?->nom,
                    'campus_id' => $c->campus_id, 'campus_nom' => $c->campus?->nom,
                    'niveau_id' => $c->niveau_id, 'niveau_libelle' => $c->niveau?->libelle,
                    'section_id' => $c->section_id, 'section_libelle' => $c->section?->libelle,
                    'cycle_id' => $c->cycle_id, 'cycle_libelle' => $c->cycle?->libelle,
                    'annee_scolaire_id' => $c->annee_scolaire_id, 'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
                ])->toArray();
            \Log::info('InscriptionController::create - Classes loaded: ' . count($classes));

            $anneesScolaires = AnneeScolaire::select('id', 'libelle')->get()->toArray();
            \Log::info('InscriptionController::create - AnneeScolaires loaded: ' . count($anneesScolaires));

            $ecoles = Ecole::select('id', 'nom')->get()->toArray();
            \Log::info('InscriptionController::create - Ecoles loaded: ' . count($ecoles));

            $campuses = Campus::select('id', 'nom')->get()->toArray();
            \Log::info('InscriptionController::create - Campuses loaded: ' . count($campuses));

            $institutions = Institution::select('id', 'nom')->get()->toArray();
            \Log::info('InscriptionController::create - Institutions loaded: ' . count($institutions));

            \Log::info('InscriptionController::create - Rendering Create page');

            // Préfill depuis ?apprenant_id=X (provenance: liste apprenants ou bouton "Enregistrer et inscrire")
            $prefill = null;
            if ($request->filled('apprenant_id')) {
                $aid = (int) $request->input('apprenant_id');
                $prefill = collect($apprenants)->firstWhere('id', $aid);
            }

            return Inertia::render('Academique::Inscriptions/Create', [
                'title' => __('actions.create'),
                'apprenants' => $apprenants,
                'classes' => $classes,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'institutions' => $institutions,
                'typesInscriptions' => \Modules\Parametrage\Entities\TypeInscription::actif()->orderBy('ordre')->get(['id', 'code', 'libelle'])->toArray(),
                'prefill' => $prefill,
            ]);
        } catch (\Throwable $th) {
            \Log::error('InscriptionController::create ERROR: ' . $th->getMessage());
            \Log::error('Stack trace: ' . $th->getTraceAsString());
            log_error("Academique", "InscriptionController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            // Auto-générer numero_inscription si non fourni (format: INS-YYYY-XXXXX)
            if (!$request->filled('numero_inscription')) {
                $request->merge([
                    'numero_inscription' => $this->generateNumeroInscription(),
                ]);
            }

            $validated = $request->validate([
                'apprenant_id'           => 'required|exists:apprenants,id',
                'classe_id'              => 'required|exists:classes,id',
                'annee_scolaire_id'      => 'required|exists:annees_scolaires,id',
                'ecole_id'               => 'nullable|exists:ecoles,id',
                'campus_id'              => 'nullable|exists:campuses,id',
                'institution_id'         => 'nullable|exists:institutions,id',
                'date_inscription'       => 'required|date',
                'numero_inscription'     => 'nullable|string|max:100|unique:inscriptions,numero_inscription',
                'type_inscription'       => 'required|in:nouveau,redoublement,transfert,reprise',
                'statut'                 => 'required|in:en_attente,validee,rejetee,suspendue',
                'premiere_inscription'   => 'boolean',
                'frais_dossier'          => 'nullable|numeric|min:0',
                'frais_inscription'      => 'nullable|numeric|min:0',
                'frais_scolarite'        => 'nullable|numeric|min:0',
                'frais_dossier_paye'     => 'nullable|numeric|min:0',
                'frais_inscription_paye' => 'nullable|numeric|min:0',
                'frais_scolarite_paye'   => 'nullable|numeric|min:0',
                'dossier_complet'        => 'boolean',
                'fiche_inscription'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'carnet_vaccination'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'photos_4x4'             => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
                'copie_acte_naissance'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'piece1'                 => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'piece2'                 => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'piece3'                 => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'piece4'                 => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            // Handle file uploads
            $documentFields = ['fiche_inscription', 'carnet_vaccination', 'photos_4x4', 'copie_acte_naissance', 'piece1', 'piece2', 'piece3', 'piece4'];
            foreach ($documentFields as $field) {
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    $validated[$field] = $request->file($field)->store('inscriptions', 'public');
                }
            }

            Inscription::create($validated);

            return redirect()->route('academique.inscriptions.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "InscriptionController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(Inscription $inscription)
    {
        try {
            $inscription->load(['apprenant.user', 'classe', 'anneeScolaire', 'ecole', 'campus', 'institution']);

            \Log::info('🔍 InscriptionController::show() - Date inscription:', [
                'date_inscription_raw' => $inscription->date_inscription,
                'date_inscription_type' => gettype($inscription->date_inscription),
            ]);

            $apprenants = Apprenant::with('user')
                ->select('id', 'user_id', 'matricule', 'numero_inscription', 'nom', 'prenoms', 'classe_id', 'ecole_id', 'campus_id', 'section_id', 'cycle_id', 'pays_residence_id')
                ->get()
                ->map(function ($apprenant) {
                    if ($apprenant->user) {
                        $libelle = $apprenant->user->prenoms . ' ' . $apprenant->user->nom . ' (' . $apprenant->matricule . ')';
                    } else {
                        $libelle = ($apprenant->prenoms ? $apprenant->prenoms . ' ' : '') . ($apprenant->nom ?: 'Sans nom') . ' (' . $apprenant->matricule . ')';
                    }

                    return [
                        'id' => $apprenant->id,
                        'libelle' => $libelle,
                        'numero_inscription' => $apprenant->numero_inscription,
                        'classe_id' => $apprenant->classe_id,
                        'ecole_id' => $apprenant->ecole_id,
                        'campus_id' => $apprenant->campus_id,
                        'section_id' => $apprenant->section_id,
                        'cycle_id' => $apprenant->cycle_id,
                        'pays_id' => $apprenant->pays_residence_id,
                    ];
                })->values()->toArray();

            $classes = Classe::with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
                ->select('id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'ecole_id' => $c->ecole_id, 'ecole_nom' => $c->ecole?->nom,
                    'campus_id' => $c->campus_id, 'campus_nom' => $c->campus?->nom,
                    'niveau_id' => $c->niveau_id, 'niveau_libelle' => $c->niveau?->libelle,
                    'section_id' => $c->section_id, 'section_libelle' => $c->section?->libelle,
                    'cycle_id' => $c->cycle_id, 'cycle_libelle' => $c->cycle?->libelle,
                    'annee_scolaire_id' => $c->annee_scolaire_id, 'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
                ])->toArray();
            $anneesScolaires = AnneeScolaire::select('id', 'libelle')->get()->toArray();
            $ecoles = Ecole::select('id', 'nom')->get()->toArray();
            $campuses = Campus::select('id', 'nom')->get()->toArray();
            $institutions = Institution::select('id', 'nom')->get()->toArray();

            return Inertia::render('Academique::Inscriptions/Show', [
                'title' => __('actions.view'),
                'inscription' => $inscription,
                'apprenants' => $apprenants,
                'classes' => $classes,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'institutions' => $institutions,
                'typesInscriptions' => \Modules\Parametrage\Entities\TypeInscription::actif()->orderBy('ordre')->get(['id', 'code', 'libelle'])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "InscriptionController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit(Inscription $inscription)
    {
        try {
            \Log::info('🔍 InscriptionController::edit() - Date inscription:', [
                'date_inscription_raw' => $inscription->date_inscription,
                'date_inscription_type' => gettype($inscription->date_inscription),
            ]);

            $apprenants = Apprenant::with('user')
                ->select('id', 'user_id', 'matricule', 'numero_inscription', 'nom', 'prenoms', 'classe_id', 'ecole_id', 'campus_id', 'section_id', 'cycle_id', 'pays_residence_id')
                ->get()
                ->map(function ($apprenant) {
                    if ($apprenant->user) {
                        $libelle = $apprenant->user->prenoms . ' ' . $apprenant->user->nom . ' (' . $apprenant->matricule . ')';
                    } else {
                        $libelle = ($apprenant->prenoms ? $apprenant->prenoms . ' ' : '') . ($apprenant->nom ?: 'Sans nom') . ' (' . $apprenant->matricule . ')';
                    }

                    return [
                        'id' => $apprenant->id,
                        'libelle' => $libelle,
                        'numero_inscription' => $apprenant->numero_inscription,
                        'classe_id' => $apprenant->classe_id,
                        'ecole_id' => $apprenant->ecole_id,
                        'campus_id' => $apprenant->campus_id,
                        'section_id' => $apprenant->section_id,
                        'cycle_id' => $apprenant->cycle_id,
                        'pays_id' => $apprenant->pays_residence_id,
                    ];
                })->values()->toArray();

            $classes = Classe::with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
                ->select('id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'ecole_id' => $c->ecole_id, 'ecole_nom' => $c->ecole?->nom,
                    'campus_id' => $c->campus_id, 'campus_nom' => $c->campus?->nom,
                    'niveau_id' => $c->niveau_id, 'niveau_libelle' => $c->niveau?->libelle,
                    'section_id' => $c->section_id, 'section_libelle' => $c->section?->libelle,
                    'cycle_id' => $c->cycle_id, 'cycle_libelle' => $c->cycle?->libelle,
                    'annee_scolaire_id' => $c->annee_scolaire_id, 'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
                ])->toArray();
            $anneesScolaires = AnneeScolaire::select('id', 'libelle')->get()->toArray();
            $ecoles = Ecole::select('id', 'nom')->get()->toArray();
            $campuses = Campus::select('id', 'nom')->get()->toArray();
            $institutions = Institution::select('id', 'nom')->get()->toArray();

            return Inertia::render('Academique::Inscriptions/Edit', [
                'title' => __('actions.edit'),
                'inscription' => $inscription->load(['apprenant.user', 'classe', 'anneeScolaire', 'ecole', 'campus', 'institution']),
                'apprenants' => $apprenants,
                'classes' => $classes,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'institutions' => $institutions,
                'typesInscriptions' => \Modules\Parametrage\Entities\TypeInscription::actif()->orderBy('ordre')->get(['id', 'code', 'libelle'])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "InscriptionController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, Inscription $inscription)
    {
        try {
            \Log::info('🚀 InscriptionController::update START', [
                'inscription_id' => $inscription->id,
                'request_all' => $request->all(),
                'apprenant_id_received' => $request->input('apprenant_id'),
                'apprenant_id_type' => gettype($request->input('apprenant_id')),
            ]);

            $validated = $request->validate([
                'apprenant_id'           => 'required|exists:apprenants,id',
                'classe_id'              => 'required|exists:classes,id',
                'annee_scolaire_id'      => 'required|exists:annees_scolaires,id',
                'ecole_id'               => 'nullable|exists:ecoles,id',
                'campus_id'              => 'nullable|exists:campuses,id',
                'institution_id'         => 'nullable|exists:institutions,id',
                'date_inscription'       => 'required|date',
                'numero_inscription'     => 'nullable|string|max:100|unique:inscriptions,numero_inscription,' . $inscription->id,
                'type_inscription'       => 'required|in:nouveau,redoublement,transfert,reprise',
                'statut'                 => 'required|in:en_attente,validee,rejetee,suspendue',
                'premiere_inscription'   => 'boolean',
                'frais_dossier'          => 'nullable|numeric|min:0',
                'frais_inscription'      => 'nullable|numeric|min:0',
                'frais_scolarite'        => 'nullable|numeric|min:0',
                'frais_dossier_paye'     => 'nullable|numeric|min:0',
                'frais_inscription_paye' => 'nullable|numeric|min:0',
                'frais_scolarite_paye'   => 'nullable|numeric|min:0',
                'dossier_complet'        => 'boolean',
                'fiche_inscription'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'carnet_vaccination'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'photos_4x4'             => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
                'copie_acte_naissance'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'piece1'                 => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'piece2'                 => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'piece3'                 => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'piece4'                 => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            // Handle file uploads
            $documentFields = ['fiche_inscription', 'carnet_vaccination', 'photos_4x4', 'copie_acte_naissance', 'piece1', 'piece2', 'piece3', 'piece4'];
            foreach ($documentFields as $field) {
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    // Delete old file if exists
                    if ($inscription->$field) {
                        \Storage::disk('public')->delete($inscription->$field);
                    }
                    $validated[$field] = $request->file($field)->store('inscriptions', 'public');
                }
            }

            $inscription->update($validated);

            return redirect()->route('academique.inscriptions.show', $inscription)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "InscriptionController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(Inscription $inscription)
    {
        try {
            $inscription->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "InscriptionController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(Inscription $inscription)
    {
        try {
            $newStatut = $inscription->statut === 'validee' ? 'suspendue' : 'validee';
            $inscription->update(['statut' => $newStatut]);

            return back()->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "InscriptionController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    /**
     * Génère un numéro d'inscription unique au format INS-YYYY-NNNNN
     * où NNNNN est un compteur séquentiel de l'année.
     */
    private function generateNumeroInscription(): string
    {
        $year = date('Y');
        $prefix = 'INS-' . $year . '-';

        // Compteur basé sur le dernier numéro de l'année
        $lastNumero = Inscription::withoutGlobalScopes()
            ->where('numero_inscription', 'like', $prefix . '%')
            ->orderByDesc('numero_inscription')
            ->value('numero_inscription');

        if ($lastNumero && preg_match('/-(\d+)$/', $lastNumero, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    }
}
