<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Cours;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Academique\Entities\Enseignant;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\AnneeScolaire;

class CoursController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:cours-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:cours-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:cours-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:cours-delete', ['only' => ['destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Cours::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('titre', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $cours = $query->with(['matiere', 'classe', 'enseignant.user'])->paginate(10)->withQueryString();

            return Inertia::render('Academique::Cours/Index', [
                'title' => 'Cours',
                'cours' => $cours,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "CoursController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            \Log::info('🔍 CoursController::create() démarré');

            \Log::info('📚 Récupération des matières...');
            $matieres = MatiereUnite::select('id', 'libelle')->get();
            \Log::info('✅ Matières trouvées: ' . $matieres->count());
            $matieres = $matieres->map(fn($m) => ['id' => $m->id, 'libelle' => $m->libelle ?? 'Sans nom'])->toArray();

            \Log::info('🏫 Récupération des classes...');
            $classes = Classe::with([
                    'ecole:id,nom',
                    'campus:id,nom',
                    'niveau:id,libelle',
                    'section:id,libelle',
                    'cycle:id,libelle',
                    'anneeScolaire:id,libelle',
                ])
                ->select('id', 'nom', 'libelle', 'libelle_affichage', 'code', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
                ->get();
            \Log::info('✅ Classes trouvées: ' . $classes->count());
            $classes = $classes->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->libelle_affichage ?: ($c->libelle ?: ($c->nom ?: 'Sans nom')),
                'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                'code' => $c->code,
                'ecole_id' => $c->ecole_id,
                'ecole_nom' => $c->ecole?->nom,
                'campus_id' => $c->campus_id,
                'campus_nom' => $c->campus?->nom,
                'niveau_id' => $c->niveau_id,
                'niveau_libelle' => $c->niveau?->libelle,
                'section_id' => $c->section_id,
                'section_libelle' => $c->section?->libelle,
                'cycle_id' => $c->cycle_id,
                'cycle_libelle' => $c->cycle?->libelle,
                'annee_scolaire_id' => $c->annee_scolaire_id,
                'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
            ])->toArray();

            \Log::info('👨‍🏫 Récupération des enseignants...');
            $enseignants = Enseignant::with('user')->get();
            \Log::info('Enseignants avant filtre: ' . $enseignants->count());
            $enseignants = $enseignants->filter(fn($e) => $e->user !== null);
            \Log::info('✅ Enseignants avec user: ' . $enseignants->count());
            $enseignants = $enseignants->map(fn($e) => ['id' => $e->id, 'libelle' => ($e->user->prenoms ?? '') . ' ' . ($e->user->nom ?? 'Sans nom')])->values()->toArray();

            \Log::info('📄 Rendu de la page Inertia...');
            return Inertia::render('Academique::Cours/Create', [
                'title' => 'Nouveau Cours',
                'matieres' => $matieres,
                'classes' => $classes,
                'enseignants' => $enseignants,
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ Erreur CoursController::create: ' . $th->getMessage());
            \Log::error('📍 File: ' . $th->getFile() . ' Line: ' . $th->getLine());
            \Log::error('🔗 Stack: ' . $th->getTraceAsString());
            log_error("Academique", "CoursController::create", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('🚀 CoursController::store() démarré');
            \Log::info('📨 Données reçues:', $request->all());

            // §UX : Cours utilise actif/inactif lowercase — mais le SearchableSelect
            // peut renvoyer "Actif" (Titlecase) selon l'option source.
            if ($request->filled('statut')) {
                $request->merge(['statut' => strtolower((string) $request->input('statut'))]);
            }

            $validated = $request->validate([
                'matiere_id' => 'required|exists:matieres_unites,id',
                'classe_id' => 'required|exists:classes,id',
                'enseignant_id' => 'required|exists:enseignants,id',
                'code' => 'required|string|max:100|unique:cours',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date_debut' => 'required|regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/',
                'date_fin' => 'required|regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/',
                'statut' => 'required|in:actif,inactif',
            ]);

            \Log::info('✅ Validation réussie');
            \Log::info('📝 Données validées:', $validated);

            // Vérifier que date_fin > date_debut
            if (strtotime($validated['date_fin']) <= strtotime($validated['date_debut'])) {
                throw new \Exception('La date de fin doit être après la date de début');
            }

            // Convert datetime-local format to proper datetime
            $validated['date_debut'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', str_replace('T', ' ', $validated['date_debut']));
            $validated['date_fin'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', str_replace('T', ' ', $validated['date_fin']));

            \Log::info('🔄 Converted dates - debut: ' . $validated['date_debut']->toDateTimeString() . ', fin: ' . $validated['date_fin']->toDateTimeString());

            $cours = Cours::create($validated);
            \Log::info('✅ Cours créé avec succès: ID ' . $cours->id);

            return redirect()->route('academique.cours.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            \Log::error('❌ Erreur CoursController::store: ' . $th->getMessage());
            \Log::error('📍 File: ' . $th->getFile() . ' Line: ' . $th->getLine());
            \Log::error('🔗 Stack: ' . $th->getTraceAsString());
            log_error("Academique", "CoursController::store", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function show(Cours $cours)
    {
        try {
            \Log::info('📄 CoursController::show() START');
            \Log::info('🔍 Is Cours model instance? ' . (is_object($cours) ? 'YES' : 'NO'));
            \Log::info('🔍 Cours ID: ' . ($cours->id ?? 'NULL'));
            \Log::info('🔍 Cours exists property: ' . ($cours->exists ? 'TRUE' : 'FALSE'));

            $matieres = MatiereUnite::select('id', 'libelle')->get()
                ->map(fn($m) => ['id' => $m->id, 'libelle' => $m->libelle ?? 'Sans nom'])
                ->toArray();

            $classes = Classe::select('id', 'nom')->get()
                ->map(fn($c) => ['id' => $c->id, 'nom' => $c->nom ?? 'Sans nom'])
                ->toArray();

            $enseignants = Enseignant::with('user')
                ->get()
                ->filter(fn($e) => $e->user !== null)
                ->map(fn($e) => ['id' => $e->id, 'libelle' => ($e->user->prenoms ?? '') . ' ' . ($e->user->nom ?? 'Sans nom')])
                ->values()
                ->toArray();

            \Log::info('🔍 Before load - Cours attributes: code=' . $cours->code . ', titre=' . $cours->titre);

            $cours->load('matiere', 'classe', 'enseignant.user', 'seances');

            \Log::info('🔍 After load - ready to construct array');

            // Manually construct the array to bypass any toArray() issues
            $arr = [
                'id' => $cours->id,
                'code' => $cours->code,
                'titre' => $cours->titre,
                'description' => $cours->description,
                'matiere_id' => $cours->matiere_id,
                'classe_id' => $cours->classe_id,
                'enseignant_id' => $cours->enseignant_id,
                'date_debut' => $cours->date_debut ? $cours->date_debut->format('Y-m-d\TH:i') : null,
                'date_fin' => $cours->date_fin ? $cours->date_fin->format('Y-m-d\TH:i') : null,
                'statut' => $cours->statut,
                'matiere' => $cours->matiere,
                'classe' => $cours->classe,
                'enseignant' => $cours->enseignant,
                'seances' => $cours->seances,
            ];

            \Log::info('✅ Array constructed');
            \Log::info('🔍 Array[id]=' . $arr['id'] . ', Array[code]=' . $arr['code'] . ', Array[titre]=' . $arr['titre']);

            return Inertia::render('Academique::Cours/Show', [
                'title' => 'Détails du Cours',
                'cours' => $arr,
                'matieres' => $matieres,
                'classes' => $classes,
                'enseignants' => $enseignants,
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ Erreur CoursController::show: ' . $th->getMessage());
            log_error("Academique", "CoursController::show", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function edit(Cours $cours)
    {
        try {
            \Log::info('✏️ CoursController::edit() pour Cours ID ' . $cours->id);

            $matieres = MatiereUnite::select('id', 'libelle')->get()
                ->map(fn($m) => ['id' => $m->id, 'libelle' => $m->libelle ?? 'Sans nom'])
                ->toArray();

            $classes = Classe::select('id', 'nom')->get()
                ->map(fn($c) => ['id' => $c->id, 'nom' => $c->nom ?? 'Sans nom'])
                ->toArray();

            $enseignants = Enseignant::with('user')
                ->get()
                ->filter(fn($e) => $e->user !== null)
                ->map(fn($e) => ['id' => $e->id, 'libelle' => ($e->user->prenoms ?? '') . ' ' . ($e->user->nom ?? 'Sans nom')])
                ->values()
                ->toArray();

            $cours->load('matiere', 'classe', 'enseignant.user');

            // Manually construct the array to bypass any toArray() issues
            $arr = [
                'id' => $cours->id,
                'code' => $cours->code,
                'titre' => $cours->titre,
                'description' => $cours->description,
                'matiere_id' => $cours->matiere_id,
                'classe_id' => $cours->classe_id,
                'enseignant_id' => $cours->enseignant_id,
                'date_debut' => $cours->date_debut ? $cours->date_debut->format('Y-m-d\TH:i') : null,
                'date_fin' => $cours->date_fin ? $cours->date_fin->format('Y-m-d\TH:i') : null,
                'statut' => $cours->statut,
                'matiere' => $cours->matiere,
                'classe' => $cours->classe,
                'enseignant' => $cours->enseignant,
            ];

            \Log::info('✅ Edit manually constructed array with ' . count($arr) . ' fields');

            return Inertia::render('Academique::Cours/Edit', [
                'title' => 'Modifier le Cours',
                'cours' => $arr,
                'matieres' => $matieres,
                'classes' => $classes,
                'enseignants' => $enseignants,
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ Erreur CoursController::edit: ' . $th->getMessage());
            log_error("Academique", "CoursController::edit", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function update(Request $request, Cours $cours)
    {
        try {
            // §UX : idem store() — normalise statut en lowercase.
            if ($request->filled('statut')) {
                $request->merge(['statut' => strtolower((string) $request->input('statut'))]);
            }
            $validated = $request->validate([
                'matiere_id' => 'required|exists:matieres_unites,id',
                'classe_id' => 'required|exists:classes,id',
                'enseignant_id' => 'required|exists:enseignants,id',
                'code' => 'required|string|max:100|unique:cours,code,' . $cours->id,
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date_debut' => 'required|regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/',
                'date_fin' => 'required|regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/',
                'statut' => 'required|in:actif,inactif',
            ]);

            // Vérifier que date_fin > date_debut
            if (strtotime($validated['date_fin']) <= strtotime($validated['date_debut'])) {
                throw new \Exception('La date de fin doit être après la date de début');
            }

            // §BUG-FIX : le format `Y-m-d\TH:i` attend un T LITTÉRAL, mais
            //   str_replace('T', ' ', ...) l'avait déjà remplacé par un espace
            //   → Carbon lève "Unexpected data found". On aligne sur store()
            //   qui utilise le bon format `Y-m-d H:i` avec le str_replace.
            $validated['date_debut'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', str_replace('T', ' ', $validated['date_debut']));
            $validated['date_fin']   = \Carbon\Carbon::createFromFormat('Y-m-d H:i', str_replace('T', ' ', $validated['date_fin']));

            $cours->update($validated);

            return redirect()->route('academique.cours.show', $cours)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "CoursController::update", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function destroy(Cours $cours)
    {
        try {
            $cours->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "CoursController::destroy", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function activate(Cours $cours)
    {
        try {
            \Log::info('🔄 Toggling statut for Cours ID ' . $cours->id);
            \Log::info('Current statut: ' . $cours->statut);

            $cours->statut = $cours->statut === 'actif' ? 'inactif' : 'actif';
            $cours->save();

            \Log::info('✅ New statut: ' . $cours->statut);
            return back()->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            \Log::error('❌ Erreur CoursController::activate: ' . $th->getMessage());
            \Log::error('📍 File: ' . $th->getFile() . ' Line: ' . $th->getLine());
            log_error("Academique", "CoursController::activate", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }
}
