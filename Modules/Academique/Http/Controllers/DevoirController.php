<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Devoir;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Academique\Entities\Classe;
use Modules\Parametrage\Entities\Classe as ParametrageClasse;

class DevoirController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:devoirs-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:devoirs-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:devoirs-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:devoirs-delete', ['only' => ['destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        try {
            \Log::info('📚 DevoirController::index() started');
            $query = Devoir::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('titre', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $devoirs = $query->with(['matiere', 'classe'])->paginate(10)->withQueryString();
            \Log::info('✅ Devoirs loaded: ' . $devoirs->count());

            return Inertia::render('Academique::Devoirs/Index', [
                'title' => 'Devoirs',
                'devoirs' => $devoirs,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ DevoirController::index error: ' . $th->getMessage());
            \Log::error('Stack: ' . $th->getFile() . ':' . $th->getLine());
            log_error("Academique", "DevoirController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            \Log::info('📚 DevoirController::create() started');

            // Get matieres with proper formatting
            $matieres = MatiereUnite::select('id', 'libelle')->get()
                ->map(fn($m) => ['id' => $m->id, 'libelle' => $m->libelle ?? 'Sans nom'])
                ->toArray();
            \Log::info('✅ Matieres loaded: ' . count($matieres));

            // Get classes with full FK chain + relations pour cascade + ContextBar
            $classes = ParametrageClasse::with([
                    'ecole:id,nom',
                    'campus:id,nom',
                    'niveau:id,libelle',
                    'section:id,libelle',
                    'cycle:id,libelle',
                    'anneeScolaire:id,libelle',
                ])
                ->select('id', 'nom', 'libelle', 'libelle_affichage', 'code', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
                ->get()
                ->map(fn($c) => [
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
                ])
                ->toArray();
            \Log::info('✅ Classes loaded: ' . count($classes));

            return Inertia::render('Academique::Devoirs/Create', [
                'title' => 'Nouveau Devoir',
                'matieres' => $matieres,
                'classes' => $classes,
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ Error in DevoirController::create: ' . $th->getMessage());
            log_error("Academique", "DevoirController::create", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('🔍 DevoirController::store() started');
            \Log::info('📋 Request data:', $request->all());

            $validated = $request->validate([
                'matiere_id' => 'required|exists:matieres_unites,id',
                'classe_id' => 'required|exists:classes,id',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date_debut' => 'required|regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/',
                'date_fin' => 'required|regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/',
                'nombre_heures' => 'nullable|numeric|min:0',
                'coefficient' => 'required|numeric|min:0',
                'statut' => 'required|in:actif,inactif',
            ]);

            // Ajoute validation que date_fin > date_debut
            if (strtotime($validated['date_fin']) <= strtotime($validated['date_debut'])) {
                throw new \Exception('La date de fin doit être après la date de début');
            }

            \Log::info('✅ Validation passed');
            \Log::info('💾 Validated data:', $validated);

            // Convert datetime-local format to proper datetime
            $validated['date_debut'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', str_replace('T', ' ', $validated['date_debut']));
            $validated['date_fin'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', str_replace('T', ' ', $validated['date_fin']));

            \Log::info('🔄 Converted dates - debut: ' . $validated['date_debut']->toDateTimeString() . ', fin: ' . $validated['date_fin']->toDateTimeString());

            Devoir::create($validated);

            \Log::info('✅ Devoir created successfully');

            return redirect()->route('academique.devoirs.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            \Log::error('❌ DevoirController::store error: ' . $th->getMessage());
            \Log::error('Stack trace:', [$th->getFile() . ':' . $th->getLine()]);
            log_error("Academique", "DevoirController::store", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function show(Devoir $devoir)
    {
        try {
            \Log::info('📋 DevoirController::show() started - Devoir ID: ' . $devoir->id);
            $devoir->load('matiere', 'classe');
            \Log::info('✅ Relationships loaded');

            // DEBUG raw dates
            \Log::info('🔍 DEBUG show() - Raw dates from DB:', [
                'date_debut_raw' => $devoir->date_debut,
                'date_fin_raw' => $devoir->date_fin,
                'date_debut_type' => gettype($devoir->date_debut),
                'date_fin_type' => gettype($devoir->date_fin),
            ]);

            // Manually construct the array to ensure all fields are included
            $arr = [
                'id' => $devoir->id,
                'matiere_id' => $devoir->matiere_id,
                'classe_id' => $devoir->classe_id,
                'titre' => $devoir->titre,
                'description' => $devoir->description,
                'date_debut' => $devoir->date_debut ? $devoir->date_debut->format('Y-m-d\TH:i') : null,
                'date_fin' => $devoir->date_fin ? $devoir->date_fin->format('Y-m-d\TH:i') : null,
                'nombre_heures' => $devoir->nombre_heures,
                'coefficient' => $devoir->coefficient,
                'statut' => $devoir->statut,
                'matiere' => $devoir->matiere,
                'classe' => $devoir->classe,
            ];

            // DEBUG formatted dates
            \Log::info('🔍 DEBUG show() - Formatted dates for Inertia:', [
                'date_debut_formatted' => $arr['date_debut'],
                'date_fin_formatted' => $arr['date_fin'],
                'nombre_heures' => $arr['nombre_heures'],
            ]);

            \Log::info('✅ Array constructed');

            return Inertia::render('Academique::Devoirs/Show', [
                'title' => 'Détails du Devoir',
                'devoir' => $arr,
                'matieres' => MatiereUnite::select('id', 'libelle')->get()->map(fn($m) => ['id' => $m->id, 'libelle' => $m->libelle ?? 'Sans nom'])->toArray(),
                'classes' => ParametrageClasse::select('id', 'nom')->get()->map(fn($c) => ['id' => $c->id, 'nom' => $c->nom ?? 'Sans nom'])->toArray(),
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ DevoirController::show error: ' . $th->getMessage());
            \Log::error('Stack: ' . $th->getFile() . ':' . $th->getLine());
            log_error("Academique", "DevoirController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Devoir $devoir)
    {
        try {
            \Log::info('📝 DevoirController::edit() started - Devoir ID: ' . $devoir->id);
            $devoir->load('matiere', 'classe');

            // DEBUG raw dates
            \Log::info('🔍 DEBUG edit() - Raw dates from DB:', [
                'date_debut_raw' => $devoir->date_debut,
                'date_fin_raw' => $devoir->date_fin,
                'date_debut_type' => gettype($devoir->date_debut),
                'date_fin_type' => gettype($devoir->date_fin),
            ]);

            // Manually construct the array to ensure all fields are included
            $arr = [
                'id' => $devoir->id,
                'matiere_id' => $devoir->matiere_id,
                'classe_id' => $devoir->classe_id,
                'titre' => $devoir->titre,
                'description' => $devoir->description,
                'date_debut' => $devoir->date_debut ? $devoir->date_debut->format('Y-m-d\TH:i') : null,
                'date_fin' => $devoir->date_fin ? $devoir->date_fin->format('Y-m-d\TH:i') : null,
                'nombre_heures' => $devoir->nombre_heures,
                'coefficient' => $devoir->coefficient,
                'statut' => $devoir->statut,
                'matiere' => $devoir->matiere,
                'classe' => $devoir->classe,
            ];

            // DEBUG formatted dates
            \Log::info('🔍 DEBUG edit() - Formatted dates for Inertia:', [
                'date_debut_formatted' => $arr['date_debut'],
                'date_fin_formatted' => $arr['date_fin'],
                'nombre_heures' => $arr['nombre_heures'],
            ]);

            $matieres = MatiereUnite::select('id', 'libelle')->get()->map(fn($m) => ['id' => $m->id, 'libelle' => $m->libelle ?? 'Sans nom'])->toArray();
            $classes = ParametrageClasse::select('id', 'nom')->get()->map(fn($c) => ['id' => $c->id, 'nom' => $c->nom ?? 'Sans nom'])->toArray();

            return Inertia::render('Academique::Devoirs/Edit', [
                'title' => 'Modifier le Devoir',
                'devoir' => $arr,
                'matieres' => $matieres,
                'classes' => $classes,
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ DevoirController::edit error: ' . $th->getMessage());
            log_error("Academique", "DevoirController::edit", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function update(Request $request, Devoir $devoir)
    {
        try {
            $validated = $request->validate([
                'matiere_id' => 'required|exists:matieres_unites,id',
                'classe_id' => 'required|exists:classes,id',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date_debut' => 'required|regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/',
                'date_fin' => 'required|regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/',
                'nombre_heures' => 'nullable|numeric|min:0',
                'coefficient' => 'required|numeric|min:0',
                'statut' => 'required|in:actif,inactif',
            ]);

            // Ajoute validation que date_fin > date_debut
            if (strtotime($validated['date_fin']) <= strtotime($validated['date_debut'])) {
                throw new \Exception('La date de fin doit être après la date de début');
            }

            // Convert datetime-local format to proper datetime
            $validated['date_debut'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', str_replace('T', ' ', $validated['date_debut']));
            $validated['date_fin'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', str_replace('T', ' ', $validated['date_fin']));

            $devoir->update($validated);

            return redirect()->route('academique.devoirs.index')
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "DevoirController::update", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function destroy(Devoir $devoir)
    {
        try {
            $devoir->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "DevoirController::destroy", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function activate(Devoir $devoir)
    {
        try {
            $devoir->statut = $devoir->statut === 'actif' ? 'inactif' : 'actif';
            $devoir->save();

            return back()->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "DevoirController::activate", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }
}
