<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Enseignant;
use App\Models\User;
use Modules\Parametrage\Entities\{Commune, Departement, Region, Pays, CategorieEnseignant, Matiere, Niveau, CycleEnseignement, Classe};

class EnseignantController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:enseignants-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:enseignants-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:enseignants-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:enseignants-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $query = Enseignant::query();

        // Filtres
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('prenoms', 'like', "%$search%");
            })->orWhere('matricule', 'like', "%$search%");
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $enseignants = $query->with('user')->paginate(10)->withQueryString();

        return Inertia::render('Academique::Enseignants/Index', [
            'enseignants' => $enseignants,
            'filters' => $request->only(['search', 'statut']),
        ]);
    }

    public function create()
    {
        $users = User::select('id', 'nom', 'prenoms', 'email')->get()->toArray();

        return Inertia::render('Academique::Enseignants/Create', [
            'title' => __('actions.create'),
            'users' => $users,
            'communes' => Commune::whereNull('deleted_at')->select('id', 'libelle', 'departement_id')->get()->toArray(),
            'departements' => Departement::whereNull('deleted_at')->select('id', 'libelle', 'region_id', 'pays_id')->get()->toArray(),
            'regions' => Region::whereNull('deleted_at')->select('id', 'libelle', 'pays_id')->get()->toArray(),
            'pays' => Pays::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'categoriesEnseignant' => CategorieEnseignant::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'matieres' => Matiere::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'cycles' => CycleEnseignement::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'niveaux' => Niveau::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'classes' => Classe::whereNull('deleted_at')->select('id', 'nom')->get()->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id|unique:enseignants',
                'matricule' => 'nullable|unique:enseignants',
                'nom' => 'nullable|string|max:100',
                'prenoms' => 'nullable|string|max:100',
                'nom_restituer' => 'nullable|string|max:100',
                'nom_jeune_fille' => 'nullable|string|max:100',
                'gender' => 'nullable|in:M,F,Autre',
                'marital_status' => 'nullable|string|max:50',
                'date_of_birth' => 'nullable|date',
                'place_of_birth' => 'nullable|string|max:100',
                'commune_id' => 'nullable|exists:communes,id',
                'department_id' => 'nullable|exists:departements,id',
                'region_id' => 'nullable|exists:regions,id',
                'country_id' => 'nullable|exists:pays,id',
                'nationalite' => 'nullable|string|max:100',
                'highest_diploma' => 'nullable|string|max:255',
                'speciality' => 'nullable|string|max:100',
                'year_obtained' => 'nullable|integer|min:1900|max:' . date('Y'),
                'languages' => 'nullable|array',
                'teaching_speciality' => 'nullable|string|max:255',
                'type_contrat' => 'nullable|in:cdi,cdd,vacataire,autre',
                'date_embauche' => 'nullable|date',
                'teacher_category' => 'nullable|string|max:100',
                'categorie_enseignant_id' => 'nullable|exists:categorie_enseignants,id',
                'email' => 'nullable|email|max:100',
                'telephone' => 'nullable|string|max:20',
                'photo' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'statut' => 'required|in:actif,suspendu,conge,retraite,demission',
                'matiere_1_id' => 'nullable|exists:matieres,id',
                'matiere_2_id' => 'nullable|exists:matieres,id',
                'matiere_3_id' => 'nullable|exists:matieres,id',
                'matiere_4_id' => 'nullable|exists:matieres,id',
                'matiere_5_id' => 'nullable|exists:matieres,id',
                'matiere_6_id' => 'nullable|exists:matieres,id',
                'matiere_7_id' => 'nullable|exists:matieres,id',
                'cycle_1_id' => 'nullable|exists:cycles_enseignement,id',
                'cycle_2_id' => 'nullable|exists:cycles_enseignement,id',
                'niveau_1_id' => 'nullable|exists:niveaux,id',
                'niveau_2_id' => 'nullable|exists:niveaux,id',
                'niveau_3_id' => 'nullable|exists:niveaux,id',
                'niveau_4_id' => 'nullable|exists:niveaux,id',
                'classe_1_id' => 'nullable|exists:classes,id',
                'classe_2_id' => 'nullable|exists:classes,id',
                'classe_3_id' => 'nullable|exists:classes,id',
                'classe_4_id' => 'nullable|exists:classes,id',
                'classe_5_id' => 'nullable|exists:classes,id',
            ]);

            // Handle photo upload
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $photoPath = $request->file('photo')->store('enseignants', 'public');
                $validated['photo'] = $photoPath;
            }

            $enseignant = Enseignant::create($validated);

            // Sync pivot tables
            $matiereIds = array_values(array_filter([
                $request->matiere_1_id, $request->matiere_2_id, $request->matiere_3_id,
                $request->matiere_4_id, $request->matiere_5_id, $request->matiere_6_id,
                $request->matiere_7_id,
            ]));
            $cycleIds = array_values(array_filter([$request->cycle_1_id, $request->cycle_2_id]));
            $niveauIds = array_values(array_filter([
                $request->niveau_1_id, $request->niveau_2_id, $request->niveau_3_id, $request->niveau_4_id,
            ]));
            $classeIds = array_values(array_filter([
                $request->classe_1_id, $request->classe_2_id, $request->classe_3_id,
                $request->classe_4_id, $request->classe_5_id,
            ]));

            $enseignant->matieres()->sync($matiereIds);
            $enseignant->cycles()->sync($cycleIds);
            $enseignant->niveaux()->sync($niveauIds);
            $enseignant->classes()->sync($classeIds);

            return redirect()->route('academique.enseignants.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "EnseignantController::store", $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    public function show(Enseignant $enseignant)
    {
        $enseignant->load(['user', 'absences', 'commune', 'departement', 'region', 'pays', 'categorieEnseignant', 'matieres', 'cycles', 'niveaux', 'classes']);

        // Add IDs for form display
        $enseignant->matieres_ids = $enseignant->matieres->pluck('id');
        $enseignant->cycles_ids = $enseignant->cycles->pluck('id');
        $enseignant->niveaux_ids = $enseignant->niveaux->pluck('id');
        $enseignant->classes_ids = $enseignant->classes->pluck('id');

        return Inertia::render('Academique::Enseignants/Show', [
            'title' => __('actions.view'),
            'enseignant' => $enseignant,
            'communes' => Commune::whereNull('deleted_at')->select('id', 'libelle', 'departement_id')->get()->toArray(),
            'departements' => Departement::whereNull('deleted_at')->select('id', 'libelle', 'region_id', 'pays_id')->get()->toArray(),
            'regions' => Region::whereNull('deleted_at')->select('id', 'libelle', 'pays_id')->get()->toArray(),
            'pays' => Pays::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'categoriesEnseignant' => CategorieEnseignant::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'matieres' => Matiere::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'cycles' => CycleEnseignement::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'niveaux' => Niveau::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'classes' => Classe::whereNull('deleted_at')->select('id', 'nom')->get()->toArray(),
        ]);
    }

    public function edit(Enseignant $enseignant)
    {
        $users = User::select('id', 'nom', 'prenoms', 'email')->get()->toArray();
        $enseignant->load(['user', 'commune', 'departement', 'region', 'pays', 'categorieEnseignant', 'matieres', 'cycles', 'niveaux', 'classes']);

        // Add IDs for form population
        $enseignant->matieres_ids = $enseignant->matieres->pluck('id');
        $enseignant->cycles_ids = $enseignant->cycles->pluck('id');
        $enseignant->niveaux_ids = $enseignant->niveaux->pluck('id');
        $enseignant->classes_ids = $enseignant->classes->pluck('id');

        return Inertia::render('Academique::Enseignants/Edit', [
            'title' => __('actions.edit'),
            'enseignant' => $enseignant,
            'users' => $users,
            'communes' => Commune::whereNull('deleted_at')->select('id', 'libelle', 'departement_id')->get()->toArray(),
            'departements' => Departement::whereNull('deleted_at')->select('id', 'libelle', 'region_id', 'pays_id')->get()->toArray(),
            'regions' => Region::whereNull('deleted_at')->select('id', 'libelle', 'pays_id')->get()->toArray(),
            'pays' => Pays::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'categoriesEnseignant' => CategorieEnseignant::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'matieres' => Matiere::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'cycles' => CycleEnseignement::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'niveaux' => Niveau::whereNull('deleted_at')->select('id', 'libelle')->get()->toArray(),
            'classes' => Classe::whereNull('deleted_at')->select('id', 'nom')->get()->toArray(),
        ]);
    }

    public function update(Request $request, Enseignant $enseignant)
    {
        \Log::info('🔄 EnseignantController::update() STARTED');
        \Log::info('📌 Enseignant ID: ' . $enseignant->id);
        \Log::info('📨 Request method: ' . $request->method());
        \Log::info('📨 Request all: ' . json_encode($request->all()));

        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id|unique:enseignants,user_id,' . $enseignant->id,
                'matricule' => 'nullable|unique:enseignants,matricule,' . $enseignant->id,
                'nom' => 'nullable|string|max:100',
                'prenoms' => 'nullable|string|max:100',
                'nom_restituer' => 'nullable|string|max:100',
                'nom_jeune_fille' => 'nullable|string|max:100',
                'gender' => 'nullable|in:M,F,Autre',
                'marital_status' => 'nullable|string|max:50',
                'date_of_birth' => 'nullable|date',
                'place_of_birth' => 'nullable|string|max:100',
                'commune_id' => 'nullable|exists:communes,id',
                'department_id' => 'nullable|exists:departements,id',
                'region_id' => 'nullable|exists:regions,id',
                'country_id' => 'nullable|exists:pays,id',
                'nationalite' => 'nullable|string|max:100',
                'highest_diploma' => 'nullable|string|max:255',
                'speciality' => 'nullable|string|max:100',
                'year_obtained' => 'nullable|integer|min:1900|max:' . date('Y'),
                'languages' => 'nullable|array',
                'teaching_speciality' => 'nullable|string|max:255',
                'type_contrat' => 'nullable|in:cdi,cdd,vacataire,autre',
                'date_embauche' => 'nullable|date',
                'teacher_category' => 'nullable|string|max:100',
                'categorie_enseignant_id' => 'nullable|exists:categorie_enseignants,id',
                'email' => 'nullable|email|max:100',
                'telephone' => 'nullable|string|max:20',
                'photo' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'statut' => 'required|in:actif,suspendu,conge,retraite,demission',
                'matiere_1_id' => 'nullable|exists:matieres,id',
                'matiere_2_id' => 'nullable|exists:matieres,id',
                'matiere_3_id' => 'nullable|exists:matieres,id',
                'matiere_4_id' => 'nullable|exists:matieres,id',
                'matiere_5_id' => 'nullable|exists:matieres,id',
                'matiere_6_id' => 'nullable|exists:matieres,id',
                'matiere_7_id' => 'nullable|exists:matieres,id',
                'cycle_1_id' => 'nullable|exists:cycles_enseignement,id',
                'cycle_2_id' => 'nullable|exists:cycles_enseignement,id',
                'niveau_1_id' => 'nullable|exists:niveaux,id',
                'niveau_2_id' => 'nullable|exists:niveaux,id',
                'niveau_3_id' => 'nullable|exists:niveaux,id',
                'niveau_4_id' => 'nullable|exists:niveaux,id',
                'classe_1_id' => 'nullable|exists:classes,id',
                'classe_2_id' => 'nullable|exists:classes,id',
                'classe_3_id' => 'nullable|exists:classes,id',
                'classe_4_id' => 'nullable|exists:classes,id',
                'classe_5_id' => 'nullable|exists:classes,id',
            ]);

            // Handle photo upload
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                // Delete old photo if exists
                if ($enseignant->photo && \Storage::disk('public')->exists($enseignant->photo)) {
                    \Storage::disk('public')->delete($enseignant->photo);
                }
                $photoPath = $request->file('photo')->store('enseignants', 'public');
                $validated['photo'] = $photoPath;
            }

            $enseignant->update($validated);

            // Sync pivot tables
            $matiereIds = array_values(array_filter([
                $request->matiere_1_id, $request->matiere_2_id, $request->matiere_3_id,
                $request->matiere_4_id, $request->matiere_5_id, $request->matiere_6_id,
                $request->matiere_7_id,
            ]));
            $cycleIds = array_values(array_filter([$request->cycle_1_id, $request->cycle_2_id]));
            $niveauIds = array_values(array_filter([
                $request->niveau_1_id, $request->niveau_2_id, $request->niveau_3_id, $request->niveau_4_id,
            ]));
            $classeIds = array_values(array_filter([
                $request->classe_1_id, $request->classe_2_id, $request->classe_3_id,
                $request->classe_4_id, $request->classe_5_id,
            ]));

            $enseignant->matieres()->sync($matiereIds);
            $enseignant->cycles()->sync($cycleIds);
            $enseignant->niveaux()->sync($niveauIds);
            $enseignant->classes()->sync($classeIds);

            return redirect()->route('academique.enseignants.index')
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "EnseignantController::update", $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()])->withInput();
        }
    }

    public function activate(Enseignant $enseignant)
    {
        try {
            $newStatut = $enseignant->statut === 'actif' ? 'suspendu' : 'actif';
            $enseignant->statut = $newStatut;
            $enseignant->save();

            return redirect()->route('academique.enseignants.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "EnseignantController::activate", $th->getMessage());
            return redirect()->route('academique.enseignants.index')
                ->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Enseignant $enseignant)
    {
        try {
            $enseignant->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "EnseignantController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
