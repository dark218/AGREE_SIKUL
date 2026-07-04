<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Parametrage\Entities\{AnneeScolaire, Classe, Section, CycleEnseignement, Ecole, Campus, Commune, Departement, Region, Pays, Quartier, TypeApprenant, CategorieApprenant};
use Modules\Academique\Entities\{Apprenant, Inscription};

class ApprenantController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:apprenants-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:apprenants-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:apprenants-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:apprenants-delete', ['only' => ['destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        $query = Apprenant::query();

        // Filtres
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nom', 'like', "%$search%")
                  ->orWhere('prenoms', 'like', "%$search%")
                  ->orWhere('matricule', 'like', "%$search%");
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $apprenants = $query->with('user', 'classe', 'section', 'cycle', 'ecole', 'campus')->paginate(10)->withQueryString();

        return Inertia::render('Academique::Apprenants/Index', [
            'apprenants' => $apprenants,
            'filters' => $request->only(['search', 'statut']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Academique::Apprenants/Create', [
            'title' => __('actions.create'),
            'classes' => Classe::whereNull('deleted_at')
                ->orderBy('libelle')
                ->orderBy('nom')
                ->get(['id', 'nom', 'libelle', 'libelle_affichage', 'code', 'ecole_id', 'campus_id'])
                ->map(fn($c) => [
                    'id' => $c->id,
                    'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'code' => $c->code,
                    'ecole_id' => $c->ecole_id,
                    'campus_id' => $c->campus_id,
                ]),
            'sections' => Section::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'cycles' => CycleEnseignement::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'ecoles' => Ecole::whereNull('deleted_at')->select('id', 'nom')->get(),
            'campuses' => Campus::whereNull('deleted_at')->select('id', 'nom')->get(),
            'anneesScolaires' => AnneeScolaire::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'typesApprenant' => TypeApprenant::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'categoriesApprenant' => CategorieApprenant::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'genres' => \Modules\Parametrage\Entities\Genre::actif()->orderBy('ordre')->get(['id', 'libelle', 'code', 'symbole', 'couleur'])->toArray(),
            'communes' => Commune::whereNull('deleted_at')->select('id', 'libelle', 'departement_id')->get(),
            'departements' => Departement::whereNull('deleted_at')->select('id', 'libelle', 'region_id', 'pays_id')->get(),
            'regions' => Region::whereNull('deleted_at')->select('id', 'libelle', 'pays_id')->get(),
            'pays' => Pays::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'quartiers' => Quartier::whereNull('deleted_at')->select('id', 'libelle', 'commune_id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        \Log::info('=== APPRENANT STORE DEBUG START ===');
        \Log::info('Request data:', $request->all());
        \Log::info('Request headers:', $request->headers->all());

        try {
            \Log::info('Validating request data...');
            $validated = $request->validate([
                'matricule' => 'required|unique:apprenants',
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'numero_inscription' => 'nullable|string|max:100',
                'email' => 'nullable|email|max:255',
                'telephone' => 'nullable|string|max:20',
                'telephone2' => 'nullable|string|max:20',
                'whatsapp1' => 'nullable|string|max:20',
                'whatsapp2' => 'nullable|string|max:20',
                'date_naissance' => 'nullable|date',
                'lieu_naissance' => 'nullable|string|max:255',
                'sexe' => 'nullable|in:M,F',
                'genre_id' => 'nullable|exists:genres,id',
                'nationalite' => 'nullable|string|max:100',
                'groupe_sanguin' => 'nullable|string|max:10',
                'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                'adresse' => 'nullable|string|max:255',
                'classe_id' => 'nullable|exists:classes,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'type_apprenant_id' => 'nullable|exists:type_apprenants,id',
                'categorie_apprenant_id' => 'nullable|exists:categorie_apprenants,id',
                'commune_naissance_id' => 'nullable|exists:communes,id',
                'departement_naissance_id' => 'nullable|exists:departements,id',
                'region_naissance_id' => 'nullable|exists:regions,id',
                'pays_naissance_id' => 'nullable|exists:pays,id',
                'ecole_precedente' => 'nullable|string|max:255',
                'classe_precedente' => 'nullable|string|max:100',
                'est_interne' => 'nullable|boolean',
                'batiment' => 'nullable|string|max:100',
                'etage' => 'nullable|string|max:50',
                'chambre' => 'nullable|string|max:50',
                'numero_lit' => 'nullable|string|max:50',
                'nom_pere' => 'nullable|string|max:255',
                'nom_mere' => 'nullable|string|max:255',
                'nom_tuteur' => 'nullable|string|max:255',
                'nom_responsable_legal' => 'nullable|string|max:255',
                'quartier_id' => 'nullable|exists:quartiers,id',
                'commune_residence_id' => 'nullable|exists:communes,id',
                'departement_residence_id' => 'nullable|exists:departements,id',
                'region_residence_id' => 'nullable|exists:regions,id',
                'pays_residence_id' => 'nullable|exists:pays,id',
                'arrondissement' => 'nullable|string|max:100',
                'ville' => 'nullable|string|max:100',
                'code_postal' => 'nullable|string|max:20',
                'boite_postal' => 'nullable|string|max:20',
                'date_entree_ecole' => 'nullable|date',
                'date_depart_ecole' => 'nullable|date',
                'motif_depart_ecole' => 'nullable|string|max:500',
                'statut' => 'required|in:actif,suspendu,exclu,diplome,abandonne',
            ]);

            \Log::info('Validation passed!', ['validated_data' => $validated]);

            // Upload de la photo si présent
            if ($request->hasFile('photo')) {
                $validated['photo'] = $request->file('photo')->store('apprenants/photos', 'public');
            } else {
                unset($validated['photo']);
            }

            $apprenant = Apprenant::create($validated);
            \Log::info('Apprenant created successfully!', ['apprenant_id' => $apprenant->id]);

            // Redirection conditionnelle selon le bouton cliqué
            $next = $request->input('next_action');
            if ($next === 'inscription') {
                return redirect()->route('academique.inscriptions.create', ['apprenant_id' => $apprenant->id])
                    ->with('success', __('messages.created_successfully') . ' — ' . ($apprenant->prenoms . ' ' . $apprenant->nom) . ' : passez à l\'inscription.');
            }
            if ($next === 'dossier') {
                return redirect()->route('academique.dossiers_apprenants.create', ['apprenant_id' => $apprenant->id])
                    ->with('success', __('messages.created_successfully') . ' — ' . ($apprenant->prenoms . ' ' . $apprenant->nom) . ' : complétez son dossier.');
            }

            return redirect()->route('academique.apprenants.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            \Log::error('=== VALIDATION ERROR ===');
            \Log::error('Validation errors:', $ve->errors());
            \Log::error('=== END VALIDATION ERROR ===');
            return back()->withErrors($ve->errors());
        } catch (\Throwable $th) {
            \Log::error('=== APPRENANT STORE ERROR ===');
            \Log::error('Error type: ' . get_class($th));
            \Log::error('Error message: ' . $th->getMessage());
            \Log::error('Error trace: ' . $th->getTraceAsString());
            \Log::error('=== END ERROR ===');
            log_error("GestionApprenants", "ApprenantController::store", $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function show(Apprenant $apprenant)
    {
        $apprenant->load(
            'user', 'classe', 'section', 'cycle', 'ecole', 'campus', 'anneeScolaire',
            'typeApprenant', 'categorieApprenant', 'communeNaissance', 'departementNaissance',
            'regionNaissance', 'paysNaissance', 'quartier', 'communeResidence',
            'departementResidence', 'regionResidence', 'paysResidence',
            // Contacts humains (parents, tuteurs, accompagnateurs) — récupérés en Show pour la nouvelle section
            'parents:id,pere_nom,pere_prenoms,mere_nom,mere_prenoms,pere_telephone_1,mere_telephone_1,pere_email_1,mere_email_1',
            // La table tuteurs n'a que user_id + relation/profession — les infos identité (nom/prénoms/tel/email) sont dans users
            'tuteurs:id,user_id,relation,profession',
            'tuteurs.user:id,nom,prenoms,email,telephone',
            'accompagnateurs:id,accompagnant1_nom,accompagnant1_prenoms,accompagnant2_nom,accompagnant2_prenoms,accompagnant3_nom,accompagnant3_prenoms',
        );

        return Inertia::render('Academique::Apprenants/Show', [
            'title' => __('actions.view'),
            'apprenant' => $apprenant,
            'classes' => Classe::whereNull('deleted_at')
                ->orderBy('libelle')
                ->orderBy('nom')
                ->get(['id', 'nom', 'libelle', 'libelle_affichage', 'code', 'ecole_id', 'campus_id'])
                ->map(fn($c) => [
                    'id' => $c->id,
                    'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'code' => $c->code,
                    'ecole_id' => $c->ecole_id,
                    'campus_id' => $c->campus_id,
                ]),
            'sections' => Section::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'cycles' => CycleEnseignement::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'ecoles' => Ecole::whereNull('deleted_at')->select('id', 'nom')->get(),
            'campuses' => Campus::whereNull('deleted_at')->select('id', 'nom')->get(),
            'anneesScolaires' => AnneeScolaire::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'typesApprenant' => TypeApprenant::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'categoriesApprenant' => CategorieApprenant::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'genres' => \Modules\Parametrage\Entities\Genre::actif()->orderBy('ordre')->get(['id', 'libelle', 'code', 'symbole', 'couleur'])->toArray(),
            'communes' => Commune::whereNull('deleted_at')->select('id', 'libelle', 'departement_id')->get(),
            'departements' => Departement::whereNull('deleted_at')->select('id', 'libelle', 'region_id', 'pays_id')->get(),
            'regions' => Region::whereNull('deleted_at')->select('id', 'libelle', 'pays_id')->get(),
            'pays' => Pays::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'quartiers' => Quartier::whereNull('deleted_at')->select('id', 'libelle', 'commune_id')->get(),
        ]);
    }

    public function edit(Apprenant $apprenant)
    {
        return Inertia::render('Academique::Apprenants/Edit', [
            'title' => __('actions.edit'),
            'apprenant' => $apprenant->load('user'),
            'classes' => Classe::whereNull('deleted_at')
                ->orderBy('libelle')
                ->orderBy('nom')
                ->get(['id', 'nom', 'libelle', 'libelle_affichage', 'code', 'ecole_id', 'campus_id'])
                ->map(fn($c) => [
                    'id' => $c->id,
                    'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                    'code' => $c->code,
                    'ecole_id' => $c->ecole_id,
                    'campus_id' => $c->campus_id,
                ]),
            'sections' => Section::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'cycles' => CycleEnseignement::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'ecoles' => Ecole::whereNull('deleted_at')->select('id', 'nom')->get(),
            'campuses' => Campus::whereNull('deleted_at')->select('id', 'nom')->get(),
            'anneesScolaires' => AnneeScolaire::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'typesApprenant' => TypeApprenant::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'categoriesApprenant' => CategorieApprenant::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'genres' => \Modules\Parametrage\Entities\Genre::actif()->orderBy('ordre')->get(['id', 'libelle', 'code', 'symbole', 'couleur'])->toArray(),
            'communes' => Commune::whereNull('deleted_at')->select('id', 'libelle', 'departement_id')->get(),
            'departements' => Departement::whereNull('deleted_at')->select('id', 'libelle', 'region_id', 'pays_id')->get(),
            'regions' => Region::whereNull('deleted_at')->select('id', 'libelle', 'pays_id')->get(),
            'pays' => Pays::whereNull('deleted_at')->select('id', 'libelle')->get(),
            'quartiers' => Quartier::whereNull('deleted_at')->select('id', 'libelle', 'commune_id')->get(),
        ]);
    }

    public function update(Request $request, Apprenant $apprenant)
    {
        try {
            $validated = $request->validate([
                'matricule' => 'required|unique:apprenants,matricule,' . $apprenant->id,
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'numero_inscription' => 'nullable|string|max:100',
                'email' => 'nullable|email|max:255',
                'telephone' => 'nullable|string|max:20',
                'telephone2' => 'nullable|string|max:20',
                'whatsapp1' => 'nullable|string|max:20',
                'whatsapp2' => 'nullable|string|max:20',
                'date_naissance' => 'nullable|date',
                'lieu_naissance' => 'nullable|string|max:255',
                'sexe' => 'nullable|in:M,F',
                'genre_id' => 'nullable|exists:genres,id',
                'nationalite' => 'nullable|string|max:100',
                'groupe_sanguin' => 'nullable|string|max:10',
                'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                'adresse' => 'nullable|string|max:255',
                'classe_id' => 'nullable|exists:classes,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'type_apprenant_id' => 'nullable|exists:type_apprenants,id',
                'categorie_apprenant_id' => 'nullable|exists:categorie_apprenants,id',
                'commune_naissance_id' => 'nullable|exists:communes,id',
                'departement_naissance_id' => 'nullable|exists:departements,id',
                'region_naissance_id' => 'nullable|exists:regions,id',
                'pays_naissance_id' => 'nullable|exists:pays,id',
                'ecole_precedente' => 'nullable|string|max:255',
                'classe_precedente' => 'nullable|string|max:100',
                'est_interne' => 'nullable|boolean',
                'batiment' => 'nullable|string|max:100',
                'etage' => 'nullable|string|max:50',
                'chambre' => 'nullable|string|max:50',
                'numero_lit' => 'nullable|string|max:50',
                'nom_pere' => 'nullable|string|max:255',
                'nom_mere' => 'nullable|string|max:255',
                'nom_tuteur' => 'nullable|string|max:255',
                'nom_responsable_legal' => 'nullable|string|max:255',
                'quartier_id' => 'nullable|exists:quartiers,id',
                'commune_residence_id' => 'nullable|exists:communes,id',
                'departement_residence_id' => 'nullable|exists:departements,id',
                'region_residence_id' => 'nullable|exists:regions,id',
                'pays_residence_id' => 'nullable|exists:pays,id',
                'arrondissement' => 'nullable|string|max:100',
                'ville' => 'nullable|string|max:100',
                'code_postal' => 'nullable|string|max:20',
                'boite_postal' => 'nullable|string|max:20',
                'date_entree_ecole' => 'nullable|date',
                'date_depart_ecole' => 'nullable|date',
                'motif_depart_ecole' => 'nullable|string|max:500',
                'statut' => 'required|in:actif,suspendu,exclu,diplome,abandonne',
            ]);

            // Upload de la nouvelle photo : remplace l'ancienne et supprime le fichier
            if ($request->hasFile('photo')) {
                if ($apprenant->photo && \Storage::disk('public')->exists($apprenant->photo)) {
                    \Storage::disk('public')->delete($apprenant->photo);
                }
                $validated['photo'] = $request->file('photo')->store('apprenants/photos', 'public');
            } else {
                // Pas de nouveau fichier → on ne touche pas au champ photo existant
                unset($validated['photo']);
            }

            $apprenant->update($validated);

            return redirect()->route('academique.apprenants.show', $apprenant)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("GestionApprenants", "ApprenantController::update", $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function statut(Apprenant $apprenant)
    {
        try {
            $apprenant->statut = $apprenant->statut === 'actif' ? 'inactif' : 'actif';
            $apprenant->save();

            return redirect()->route('academique.apprenants.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("GestionApprenants", "ApprenantController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function apiShow(Apprenant $apprenant)
    {
        try {
            $apprenant->load('classe', 'ecole', 'campus', 'section', 'cycle', 'anneeScolaire');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $apprenant->id,
                    'matricule' => $apprenant->matricule,
                    'classe_id' => $apprenant->classe_id,
                    'classe' => $apprenant->classe ? [
                        'id' => $apprenant->classe->id,
                        'nom' => $apprenant->classe->nom,
                    ] : null,
                    'ecole_id' => $apprenant->ecole_id,
                    'ecole' => $apprenant->ecole ? [
                        'id' => $apprenant->ecole->id,
                        'nom' => $apprenant->ecole->nom,
                    ] : null,
                    'campus_id' => $apprenant->campus_id,
                    'campus' => $apprenant->campus ? [
                        'id' => $apprenant->campus->id,
                        'nom' => $apprenant->campus->nom,
                    ] : null,
                    'section_id' => $apprenant->section_id,
                    'section' => $apprenant->section ? [
                        'id' => $apprenant->section->id,
                        'libelle' => $apprenant->section->libelle,
                    ] : null,
                    'cycle_id' => $apprenant->cycle_id,
                    'cycle' => $apprenant->cycle ? [
                        'id' => $apprenant->cycle->id,
                        'libelle' => $apprenant->cycle->libelle,
                    ] : null,
                    'annee_scolaire_id' => $apprenant->annee_scolaire_id,
                    'annee_scolaire' => $apprenant->anneeScolaire ? [
                        'id' => $apprenant->anneeScolaire->id,
                        'libelle' => $apprenant->anneeScolaire->libelle,
                    ] : null,
                ]
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ ApprenantController::apiShow ERROR', [
                'apprenant_id' => $apprenant->id,
                'error' => $th->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(Apprenant $apprenant)
    {
        try {
            if ($apprenant->trashed()) {
                $apprenant->restore();
            } else {
                $apprenant->delete();
            }

            return redirect()->route('academique.apprenants.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("GestionApprenants", "ApprenantController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    /**
     * API: Calculer la moyenne d'un apprenant pour une matière (Phase 1)
     * Utilisé par MoyenneMatiereForm pour auto-calculer les moyennes
     */
    public function calculateMoyennes(Request $request)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|integer|exists:apprenants,id',
                'matiere_id' => 'nullable|integer|exists:matieres,id',
            ]);

            $apprenantId = $validated['apprenant_id'];
            $matiereId = $validated['matiere_id'] ?? null;

            $apprenant = Apprenant::findOrFail($apprenantId);

            // Si matiere_id passé: calculer juste pour cette matière
            if (!empty($matiereId)) {
                $notes = \Modules\Academique\Entities\Note::where('apprenant_id', $apprenantId)
                    ->where('matiere_id', $matiereId)
                    ->where('statut', 'validee')
                    ->with(['evaluation'])
                    ->get();

                if ($notes->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucune note validée trouvée pour cette matière',
                    ]);
                }

                // Calculer la moyenne simple
                $moyenneSimple = $notes->avg('note') ?? 0;

                // Déterminer l'appréciation
                $appreciation = $this->getAppreciation($moyenneSimple);

                // Formater les notes
                $notesFormatted = $notes->map(function ($note) {
                    return [
                        'id' => $note->id,
                        'matiere_id' => $note->matiere_id,
                        'evaluation' => $note->evaluation?->libelle ?? 'N/A',
                        'note' => $note->note,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'data' => [
                        'moyenne' => round($moyenneSimple, 2),
                        'appreciation' => $appreciation,
                        'notes' => $notesFormatted,
                        'total_notes' => $notes->count(),
                    ],
                ]);
            }

            // Si pas de matiere_id: retourner toutes les moyennes de l'apprenant
            return response()->json([
                'success' => false,
                'message' => 'matiere_id est requis',
            ]);

        } catch (\Throwable $th) {
            \Log::error('ApprenantController::calculateMoyennes', [
                'error' => $th->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Déterminer l'appréciation selon la moyenne
     */
    private function getAppreciation($moyenne)
    {
        if ($moyenne >= 18) {
            return 'excellent';
        } elseif ($moyenne >= 16) {
            return 'bien';
        } elseif ($moyenne >= 14) {
            return 'assez';
        } elseif ($moyenne >= 12) {
            return 'moyen';
        } else {
            return 'faible';
        }
    }
}
