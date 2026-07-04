<?php

namespace Modules\Personnel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Personnel\Entities\StudentParent;
use Modules\Academique\Entities\Apprenant;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\Institution;
use Modules\Parametrage\Entities\Campus;

class ParentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:parents-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:parents-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parents-edit', ['only' => ['edit', 'update', 'statut']]);
        $this->middleware('permission.check:parents-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): Response
    {
        try {
            $query = StudentParent::with([
                'apprenant',
                'apprenants:id,nom,prenoms,matricule',
                'classe',
                'ecole',
            ])->whereNull('deleted_at');

            // Filter by apprenant search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('prenoms', 'like', "%{$search}%");
                });
            }

            // Filter by school
            if ($request->filled('ecole_id')) {
                $query->where('ecole_id', $request->ecole_id);
            }

            // Filter by status
            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $parents = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

            $ecoles = Ecole::select('id', 'nom as libelle')->orderBy('nom')->get();

            return Inertia::render('Personnel/Parents/Index', [
                'parents' => $parents,
                'ecoles' => $ecoles,
                'filters' => $request->only(['search', 'ecole_id', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error("Personnel", "ParentController::index", $th->getMessage());
            return Inertia::render('Personnel/Parents/Index', [
                'parents' => [],
                'ecoles' => [],
                'filters' => [],
            ]);
        }
    }

    public function create()
    {
        try {
            \Log::info('🔵 ParentController::create - Starting');

            // Enrichi avec les noms de contacts pour auto-fill dans le form parent
            $apprenants = Apprenant::select(
                    'id', 'nom', 'prenoms', 'matricule',
                    'classe_id', 'ecole_id', 'institution_id', 'campus_id',
                    'nom_pere', 'nom_mere', 'nom_tuteur', 'nom_responsable_legal',
                    'telephone', 'email', 'adresse'
                )
                ->selectRaw("CONCAT(prenoms, ' ', nom, ' (', COALESCE(matricule, ''), ')') as libelle")
                ->whereNull('deleted_at')
                ->orderBy('nom')
                ->get();

            $classes = Classe::select('id', 'nom as libelle')->orderBy('nom')->get();
            $ecoles = Ecole::select('id', 'nom as libelle')->orderBy('nom')->get();
            $institutions = Institution::select('id', 'nom as libelle')->orderBy('nom')->get();
            $campuses = Campus::select('id', 'nom as libelle')->orderBy('nom')->get();

            \Log::info('✓ ParentController::create - Data fetched', [
                'apprenants' => $apprenants->count(),
                'classes' => $classes->count(),
                'ecoles' => $ecoles->count(),
                'institutions' => $institutions->count(),
                'campuses' => $campuses->count(),
            ]);

            return Inertia::render('Personnel/Parents/Create', [
                'apprenants' => $apprenants,
                'classes' => $classes,
                'ecoles' => $ecoles,
                'institutions' => $institutions,
                'campuses' => $campuses,
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ ParentController::create - ERROR', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
            log_error("Personnel", "ParentController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                // Legacy 1-1 (rétro-compat, sera retiré) — remplacé par apprenant_ids
                'apprenant_id' => 'nullable|exists:apprenants,id',
                // Nouveau : multi-apprenants (fratrie même école)
                'apprenant_ids' => ['nullable', 'array'],
                'apprenant_ids.*' => ['integer', 'exists:apprenants,id'],
                'lien_parente' => ['nullable', 'array'],
                'lien_parente.*' => ['nullable', 'string', 'in:pere,mere,tuteur_legal,autre'],
                'classe_id' => 'nullable|exists:classes,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'institution_id' => 'nullable|exists:institutions,id',
                'campus_id' => 'nullable|exists:campuses,id',
                // Father fields
                'pere_nom' => 'nullable|string|max:255',
                'pere_prenoms' => 'nullable|string|max:255',
                'pere_nom_complet' => 'nullable|string|max:255',
                'pere_profession' => 'nullable|string|max:255',
                'pere_organisation_travail' => 'nullable|string|max:255',
                'pere_ville_travail' => 'nullable|string|max:255',
                'pere_pays_travail' => 'nullable|string|max:255',
                'pere_adresse_residence' => 'nullable|string',
                'pere_quartier' => 'nullable|string|max:255',
                'pere_commune' => 'nullable|string|max:255',
                'pere_departement' => 'nullable|string|max:255',
                'pere_region' => 'nullable|string|max:255',
                'pere_code_postal' => 'nullable|string|max:20',
                'pere_boite_postal' => 'nullable|string|max:255',
                'pere_telephone_1' => 'nullable|string|max:20',
                'pere_telephone_2' => 'nullable|string|max:20',
                'pere_whatsapp_1' => 'nullable|string|max:20',
                'pere_whatsapp_2' => 'nullable|string|max:20',
                'pere_email_1' => 'nullable|email|max:255',
                'pere_email_2' => 'nullable|email|max:255',
                // Mother fields
                'mere_nom' => 'nullable|string|max:255',
                'mere_prenoms' => 'nullable|string|max:255',
                'mere_nom_complet' => 'nullable|string|max:255',
                'mere_profession' => 'nullable|string|max:255',
                'mere_organisation_travail' => 'nullable|string|max:255',
                'mere_ville_travail' => 'nullable|string|max:255',
                'mere_pays_travail' => 'nullable|string|max:255',
                'mere_adresse_residence' => 'nullable|string',
                'mere_quartier' => 'nullable|string|max:255',
                'mere_commune' => 'nullable|string|max:255',
                'mere_departement' => 'nullable|string|max:255',
                'mere_region' => 'nullable|string|max:255',
                'mere_code_postal' => 'nullable|string|max:20',
                'mere_boite_postal' => 'nullable|string|max:255',
                'mere_telephone_1' => 'nullable|string|max:20',
                'mere_telephone_2' => 'nullable|string|max:20',
                'mere_whatsapp_1' => 'nullable|string|max:20',
                'mere_whatsapp_2' => 'nullable|string|max:20',
                'mere_email_1' => 'nullable|email|max:255',
                'mere_email_2' => 'nullable|email|max:255',
                // Guardian 1 fields
                'tuteur1_nom' => 'nullable|string|max:255',
                'tuteur1_prenoms' => 'nullable|string|max:255',
                'tuteur1_nom_complet' => 'nullable|string|max:255',
                'tuteur1_profession' => 'nullable|string|max:255',
                'tuteur1_organisation_travail' => 'nullable|string|max:255',
                'tuteur1_ville_travail' => 'nullable|string|max:255',
                'tuteur1_pays_travail' => 'nullable|string|max:255',
                'tuteur1_adresse_residence' => 'nullable|string',
                'tuteur1_quartier' => 'nullable|string|max:255',
                'tuteur1_commune' => 'nullable|string|max:255',
                'tuteur1_arrondissement' => 'nullable|string|max:255',
                'tuteur1_ville' => 'nullable|string|max:255',
                'tuteur1_departement' => 'nullable|string|max:255',
                'tuteur1_region' => 'nullable|string|max:255',
                'tuteur1_pays' => 'nullable|string|max:255',
                'tuteur1_code_postal' => 'nullable|string|max:20',
                'tuteur1_boite_postal' => 'nullable|string|max:255',
                'tuteur1_telephone_1' => 'nullable|string|max:20',
                'tuteur1_telephone_2' => 'nullable|string|max:20',
                'tuteur1_email' => 'nullable|email|max:255',
                'tuteur1_whatsapp_1' => 'nullable|string|max:20',
                'tuteur1_whatsapp_2' => 'nullable|string|max:20',
                // Guardian 2 fields
                'tuteur2_nom' => 'nullable|string|max:255',
                'tuteur2_prenoms' => 'nullable|string|max:255',
                'tuteur2_nom_complet' => 'nullable|string|max:255',
                'tuteur2_profession' => 'nullable|string|max:255',
                'tuteur2_organisation_travail' => 'nullable|string|max:255',
                'tuteur2_ville_travail' => 'nullable|string|max:255',
                'tuteur2_pays_travail' => 'nullable|string|max:255',
                'tuteur2_adresse_residence' => 'nullable|string',
                'tuteur2_quartier' => 'nullable|string|max:255',
                'tuteur2_commune' => 'nullable|string|max:255',
                'tuteur2_arrondissement' => 'nullable|string|max:255',
                'tuteur2_ville' => 'nullable|string|max:255',
                'tuteur2_departement' => 'nullable|string|max:255',
                'tuteur2_region' => 'nullable|string|max:255',
                'tuteur2_pays' => 'nullable|string|max:255',
                'tuteur2_code_postal' => 'nullable|string|max:20',
                'tuteur2_boite_postal' => 'nullable|string|max:255',
                'tuteur2_telephone_1' => 'nullable|string|max:20',
                'tuteur2_telephone_2' => 'nullable|string|max:20',
                'tuteur2_email' => 'nullable|email|max:255',
                'tuteur2_whatsapp_1' => 'nullable|string|max:20',
                'tuteur2_whatsapp_2' => 'nullable|string|max:20',
                'etat' => 'required|in:actif,inactif',
            ]);

            // Règle métier : tous les apprenants dans la même école
            $apprenantIds = array_values(array_filter($request->input('apprenant_ids', [])));
            (new \App\Rules\SameSchoolForApprenants())->validate('apprenant_ids', $apprenantIds, function ($msg) {
                throw \Illuminate\Validation\ValidationException::withMessages(['apprenant_ids' => $msg]);
            });

            $parent = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request, $apprenantIds) {
                // Auto-création du User associé — utilise le père en priorité,
                // sinon la mère, sinon le tuteur 1 comme identité principale.
                $validated['user_id'] = \App\Services\AutoUserCreator::forProfile([
                    'nom'       => $validated['pere_nom'] ?? $validated['mere_nom'] ?? $validated['tuteur1_nom'] ?? 'Parent',
                    'prenoms'   => $validated['pere_prenoms'] ?? $validated['mere_prenoms'] ?? $validated['tuteur1_prenoms'] ?? null,
                    'email'     => $validated['pere_email_1'] ?? $validated['mere_email_1'] ?? $validated['tuteur1_email'] ?? null,
                    'telephone' => $validated['pere_telephone_1'] ?? $validated['mere_telephone_1'] ?? $validated['tuteur1_telephone_1'] ?? null,
                    'role'      => 'parent',
                ]);

                $parent = StudentParent::create($validated);

                // Sync du pivot avec métadonnées (lien_parente + est_principal sur le 1er)
                $sync = [];
                $lienList = $request->input('lien_parente', []);
                foreach ($apprenantIds as $i => $aid) {
                    $sync[$aid] = [
                        'lien_parente' => $lienList[$i] ?? null,
                        'est_principal' => $i === 0,
                    ];
                }
                if (!empty($sync)) {
                    $parent->apprenants()->sync($sync);
                }
                return $parent;
            });

            return redirect()->route('parents.index')
                ->with('success', __('messages.created'));
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve; // laisse remonter → Inertia gère les erreurs par champ
        } catch (\Throwable $th) {
            log_error("Personnel", "ParentController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $parent = StudentParent::with([
                'apprenant',
                'apprenants:id,nom,prenoms,matricule',
                'classe',
                'ecole',
                'institution',
                'campus',
            ])->findOrFail($id);

            // Enrichi avec les noms de contacts pour auto-fill dans le form parent
            $apprenants = Apprenant::select(
                    'id', 'nom', 'prenoms', 'matricule',
                    'classe_id', 'ecole_id', 'institution_id', 'campus_id',
                    'nom_pere', 'nom_mere', 'nom_tuteur', 'nom_responsable_legal',
                    'telephone', 'email', 'adresse'
                )
                ->selectRaw("CONCAT(prenoms, ' ', nom, ' (', COALESCE(matricule, ''), ')') as libelle")
                ->whereNull('deleted_at')
                ->orderBy('nom')
                ->get();

            $classes = Classe::select('id', 'nom as libelle')->orderBy('nom')->get();
            $ecoles = Ecole::select('id', 'nom as libelle')->orderBy('nom')->get();
            $institutions = Institution::select('id', 'nom as libelle')->orderBy('nom')->get();
            $campuses = Campus::select('id', 'nom as libelle')->orderBy('nom')->get();

            return Inertia::render('Personnel/Parents/Show', [
                'parent' => $parent,
                'apprenants' => $apprenants,
                'classes' => $classes,
                'ecoles' => $ecoles,
                'institutions' => $institutions,
                'campuses' => $campuses,
            ]);
        } catch (\Throwable $th) {
            log_error("Personnel", "ParentController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit($id)
    {
        try {
            $parent = StudentParent::with([
                'apprenant',
                'apprenants:id',      // nouveau : liste des apprenants liés
                'classe',
                'ecole',
                'institution',
                'campus',
            ])->findOrFail($id);

            // Injecte les IDs + méta pivot au format attendu par le composant Vue
            $parent->apprenant_ids = $parent->apprenants->pluck('id')->all();
            $parent->apprenant_pivots = $parent->apprenants->map(fn($a) => [
                'apprenant_id' => $a->id,
                'lien_parente' => $a->pivot->lien_parente,
                'est_principal' => (bool) $a->pivot->est_principal,
            ])->values()->all();

            // Enrichi avec les noms de contacts pour auto-fill dans le form parent
            $apprenants = Apprenant::select(
                    'id', 'nom', 'prenoms', 'matricule',
                    'classe_id', 'ecole_id', 'institution_id', 'campus_id',
                    'nom_pere', 'nom_mere', 'nom_tuteur', 'nom_responsable_legal',
                    'telephone', 'email', 'adresse'
                )
                ->selectRaw("CONCAT(prenoms, ' ', nom, ' (', COALESCE(matricule, ''), ')') as libelle")
                ->whereNull('deleted_at')
                ->orderBy('nom')
                ->get();

            $classes = Classe::select('id', 'nom as libelle')->orderBy('nom')->get();
            $ecoles = Ecole::select('id', 'nom as libelle')->orderBy('nom')->get();
            $institutions = Institution::select('id', 'nom as libelle')->orderBy('nom')->get();
            $campuses = Campus::select('id', 'nom as libelle')->orderBy('nom')->get();

            return Inertia::render('Personnel/Parents/Edit', [
                'parent' => $parent,
                'apprenants' => $apprenants,
                'classes' => $classes,
                'ecoles' => $ecoles,
                'institutions' => $institutions,
                'campuses' => $campuses,
            ]);
        } catch (\Throwable $th) {
            log_error("Personnel", "ParentController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $parent = StudentParent::findOrFail($id);

            $validated = $request->validate([
                'apprenant_id' => 'nullable|exists:apprenants,id',
                'apprenant_ids' => ['nullable', 'array'],
                'apprenant_ids.*' => ['integer', 'exists:apprenants,id'],
                'lien_parente' => ['nullable', 'array'],
                'lien_parente.*' => ['nullable', 'string', 'in:pere,mere,tuteur_legal,autre'],
                'classe_id' => 'nullable|exists:classes,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'institution_id' => 'nullable|exists:institutions,id',
                'campus_id' => 'nullable|exists:campuses,id',
                // Father fields
                'pere_nom' => 'nullable|string|max:255',
                'pere_prenoms' => 'nullable|string|max:255',
                'pere_nom_complet' => 'nullable|string|max:255',
                'pere_profession' => 'nullable|string|max:255',
                'pere_organisation_travail' => 'nullable|string|max:255',
                'pere_ville_travail' => 'nullable|string|max:255',
                'pere_pays_travail' => 'nullable|string|max:255',
                'pere_adresse_residence' => 'nullable|string',
                'pere_quartier' => 'nullable|string|max:255',
                'pere_commune' => 'nullable|string|max:255',
                'pere_departement' => 'nullable|string|max:255',
                'pere_region' => 'nullable|string|max:255',
                'pere_code_postal' => 'nullable|string|max:20',
                'pere_boite_postal' => 'nullable|string|max:255',
                'pere_telephone_1' => 'nullable|string|max:20',
                'pere_telephone_2' => 'nullable|string|max:20',
                'pere_whatsapp_1' => 'nullable|string|max:20',
                'pere_whatsapp_2' => 'nullable|string|max:20',
                'pere_email_1' => 'nullable|email|max:255',
                'pere_email_2' => 'nullable|email|max:255',
                // Mother fields
                'mere_nom' => 'nullable|string|max:255',
                'mere_prenoms' => 'nullable|string|max:255',
                'mere_nom_complet' => 'nullable|string|max:255',
                'mere_profession' => 'nullable|string|max:255',
                'mere_organisation_travail' => 'nullable|string|max:255',
                'mere_ville_travail' => 'nullable|string|max:255',
                'mere_pays_travail' => 'nullable|string|max:255',
                'mere_adresse_residence' => 'nullable|string',
                'mere_quartier' => 'nullable|string|max:255',
                'mere_commune' => 'nullable|string|max:255',
                'mere_departement' => 'nullable|string|max:255',
                'mere_region' => 'nullable|string|max:255',
                'mere_code_postal' => 'nullable|string|max:20',
                'mere_boite_postal' => 'nullable|string|max:255',
                'mere_telephone_1' => 'nullable|string|max:20',
                'mere_telephone_2' => 'nullable|string|max:20',
                'mere_whatsapp_1' => 'nullable|string|max:20',
                'mere_whatsapp_2' => 'nullable|string|max:20',
                'mere_email_1' => 'nullable|email|max:255',
                'mere_email_2' => 'nullable|email|max:255',
                // Guardian 1 fields
                'tuteur1_nom' => 'nullable|string|max:255',
                'tuteur1_prenoms' => 'nullable|string|max:255',
                'tuteur1_nom_complet' => 'nullable|string|max:255',
                'tuteur1_profession' => 'nullable|string|max:255',
                'tuteur1_organisation_travail' => 'nullable|string|max:255',
                'tuteur1_ville_travail' => 'nullable|string|max:255',
                'tuteur1_pays_travail' => 'nullable|string|max:255',
                'tuteur1_adresse_residence' => 'nullable|string',
                'tuteur1_quartier' => 'nullable|string|max:255',
                'tuteur1_commune' => 'nullable|string|max:255',
                'tuteur1_arrondissement' => 'nullable|string|max:255',
                'tuteur1_ville' => 'nullable|string|max:255',
                'tuteur1_departement' => 'nullable|string|max:255',
                'tuteur1_region' => 'nullable|string|max:255',
                'tuteur1_pays' => 'nullable|string|max:255',
                'tuteur1_code_postal' => 'nullable|string|max:20',
                'tuteur1_boite_postal' => 'nullable|string|max:255',
                'tuteur1_telephone_1' => 'nullable|string|max:20',
                'tuteur1_telephone_2' => 'nullable|string|max:20',
                'tuteur1_email' => 'nullable|email|max:255',
                'tuteur1_whatsapp_1' => 'nullable|string|max:20',
                'tuteur1_whatsapp_2' => 'nullable|string|max:20',
                // Guardian 2 fields
                'tuteur2_nom' => 'nullable|string|max:255',
                'tuteur2_prenoms' => 'nullable|string|max:255',
                'tuteur2_nom_complet' => 'nullable|string|max:255',
                'tuteur2_profession' => 'nullable|string|max:255',
                'tuteur2_organisation_travail' => 'nullable|string|max:255',
                'tuteur2_ville_travail' => 'nullable|string|max:255',
                'tuteur2_pays_travail' => 'nullable|string|max:255',
                'tuteur2_adresse_residence' => 'nullable|string',
                'tuteur2_quartier' => 'nullable|string|max:255',
                'tuteur2_commune' => 'nullable|string|max:255',
                'tuteur2_arrondissement' => 'nullable|string|max:255',
                'tuteur2_ville' => 'nullable|string|max:255',
                'tuteur2_departement' => 'nullable|string|max:255',
                'tuteur2_region' => 'nullable|string|max:255',
                'tuteur2_pays' => 'nullable|string|max:255',
                'tuteur2_code_postal' => 'nullable|string|max:20',
                'tuteur2_boite_postal' => 'nullable|string|max:255',
                'tuteur2_telephone_1' => 'nullable|string|max:20',
                'tuteur2_telephone_2' => 'nullable|string|max:20',
                'tuteur2_email' => 'nullable|email|max:255',
                'tuteur2_whatsapp_1' => 'nullable|string|max:20',
                'tuteur2_whatsapp_2' => 'nullable|string|max:20',
                'etat' => 'required|in:actif,inactif',
            ]);

            // Règle métier : tous les apprenants dans la même école
            $apprenantIds = array_values(array_filter($request->input('apprenant_ids', [])));
            (new \App\Rules\SameSchoolForApprenants())->validate('apprenant_ids', $apprenantIds, function ($msg) {
                throw \Illuminate\Validation\ValidationException::withMessages(['apprenant_ids' => $msg]);
            });

            \Illuminate\Support\Facades\DB::transaction(function () use ($parent, $validated, $request, $apprenantIds) {
                $parent->update($validated);

                $sync = [];
                $lienList = $request->input('lien_parente', []);
                foreach ($apprenantIds as $i => $aid) {
                    $sync[$aid] = [
                        'lien_parente' => $lienList[$i] ?? null,
                        'est_principal' => $i === 0,
                    ];
                }
                $parent->apprenants()->sync($sync);
            });

            return redirect()->route('parents.index')
                ->with('success', __('messages.updated'));
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Personnel", "ParentController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $parent = StudentParent::findOrFail($id);
            $parent->delete();

            return redirect()->route('parents.index')
                ->with('success', __('messages.deleted'));
        } catch (\Throwable $th) {
            log_error("Personnel", "ParentController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut($id)
    {
        try {
            $parent = StudentParent::findOrFail($id);
            $parent->etat = $parent->etat === 'actif' ? 'inactif' : 'actif';
            $parent->save();

            return redirect()->route('parents.index')
                ->with('success', __('messages.updated'));
        } catch (\Throwable $th) {
            log_error("Personnel", "ParentController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
