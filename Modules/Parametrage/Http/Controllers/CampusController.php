<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\Institution;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\Commune;
use Modules\Parametrage\Entities\Departement;
use Modules\Parametrage\Entities\Quartier;
use Modules\Parametrage\Entities\Region;
use App\Models\User;
use Illuminate\Foundation\Validation\ValidatesRequests;

class CampusController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:campuses-list', ['only' => ['index']]);
        $this->middleware('permission.check:campuses-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:campuses-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:campuses-delete', ['only' => ['destroy', 'activate', 'statut']]);
    }

    /**
     * Afficher la liste des campus
     */
    public function index(Request $request)
    {
        try {
            $query = Campus::query()->with('institution', 'responsable');

            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('nom', 'like', '%' . $request->search . '%')
                    ->orWhere('ville', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('institution_id')) {
                $query->where('institution_id', $request->institution_id);
            }

            $campuses = $query->orderBy('nom')->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::Campuses/Index', [
                'campuses' => $campuses,
                'filters' => $request->only(['search', 'statut', 'institution_id']),
            ]);
        } catch (\Exception $e) {
            \Log::error('CampusController@index: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement des campus');
        }
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        try {
            \Log::info('CampusController@create - START');

            // Récupérer les institutions actives
            \Log::info('CampusController@create - Fetching institutions');
            $institutions = Institution::where('statut', 'actif')
                ->orderBy('nom')
                ->get(['id', 'nom', 'code'])
                ->toArray();
            \Log::info('CampusController@create - Institutions count: ' . count($institutions), ['institutions' => $institutions]);

            // Récupérer les responsables
            \Log::info('CampusController@create - Fetching responsables');
            $responsables = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['administrateur', 'directeur', 'super_admin']);
            })->orderBy('nom')
                ->get(['id', 'nom', 'email'])
                ->toArray();
            \Log::info('CampusController@create - Responsables count: ' . count($responsables), ['responsables' => $responsables]);

            // Récupérer les pays
            \Log::info('CampusController@create - Fetching pays');
            $paysList = Pays::actif()->get(['id', 'libelle', 'code'])->toArray();
            \Log::info('CampusController@create - Pays count: ' . count($paysList), ['paysList' => $paysList]);

            // Récupérer les communes, departements, quartiers, regions
            \Log::info('CampusController@create - Fetching geographical data');
            $communes = Commune::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();
            $departements = Departement::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();
            $quartiers = Quartier::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();
            $regions = Region::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();
            \Log::info('CampusController@create - Geographical data counts', [
                'communes' => count($communes),
                'departements' => count($departements),
                'quartiers' => count($quartiers),
                'regions' => count($regions)
            ]);

            \Log::info('CampusController@create - Rendering view');
            return Inertia::render('Parametrage::Campuses/Create', [
                'institutions' => $institutions,
                'responsables' => $responsables,
                'paysList' => $paysList,
                'communes' => $communes,
                'departements' => $departements,
                'quartiers' => $quartiers,
                'regions' => $regions,
            ]);
        } catch (\Exception $e) {
            \Log::error('CampusController@create - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    /**
     * Créer un nouveau campus
     */
    public function store(Request $request)
    {
        try {
            \Log::info('===== CampusController@store - START =====');
            \Log::info('📨 Request all data:', $request->all());

            $validated = $request->validate([
                'institution_id' => 'required|exists:institutions,id',
                'code' => 'required|string|max:100|unique:campuses,code',
                'nom' => 'required|string|max:255',
                'adresse' => 'nullable|string',
                'ville' => 'required|string|max:100',
                'code_postal' => 'nullable|string|max:20',
                'boite_postale' => 'nullable|string|max:100',
                'quartier' => 'nullable|string|max:100',
                'commune' => 'nullable|string|max:100',
                'departement' => 'nullable|string|max:100',
                'region' => 'nullable|string|max:100',
                'pays_id' => 'nullable|exists:pays,id',
                'longitude' => 'nullable|numeric|between:-180,180',
                'latitude' => 'nullable|numeric|between:-90,90',
                'telephone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'responsable_id' => 'nullable|exists:users,id',
                'statut' => 'nullable|in:actif,non_actif',
            ]);

            \Log::info('✅ Validation passed!');
            \Log::info('📦 Validated data:', $validated);

            $validated['statut'] = $validated['statut'] ?? 'actif';
            $validated['creation_username'] = auth()->user()->nom;
            $validated['creation_hostname'] = gethostname();

            \Log::info('📝 Data before create:', $validated);

            $campus = Campus::create($validated);

            \Log::info('🎉 Campus created successfully!', ['id' => $campus->id, 'nom' => $campus->nom]);

            return redirect()
                ->route('parametrage.campuses.index')
                ->with('success', 'Parametrage créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ Validation error:', $e->errors());
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('❌ CampusController@store - EXCEPTION:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erreur lors de la création du campus: ' . $e->getMessage());
        }
    }

    /**
     * Afficher les détails d'un campus
     */
    public function show(Campus $campus)
    {
        try {
            $campus->load(['institution', 'responsable', 'ecoles']);

            $institutions = Institution::where('statut', 'actif')
                ->orderBy('nom')
                ->get(['id', 'nom', 'code'])
                ->toArray();

            $responsables = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['administrateur', 'directeur', 'super_admin']);
            })->orderBy('nom')
                ->get(['id', 'nom', 'email'])
                ->toArray();

            // Récupérer les pays
            $paysList = Pays::actif()->get(['id', 'libelle', 'code'])->toArray();

            // Récupérer les communes, departements, quartiers, regions
            $communes = Commune::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();
            $departements = Departement::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();
            $quartiers = Quartier::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();
            $regions = Region::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();

            return Inertia::render('Parametrage::Campuses/Show', [
                'campus' => $campus,
                'institutions' => $institutions,
                'responsables' => $responsables,
                'paysList' => $paysList,
                'communes' => $communes,
                'departements' => $departements,
                'quartiers' => $quartiers,
                'regions' => $regions,
            ]);
        } catch (\Exception $e) {
            \Log::error('CampusController@show - EXCEPTION:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement: ' . $e->getMessage());
        }
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Campus $campus)
    {
        try {
            $campus->load('institution', 'responsable');

            $institutions = Institution::actif()
                ->orderBy('nom')
                ->get(['id', 'nom', 'code'])
                ->toArray();

            $responsables = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['administrateur', 'directeur', 'super_admin']);
            })->orderBy('nom')
                ->get(['id', 'nom', 'email'])
                ->toArray();

            // Récupérer les pays
            $paysList = Pays::actif()->get(['id', 'libelle', 'code'])->toArray();

            // Récupérer les communes, departements, quartiers, regions
            $communes = Commune::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();
            $departements = Departement::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();
            $quartiers = Quartier::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();
            $regions = Region::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray();

            return Inertia::render('Parametrage::Campuses/Edit', [
                'campus' => $campus,
                'institutions' => $institutions,
                'responsables' => $responsables,
                'paysList' => $paysList,
                'communes' => $communes,
                'departements' => $departements,
                'quartiers' => $quartiers,
                'regions' => $regions,
            ]);
        } catch (\Exception $e) {
            \Log::error('CampusController@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    /**
     * Mettre à jour un campus
     */
    public function update(Request $request, Campus $campus)
    {
        try {
            $validated = $request->validate([
                'institution_id' => 'required|exists:institutions,id',
                'code' => 'required|string|max:100|unique:campuses,code,' . $campus->id,
                'nom' => 'required|string|max:255',
                'adresse' => 'nullable|string',
                'ville' => 'required|string|max:100',
                'code_postal' => 'nullable|string|max:20',
                'boite_postale' => 'nullable|string|max:100',
                'quartier' => 'nullable|string|max:100',
                'commune' => 'nullable|string|max:100',
                'departement' => 'nullable|string|max:100',
                'region' => 'nullable|string|max:100',
                'pays_id' => 'nullable|exists:pays,id',
                'longitude' => 'nullable|numeric|between:-180,180',
                'latitude' => 'nullable|numeric|between:-90,90',
                'telephone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'responsable_id' => 'nullable|exists:users,id',
                'statut' => 'nullable|in:actif,non_actif',
            ]);

            $validated['modification_username'] = auth()->user()->nom;
            $validated['modification_hostname'] = gethostname();

            $campus->update($validated);

            return redirect()
                ->route('parametrage.campuses.index')
                ->with('success', 'Parametrage modifié avec succès');
        } catch (\Exception $e) {
            \Log::error('CampusController@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    /**
     * Supprimer (soft delete) un campus
     */
    public function destroy(Campus $campus)
    {
        try {
            $campus->deletion_username = auth()->user()->nom;
            $campus->deletion_hostname = gethostname();
            $campus->save();
            $campus->delete();

            return redirect()->route('parametrage.campuses.index')->with('success', 'Parametrage supprimé avec succès');
        } catch (\Exception $e) {
            \Log::error('CampusController@error: ' . $e->getMessage());
            return redirect()->route('parametrage.campuses.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    /**
     * Activer/Désactiver un campus
     */
    public function activate(Campus $campus)
    {
        try {
            $newStatut = $campus->statut === 'actif' ? 'non_actif' : 'actif';
            $campus->statut = $newStatut;
            $campus->modification_username = auth()->user()->nom;
            $campus->modification_hostname = gethostname();
            $campus->save();

            $message = $newStatut === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.campuses.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('CampusController@error: ' . $e->getMessage());
            return redirect()->route('parametrage.campuses.index')->with('error', 'Erreur lors du changement de statut');
        }
    }

    /**
     * Toggle statut between actif and inactif
     */
    public function statut(Campus $campus)
    {
        try {
            \Log::info('🔵 [CAMPUS] statut() START - ID: ' . $campus->id);
            \Log::info('   Current statut: ' . $campus->statut);
            \Log::info('   Fillable: ' . implode(', ', $campus->getFillable()));

            $newStatut = $campus->statut === 'actif' ? 'non_actif' : 'actif';
            \Log::info('   Toggling to: ' . $newStatut);

            $campus->statut = $newStatut;
            \Log::info('   After assignment - statut: ' . $campus->statut);
            \Log::info('   Auth ID: ' . auth()->id());

            \Log::info('   About to save...');

            $saved = $campus->save();
            \Log::info('   Save result: ' . ($saved ? 'SUCCESS' : 'FAILED'));

            $message = $newStatut === 'actif' ? 'Activé' : 'Désactivé';
            \Log::info('   ✅ Campus ' . $message . ' avec succès');
            return redirect()->route('parametrage.campuses.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('❌ EXCEPTION in statut(): ' . $e->getMessage());
            \Log::error('   Code: ' . $e->getCode());
            \Log::error('   File: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('   Trace: ' . $e->getTraceAsString());
            return redirect()->route('parametrage.campuses.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
