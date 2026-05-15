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
use Modules\Parametrage\Http\Controllers\Concerns\ProvidesParametrageLookups;
use Modules\Parametrage\Http\Requests\StoreCampusRequest;
use Modules\Parametrage\Http\Requests\UpdateCampusRequest;
use App\Models\User;
use Illuminate\Foundation\Validation\ValidatesRequests;

class CampusController extends Controller
{
    use ValidatesRequests, ProvidesParametrageLookups;

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
            return Inertia::render('Parametrage::Campuses/Create', $this->campusLookups());
        } catch (\Exception $e) {
            \Log::error('CampusController@create - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    /**
     * Créer un nouveau campus
     */
    public function store(StoreCampusRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['statut'] = $validated['statut'] ?? 'actif';
            $validated['creation_username'] = auth()->user()->nom;
            $validated['creation_hostname'] = gethostname();

            Campus::create($validated);

            return redirect()
                ->route('parametrage.campuses.index')
                ->with('success', 'Campus créé avec succès');
        } catch (\Exception $e) {
            \Log::error('CampusController@store - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors de la création du campus: ' . $e->getMessage())->withInput();
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
            return Inertia::render('Parametrage::Campuses/Edit', array_merge(
                $this->campusLookups(),
                ['campus' => $campus]
            ));
        } catch (\Exception $e) {
            \Log::error('CampusController@edit - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    /**
     * Mettre à jour un campus
     */
    public function update(UpdateCampusRequest $request, Campus $campus)
    {
        try {
            $validated = $request->validated();
            $validated['modification_username'] = auth()->user()->nom;
            $validated['modification_hostname'] = gethostname();

            $campus->update($validated);

            return redirect()
                ->route('parametrage.campuses.index')
                ->with('success', 'Campus modifié avec succès');
        } catch (\Exception $e) {
            \Log::error('CampusController@update - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors de la modification: ' . $e->getMessage())->withInput();
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
