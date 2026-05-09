<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Niveau;
use Modules\Parametrage\Entities\Ecole;
use Illuminate\Foundation\Validation\ValidatesRequests;

class NiveauController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:niveaux-list', ['only' => ['index']]);
        $this->middleware('permission.check:niveaux-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:niveaux-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:niveaux-delete', ['only' => ['destroy', 'activate', 'statut']]);
    }

    /**
     * Afficher la liste des niveaux
     */
    public function index(Request $request)
    {
        try {
            $query = Niveau::query()->with('ecole');

            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('libelle', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('ecole_id')) {
                $query->where('ecole_id', $request->ecole_id);
            }

            $niveaux = $query->orderBy('ordre')->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::Niveaux/Index', [
                'niveaux' => $niveaux,
                'filters' => $request->only(['search', 'statut', 'ecole_id']),
            ]);
        } catch (\Exception $e) {
            \Log::error('NiveauController@index: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement des niveaux');
        }
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        try {
            \Log::info('NiveauController@create - START');

            // Récupérer les écoles actives
            \Log::info('NiveauController@create - Fetching ecoles');
            $ecoles = Ecole::where('statut', 'actif')
                ->with('campus')
                ->orderBy('nom')
                ->get(['id', 'nom', 'code', 'campus_id'])
                ->toArray();
            \Log::info('NiveauController@create - Ecoles count: ' . count($ecoles), ['ecoles' => $ecoles]);

            \Log::info('NiveauController@create - Rendering view');
            return Inertia::render('Parametrage::Niveaux/Create', [
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            \Log::error('NiveauController@create - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    /**
     * Créer un nouveau niveau
     */
    public function store(Request $request)
    {
        try {
            \Log::info('===== NiveauController@store - START =====');
            \Log::info('📨 Request all data:', $request->all());

            $validated = $request->validate([
                'ecole_id' => 'required|exists:ecoles,id',
                'code' => 'required|string|max:100',
                'libelle' => 'required|string|max:255',
                'ordre' => 'required|integer|min:1',
                'age_min' => 'nullable|integer|min:1',
                'age_max' => 'nullable|integer|min:1',
                'cycle' => 'nullable|string|max:100',
                'statut' => 'nullable|in:actif,non_actif,suspendu',
            ]);

            \Log::info('✅ Validation passed!');
            \Log::info('📦 Validated data:', $validated);

            $validated['statut'] = $validated['statut'] ?? 'actif';
            $validated['creation_username'] = auth()->user()->nom;
            $validated['creation_hostname'] = gethostname();

            \Log::info('📝 Data before create:', $validated);

            $niveau = Niveau::create($validated);

            \Log::info('🎉 Niveau created successfully!', ['id' => $niveau->id, 'libelle' => $niveau->libelle]);

            return redirect()
                ->route('parametrage.niveaux.index')
                ->with('success', 'Niveau créé avec succès');
        } catch (\Exception $e) {
            \Log::error('❌ NiveauController@store - EXCEPTION:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erreur lors de la création du niveau: ' . $e->getMessage());
        }
    }

    /**
     * Afficher les détails d'un niveau
     */
    public function show(Niveau $niveau)
    {
        try {
            $niveau->load(['ecole', 'classes']);

            $ecoles = Ecole::where('statut', 'actif')
                ->orderBy('nom')
                ->get(['id', 'nom', 'code']);

            return Inertia::render('Parametrage::Niveaux/Show', [
                'niveau' => $niveau,
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            \Log::error('NiveauController@show: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Niveau $niveau)
    {
        try {
            $niveau->load('ecole');

            $ecoles = Ecole::actif()
                ->with('campus')
                ->orderBy('nom')
                ->get(['id', 'nom', 'code', 'campus_id'])
                ->toArray();

            return Inertia::render('Parametrage::Niveaux/Edit', [
                'niveau' => $niveau,
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            \Log::error('NiveauController@edit: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    /**
     * Mettre à jour un niveau
     */
    public function update(Request $request, Niveau $niveau)
    {
        try {
            $validated = $request->validate([
                'ecole_id' => 'required|exists:ecoles,id',
                'code' => 'required|string|max:100',
                'libelle' => 'required|string|max:255',
                'ordre' => 'required|integer|min:1',
                'age_min' => 'nullable|integer|min:1',
                'age_max' => 'nullable|integer|min:1',
                'cycle' => 'nullable|string|max:100',
                'statut' => 'nullable|in:actif,non_actif,suspendu',
            ]);

            $validated['modification_username'] = auth()->user()->nom;
            $validated['modification_hostname'] = gethostname();

            $niveau->update($validated);

            return redirect()
                ->route('parametrage.niveaux.index')
                ->with('success', 'Niveau modifié avec succès');
        } catch (\Exception $e) {
            \Log::error('NiveauController@update: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    /**
     * Supprimer (soft delete) un niveau
     */
    public function destroy(Niveau $niveau)
    {
        try {
            $niveau->deletion_username = auth()->user()->nom;
            $niveau->deletion_hostname = gethostname();
            $niveau->save();
            $niveau->delete();

            return redirect()->route('parametrage.niveaux.index')->with('success', 'Niveau supprimé avec succès');
        } catch (\Exception $e) {
            \Log::error('NiveauController@destroy: ' . $e->getMessage());
            return redirect()->route('parametrage.niveaux.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    /**
     * Activer/Désactiver un niveau
     */
    public function activate(Niveau $niveau)
    {
        try {
            $newStatut = $niveau->statut === 'actif' ? 'non_actif' : 'actif';
            $niveau->statut = $newStatut;
            $niveau->modification_username = auth()->user()->nom;
            $niveau->modification_hostname = gethostname();
            $niveau->save();

            $message = $newStatut === 'actif' ? 'Niveau activé' : 'Niveau désactivé';
            return redirect()->route('parametrage.niveaux.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('NiveauController@activate: ' . $e->getMessage());
            return redirect()->route('parametrage.niveaux.index')->with('error', 'Erreur lors du changement de statut');
        }
    }

    /**
     * Toggle statut between actif and inactif
     */
    public function statut(Niveau $niveau)
    {
        try {
            \Log::info('🔵 [NIVEAU] statut() START - ID: ' . $niveau->id);
            \Log::info('   Current statut: ' . $niveau->statut);
            \Log::info('   Fillable: ' . implode(', ', $niveau->getFillable()));

            $newStatut = $niveau->statut === 'actif' ? 'non_actif' : 'actif';
            \Log::info('   Toggling to: ' . $newStatut);

            $niveau->statut = $newStatut;
            \Log::info('   After assignment - statut: ' . $niveau->statut);
            \Log::info('   Auth ID: ' . auth()->id());

            \Log::info('   About to save...');

            $saved = $niveau->save();
            \Log::info('   Save result: ' . ($saved ? 'SUCCESS' : 'FAILED'));

            $message = $newStatut === 'actif' ? 'Activé' : 'Désactivé';
            \Log::info('   ✅ Niveau ' . $message . ' avec succès');
            return redirect()->route('parametrage.niveaux.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('❌ EXCEPTION in statut(): ' . $e->getMessage());
            \Log::error('   Code: ' . $e->getCode());
            \Log::error('   File: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('   Trace: ' . $e->getTraceAsString());
            return redirect()->route('parametrage.niveaux.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
