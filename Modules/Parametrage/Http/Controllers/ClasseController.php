<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Niveau;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Http\Controllers\Concerns\ProvidesParametrageLookups;
use Modules\Parametrage\Http\Requests\StoreClasseRequest;
use Modules\Parametrage\Http\Requests\UpdateClasseRequest;
use App\Models\User;
use Illuminate\Foundation\Validation\ValidatesRequests;

class ClasseController extends Controller
{
    use ValidatesRequests, ProvidesParametrageLookups;

    public function __construct()
    {
        $this->middleware('permission.check:classes-list', ['only' => ['index']]);
        $this->middleware('permission.check:classes-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:classes-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:classes-delete', ['only' => ['destroy', 'activate', 'statut']]);
    }

    /**
     * Afficher la liste des classes
     */
    public function index(Request $request)
    {
        try {
            $query = Classe::query()->with('ecole', 'niveau');

            if ($request->filled('search')) {
                $query->where('nom', 'like', '%' . $request->search . '%')
                    ->orWhere('salle', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('ecole_id')) {
                $query->where('ecole_id', $request->ecole_id);
            }

            if ($request->filled('niveau_id')) {
                $query->where('niveau_id', $request->niveau_id);
            }

            $classes = $query->orderBy('nom')->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::Classes/Index', [
                'classes' => $classes,
                'filters' => $request->only(['search', 'statut', 'ecole_id', 'niveau_id']),
            ]);
        } catch (\Exception $e) {
            \Log::error('ClasseController@index: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement des classes');
        }
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        try {
            return Inertia::render('Parametrage::Classes/Create', $this->classeLookups());
        } catch (\Exception $e) {
            \Log::error('ClasseController@create - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    /**
     * Créer une nouvelle classe
     */
    public function store(StoreClasseRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['statut'] = $validated['statut'] ?? 'actif';
            // Mapper libelle vers nom legacy si nom absent (pour compat ancienne data)
            if (empty($validated['nom']) && !empty($validated['libelle'])) {
                $validated['nom'] = $validated['libelle'];
            }
            $validated['creation_username'] = auth()->user()->nom;
            $validated['creation_hostname'] = gethostname();

            Classe::create($validated);

            return redirect()
                ->route('parametrage.classes.index')
                ->with('success', 'Classe créée avec succès');
        } catch (\Exception $e) {
            \Log::error('ClasseController@store - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors de la création de la classe: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Afficher les détails d'une classe
     */
    public function show(Classe $classe)
    {
        try {
            $classe->load(['ecole', 'niveau', 'section', 'cycle', 'enseignantTitulaire', 'anneeScolaire', 'campus']);

            $ecoles = Ecole::where('statut', 'actif')
                ->orderBy('nom')
                ->get(['id', 'nom', 'code']);

            $niveaux = Niveau::orderBy('ordre')
                ->get(['id', 'libelle']);

            $sections = Section::orderBy('libelle')
                ->get(['id', 'libelle']);

            $cycles = CycleEnseignement::orderBy('libelle')
                ->get(['id', 'libelle']);

            $enseignants = User::orderBy('nom')
                ->get(['id', 'nom', 'prenoms']);

            $anneesScolaires = AnneeScolaire::where('etat', 'actif')
                ->orderBy('libelle', 'desc')
                ->get(['id', 'libelle']);

            $campuses = Campus::orderBy('nom')
                ->get(['id', 'nom as libelle']);

            return Inertia::render('Parametrage::Classes/Show', [
                'classe' => $classe,
                'ecoles' => $ecoles,
                'niveaux' => $niveaux,
                'sections' => $sections,
                'cycles' => $cycles,
                'enseignants' => $enseignants,
                'anneesScolaires' => $anneesScolaires,
                'campuses' => $campuses,
            ]);
        } catch (\Exception $e) {
            \Log::error('ClasseController@show: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Classe $classe)
    {
        try {
            $classe->load('ecole', 'niveau', 'section', 'cycle', 'enseignantTitulaire', 'anneeScolaire', 'campus');
            return Inertia::render('Parametrage::Classes/Edit', array_merge(
                $this->classeLookups(),
                ['classe' => $classe]
            ));
        } catch (\Exception $e) {
            \Log::error('ClasseController@edit - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    /**
     * Mettre à jour une classe
     */
    public function update(UpdateClasseRequest $request, Classe $classe)
    {
        try {
            $validated = $request->validated();
            $validated['modification_username'] = auth()->user()->nom;
            $validated['modification_hostname'] = gethostname();
            if (empty($validated['nom']) && !empty($validated['libelle'])) {
                $validated['nom'] = $validated['libelle'];
            }

            $classe->update($validated);

            return redirect()
                ->route('parametrage.classes.index')
                ->with('success', 'Classe modifiée avec succès');
        } catch (\Exception $e) {
            \Log::error('ClasseController@update - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors de la modification: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Supprimer (soft delete) une classe
     */
    public function destroy(Classe $classe)
    {
        try {
            $classe->deletion_username = auth()->user()->nom;
            $classe->deletion_hostname = gethostname();
            $classe->save();
            $classe->delete();

            return redirect()->route('parametrage.classes.index')->with('success', 'Classe supprimée avec succès');
        } catch (\Exception $e) {
            \Log::error('ClasseController@destroy: ' . $e->getMessage());
            return redirect()->route('parametrage.classes.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    /**
     * Activer/Désactiver une classe
     */
    public function activate(Classe $classe)
    {
        try {
            $newStatut = $classe->statut === 'actif' ? 'non_actif' : 'actif';
            $classe->statut = $newStatut;
            $classe->modification_username = auth()->user()->nom;
            $classe->modification_hostname = gethostname();
            $classe->save();

            $message = $newStatut === 'actif' ? 'Activée' : 'Désactivée';
            return redirect()->route('parametrage.classes.index')->with('success', 'Classe ' . $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('ClasseController@activate: ' . $e->getMessage());
            return redirect()->route('parametrage.classes.index')->with('error', 'Erreur lors du changement de statut');
        }
    }

    /**
     * Toggle statut between actif and inactif
     */
    public function statut(Classe $classe)
    {
        try {
            \Log::info('🔵 [CLASSE] statut() START - ID: ' . $classe->id);
            \Log::info('   Current statut: ' . $classe->statut);
            \Log::info('   Fillable: ' . implode(', ', $classe->getFillable()));

            $newStatut = $classe->statut === 'actif' ? 'non_actif' : 'actif';
            \Log::info('   Toggling to: ' . $newStatut);

            $classe->statut = $newStatut;
            \Log::info('   After assignment - statut: ' . $classe->statut);
            \Log::info('   Auth ID: ' . auth()->id());

            \Log::info('   About to save...');

            $saved = $classe->save();
            \Log::info('   Save result: ' . ($saved ? 'SUCCESS' : 'FAILED'));

            $message = $newStatut === 'actif' ? 'Activé' : 'Désactivé';
            \Log::info('   ✅ Classe ' . $message . ' avec succès');
            return redirect()->route('parametrage.classes.index')->with('success', 'Classe ' . $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('❌ EXCEPTION in statut(): ' . $e->getMessage());
            \Log::error('   Code: ' . $e->getCode());
            \Log::error('   File: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('   Trace: ' . $e->getTraceAsString());
            return redirect()->route('parametrage.classes.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * API endpoint: retourner les détails d'une classe pour auto-fill
     */
    public function apiShow($id)
    {
        try {
            $classe = Classe::find($id);

            if (!$classe) {
                return response()->json(['error' => 'Classe non trouvée'], 404);
            }

            return response()->json([
                'id' => $classe->id,
                'ecole_id' => $classe->ecole_id,
                'campus_id' => $classe->campus_id,
                'section_id' => $classe->section_id,
                'cycle_id' => $classe->cycle_id,
                'niveau_id' => $classe->niveau_id,
                'annee_scolaire_id' => $classe->annee_scolaire_id,
            ]);
        } catch (\Exception $e) {
            \Log::error('ClasseController@apiShow: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors du chargement'], 500);
        }
    }
}
