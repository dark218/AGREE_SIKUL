<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Institution;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\Region;
use Modules\Parametrage\Entities\Departement;
use Modules\Parametrage\Entities\Commune;
use Modules\Parametrage\Entities\Fichier;
use App\Models\User;
use Illuminate\Foundation\Validation\ValidatesRequests;

class InstitutionController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:institutions-list', ['only' => ['index']]);
        $this->middleware('permission.check:institutions-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:institutions-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:institutions-delete', ['only' => ['destroy', 'activate', 'statut']]);
    }

    /**
     * Afficher la liste des institutions
     */
    public function index(Request $request)
    {
        try {
            $query = Institution::query()->with('pays', 'directeurGeneral');

            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('nom', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $institutions = $query->orderBy('nom')->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::Institutions/Index', [
                'institutions' => $institutions,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Exception $e) {
            \Log::error('InstitutionController@index: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement des institutions');
        }
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        try {
            \Log::info('InstitutionController@create - START');

            // Récupérer les pays actifs
            \Log::info('InstitutionController@create - Fetching pays');
            $paysList = Pays::actif()->get(['id', 'libelle', 'code'])->toArray();
            \Log::info('InstitutionController@create - Pays count: ' . count($paysList), ['paysList' => $paysList]);

            // Récupérer les directeurs
            \Log::info('InstitutionController@create - Fetching directeurs');
            $directeurs = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['administrateur', 'directeur', 'super_admin']);
            })->get(['id', 'nom', 'email'])->toArray();
            \Log::info('InstitutionController@create - Directeurs count: ' . count($directeurs), ['directeurs' => $directeurs]);

            // Récupérer les régions, départements et communes
            \Log::info('InstitutionController@create - Fetching regions');
            $regions = Region::actif()->get(['id', 'libelle'])->toArray();

            \Log::info('InstitutionController@create - Fetching departements');
            $departements = Departement::actif()->get(['id', 'libelle'])->toArray();

            \Log::info('InstitutionController@create - Fetching communes');
            $communes = Commune::actif()->get(['id', 'libelle'])->toArray();

            \Log::info('InstitutionController@create - Rendering view');
            return Inertia::render('Parametrage::Institutions/Create', [
                'paysList' => $paysList,
                'directeurs' => $directeurs,
                'regions' => $regions,
                'departements' => $departements,
                'communes' => $communes,
            ]);
        } catch (\Exception $e) {
            \Log::error('InstitutionController@create - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    /**
     * Créer une nouvelle institution
     */
    public function store(Request $request)
    {
        try {
            \Log::info('===== InstitutionController@store - START =====');
            \Log::info('📨 Request all data:', $request->all());

            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:institutions,code',
                'nom' => 'required|string|max:255',
                'sigle' => 'nullable|string|max:50',
                'type' => 'nullable|in:primaire,secondaire,superieur,formation,autre',
                'statut_juridique' => 'nullable|string|max:100',
                'numero_autorisation' => 'nullable|string|max:100',
                'date_creation' => 'nullable|date',
                'directeur_general_id' => 'nullable|exists:users,id',
                'email_principal' => 'nullable|email|max:255',
                'telephone_principal' => 'nullable|string|max:20',
                'site_web' => 'nullable|url|max:255',
                'adresse_siege' => 'nullable|string',
                'code_postal' => 'nullable|string|max:20',
                'boite_postale' => 'nullable|string|max:100',
                'quartier' => 'nullable|string|max:100',
                'commune' => 'nullable|string|max:100',
                'ville' => 'nullable|string|max:100',
                'departement' => 'nullable|string|max:100',
                'region' => 'nullable|string|max:100',
                'pays_id' => 'nullable|exists:pays,id',
                'devise_principale' => 'nullable|string|max:3',
                'ministere_tutelle_1' => 'nullable|string|max:255',
                'ministere_tutelle_2' => 'nullable|string|max:255',
                'ministere_tutelle_3' => 'nullable|string|max:255',
                'ministere_tutelle_4' => 'nullable|string|max:255',
                'telephone_1' => 'nullable|string|max:20',
                'telephone_2' => 'nullable|string|max:20',
                'telephone_3' => 'nullable|string|max:20',
                'whatsapp_1' => 'nullable|string|max:20',
                'whatsapp_2' => 'nullable|string|max:20',
                'fax' => 'nullable|string|max:20',
                'email_1' => 'nullable|email|max:255',
                'email_2' => 'nullable|email|max:255',
                'facebook' => 'nullable|string|max:255',
                'linkedin' => 'nullable|string|max:255',
                'twitter' => 'nullable|string|max:255',
                'fuseau_horaire' => 'nullable|string|max:50',
                'langue_principale' => 'nullable|string|max:10',
                'description' => 'nullable|string',
                'vision' => 'nullable|string',
                'mission' => 'nullable|string',
                'statut' => 'nullable|in:actif,non_actif',
            ]);

            \Log::info('✅ Validation passed!');
            \Log::info('📦 Validated data:', $validated);

            $validated['statut'] = $validated['statut'] ?? 'actif';
            $validated['langue_principale'] = $validated['langue_principale'] ?? 'fr';
            $validated['creation_username'] = auth()->user()->nom;
            $validated['creation_hostname'] = gethostname();

            \Log::info('📝 Data before create:', $validated);

            $institution = Institution::create($validated);

            \Log::info('🎉 Institution created successfully!', ['id' => $institution->id, 'nom' => $institution->nom]);

            return redirect()
                ->route('parametrage.institution.index')
                ->with('success', 'Parametrage créée avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ Validation error:', $e->errors());
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('❌ InstitutionController@store - EXCEPTION:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erreur lors de la création de l\'institution: ' . $e->getMessage());
        }
    }

    /**
     * Afficher les détails d'une institution
     */
    public function show(Institution $institution)
    {
        try {
            $institution->load(['pays', 'directeurGeneral', 'campus']);

            // Convert to array and format date BEFORE passing to Inertia
            $institutionData = $institution->toArray();

            if ($institution->date_creation) {
                $institutionData['date_creation'] = $institution->date_creation->format('Y-m-d');
                \Log::info('✅ InstitutionController@show - date_creation formatted:', [
                    'original' => $institution->getOriginal('date_creation'),
                    'formatted' => $institutionData['date_creation'],
                ]);
            }

            $paysList = Pays::actif()->get(['id', 'libelle', 'code'])->toArray();

            $directeurs = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['administrateur', 'directeur', 'super_admin']);
            })->orderBy('nom')
                ->get(['id', 'nom', 'email'])
                ->toArray();

            return Inertia::render('Parametrage::Institutions/Show', [
                'institution' => $institutionData,
                'paysList' => $paysList,
                'directeurs' => $directeurs,
            ]);
        } catch (\Exception $e) {
            \Log::error('InstitutionController@show: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Institution $institution)
    {
        try {
            \Log::info('InstitutionController@edit - Loading institution ID: ' . $institution->id);

            $institution->load('pays', 'directeurGeneral');

            // Convert to array and format date BEFORE passing to Inertia
            $institutionData = $institution->toArray();

            if ($institution->date_creation) {
                $institutionData['date_creation'] = $institution->date_creation->format('Y-m-d');
                \Log::info('✅ InstitutionController@edit - date_creation formatted:', [
                    'original' => $institution->getOriginal('date_creation'),
                    'formatted' => $institutionData['date_creation'],
                ]);
            }

            $paysList = Pays::actif()->get(['id', 'libelle', 'code'])->toArray();
            $directeurs = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['administrateur', 'directeur', 'super_admin']);
            })->get(['id', 'nom', 'email'])->toArray();

            \Log::info('InstitutionController@edit - Rendering view with institution:', ['id' => $institution->id, 'nom' => $institution->nom]);

            return Inertia::render('Parametrage::Institutions/Edit', [
                'institution' => $institutionData,
                'paysList' => $paysList,
                'directeurs' => $directeurs,
            ]);
        } catch (\Exception $e) {
            \Log::error('InstitutionController@edit - EXCEPTION:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour une institution
     */
    public function update(Request $request, Institution $institution)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:institutions,code,' . $institution->id,
                'nom' => 'required|string|max:255',
                'sigle' => 'nullable|string|max:50',
                'type' => 'nullable|in:primaire,secondaire,superieur,formation,autre',
                'statut_juridique' => 'nullable|string|max:100',
                'numero_autorisation' => 'nullable|string|max:100',
                'date_creation' => 'nullable|date',
                'directeur_general_id' => 'nullable|exists:users,id',
                'email_principal' => 'nullable|email|max:255',
                'telephone_principal' => 'nullable|string|max:20',
                'site_web' => 'nullable|url|max:255',
                'adresse_siege' => 'nullable|string',
                'code_postal' => 'nullable|string|max:20',
                'boite_postale' => 'nullable|string|max:100',
                'quartier' => 'nullable|string|max:100',
                'commune' => 'nullable|string|max:100',
                'ville' => 'nullable|string|max:100',
                'departement' => 'nullable|string|max:100',
                'region' => 'nullable|string|max:100',
                'pays_id' => 'nullable|exists:pays,id',
                'devise_principale' => 'nullable|string|max:3',
                'ministere_tutelle_1' => 'nullable|string|max:255',
                'ministere_tutelle_2' => 'nullable|string|max:255',
                'ministere_tutelle_3' => 'nullable|string|max:255',
                'ministere_tutelle_4' => 'nullable|string|max:255',
                'telephone_1' => 'nullable|string|max:20',
                'telephone_2' => 'nullable|string|max:20',
                'telephone_3' => 'nullable|string|max:20',
                'whatsapp_1' => 'nullable|string|max:20',
                'whatsapp_2' => 'nullable|string|max:20',
                'fax' => 'nullable|string|max:20',
                'email_1' => 'nullable|email|max:255',
                'email_2' => 'nullable|email|max:255',
                'facebook' => 'nullable|string|max:255',
                'linkedin' => 'nullable|string|max:255',
                'twitter' => 'nullable|string|max:255',
                'fuseau_horaire' => 'nullable|string|max:50',
                'langue_principale' => 'nullable|string|max:10',
                'description' => 'nullable|string',
                'vision' => 'nullable|string',
                'mission' => 'nullable|string',
                'statut' => 'nullable|in:actif,non_actif',
            ]);

            $validated['modification_username'] = auth()->user()->nom;
            $validated['modification_hostname'] = gethostname();

            $institution->update($validated);

            return redirect()
                ->route('parametrage.institution.index')
                ->with('success', 'Parametrage modifiée avec succès');
        } catch (\Exception $e) {
            \Log::error('InstitutionController@update: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    /**
     * Supprimer (soft delete) une institution
     */
    public function destroy(Institution $institution)
    {
        try {
            $institution->deletion_username = auth()->user()->nom;
            $institution->deletion_hostname = gethostname();
            $institution->save();
            $institution->delete();

            return redirect()->route('parametrage.institution.index')->with('success', 'Parametrage supprimée avec succès');
        } catch (\Exception $e) {
            \Log::error('InstitutionController@destroy: ' . $e->getMessage());
            return redirect()->route('parametrage.institution.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    /**
     * Activer/Désactiver une institution
     */
    public function activate(Institution $institution)
    {
        try {
            if ($institution->statut === 'actif') {
                $institution->statut = 'non_actif';
                $message = 'Parametrage désactivée';
            } else {
                $institution->statut = 'actif';
                $message = 'Parametrage activée';
            }

            $institution->modification_username = auth()->user()->nom;
            $institution->modification_hostname = gethostname();
            $institution->save();

            return redirect()->route('parametrage.institution.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('InstitutionController@activate: ' . $e->getMessage());
            return redirect()->route('parametrage.institution.index')->with('error', 'Erreur lors du changement de statut');
        }
    }

    /**
     * Toggle statut between actif and inactif
     */
    public function statut(Institution $institution)
    {
        try {
            \Log::info('🔵 [INSTITUTION] statut() START - ID: ' . $institution->id);
            \Log::info('   Current statut: ' . $institution->statut);
            \Log::info('   Fillable: ' . implode(', ', $institution->getFillable()));

            $newStatut = $institution->statut === 'actif' ? 'non_actif' : 'actif';
            \Log::info('   Toggling to: ' . $newStatut);

            $institution->statut = $newStatut;
            \Log::info('   After assignment - statut: ' . $institution->statut);
            \Log::info('   Auth ID: ' . auth()->id());

            \Log::info('   About to save...');

            $saved = $institution->save();
            \Log::info('   Save result: ' . ($saved ? 'SUCCESS' : 'FAILED'));

            $message = $newStatut === 'actif' ? 'Activé' : 'Désactivé';
            \Log::info('   ✅ Institution ' . $message . ' avec succès');
            return redirect()->route('parametrage.institution.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('❌ EXCEPTION in statut(): ' . $e->getMessage());
            \Log::error('   Code: ' . $e->getCode());
            \Log::error('   File: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('   Trace: ' . $e->getTraceAsString());
            return redirect()->route('parametrage.institution.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
