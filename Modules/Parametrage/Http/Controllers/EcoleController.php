<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\EcoleDirigent;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\Institution;
use Modules\Parametrage\Entities\TypeCours;
use Modules\Parametrage\Entities\TypeEtablissement;
use App\Models\User;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;

class EcoleController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:ecoles-list', ['only' => ['index']]);
        $this->middleware('permission.check:ecoles-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:ecoles-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:ecoles-delete', ['only' => ['destroy', 'activate', 'statut']]);
    }

    /**
     * Afficher la liste des écoles
     */
    public function index(Request $request)
    {
        try {
            $query = Ecole::query()->with('campus', 'directeur');

            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('nom', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('campus_id')) {
                $query->where('campus_id', $request->campus_id);
            }

            $ecoles = $query->orderBy('nom')->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::Ecoles/Index', [
                'ecoles' => $ecoles,
                'filters' => $request->only(['search', 'statut', 'campus_id']),
            ]);
        } catch (\Exception $e) {
            Log::error('ecolecontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement des écoles');
        }
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        try {
            Log::info('EcoleController@create - START');

            // Récupérer les campus actifs
            Log::info('EcoleController@create - Fetching campuses');
            $campuses = Campus::where('statut', 'actif')
                ->with('institution')
                ->orderBy('nom')
                ->get()
                ->map(function ($campus) {
                    return [
                        'id' => $campus->id,
                        'nom' => $campus->nom . ' (' . $campus->institution->nom . ')',
                        'institution_id' => $campus->institution_id,
                    ];
                })
                ->toArray();
            Log::info('EcoleController@create - Campuses count: ' . count($campuses), ['campuses' => $campuses]);

            // Récupérer les directeurs
            Log::info('EcoleController@create - Fetching directeurs');
            $directeurs = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['administrateur', 'directeur', 'super_admin']);
            })->orderBy('nom')
                ->get(['id', 'nom', 'email'])
                ->toArray();
            Log::info('EcoleController@create - Directeurs count: ' . count($directeurs), ['directeurs' => $directeurs]);

            // Récupérer les pays
            Log::info('EcoleController@create - Fetching pays');
            $paysList = Pays::actif()->get(['id', 'libelle', 'code'])->toArray();
            Log::info('EcoleController@create - Pays count: ' . count($paysList), ['paysList' => $paysList]);

            // Récupérer les types d'établissement
            Log::info('EcoleController@create - Fetching typeEtablissements');
            $typeEtablissements = TypeEtablissement::actif()->get(['id', 'libelle'])->toArray();
            
            // Récupérer les types de cours
            Log::info('EcoleController@create - Fetching typeCours');
            $typeCours = TypeCours::actif()->get(['id', 'libelle'])->toArray();
            
            // Récupérer les institutions
            Log::info('EcoleController@create - Fetching institutions');
            $institutions = Institution::actif()->get(['id', 'nom'])->toArray();

            Log::info('EcoleController@create - Rendering view');
            return Inertia::render('Parametrage::Ecoles/Create', [
                'campuses' => $campuses,
                'directeurs' => $directeurs,
                'paysList' => $paysList,
                'typeEtablissements' => $typeEtablissements,
                'typeCours' => $typeCours,
                'institutions' => $institutions,
            ]);
        } catch (\Exception $e) {
            \Log::error('EcoleController@create - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    /**
     * Créer une nouvelle école
     */
    public function store(Request $request)
    {
        try {
            \Log::info('===== EcoleController@store - START =====');
            \Log::info('📨 Request all data:', $request->all());

            $validated = $request->validate([
                'campus_id' => 'required|exists:campuses,id',
                'code' => 'required|string|max:100|unique:ecoles,code',
                'nom' => 'required|string|max:255',
                'type_enseignement' => 'nullable|in:primaire,secondaire,superieur,formation,autre',
                'directeur_id' => 'nullable|exists:users,id',
                'capacite_totale' => 'nullable|integer|min:1',
                'statut' => 'nullable|in:actif,non_actif,suspendu',
                // Adresse et localisation
                'adresse_siege' => 'nullable|string',
                'code_postal' => 'nullable|string|max:20',
                'boite_postale' => 'nullable|string|max:100',
                'ville' => 'nullable|string|max:100',
                'quartier' => 'nullable|string|max:100',
                'commune' => 'nullable|string|max:100',
                'departement' => 'nullable|string|max:100',
                'region' => 'nullable|string|max:100',
                'pays_id' => 'nullable|exists:pays,id',
                // Contacts - Téléphones
                'telephone_principal' => 'nullable|string|max:20',
                'telephone_2' => 'nullable|string|max:20',
                'telephone_3' => 'nullable|string|max:20',
                // Contacts - WhatsApp
                'whatsapp_1' => 'nullable|string|max:20',
                'whatsapp_2' => 'nullable|string|max:20',
                // Contacts - Autres
                'fax' => 'nullable|string|max:20',
                'email_principal' => 'nullable|email|max:255',
                'email_1' => 'nullable|email|max:255',
                'site_web' => 'nullable|url|max:255',
                'facebook' => 'nullable|string|max:255',
                'linkedin' => 'nullable|string|max:255',
                'twitter' => 'nullable|string|max:255',
                // Description
                'description' => 'nullable|string',
                'vision' => 'nullable|string',
                'mission' => 'nullable|string',
                // Dirigeants
                'dirigeants' => 'nullable|array',
                'dirigeants.*.nom' => 'nullable|string|max:255',
                'dirigeants.*.prenom' => 'nullable|string|max:255',
                'dirigeants.*.nom_restituer' => 'nullable|string|max:255',
                'dirigeants.*.fonction' => 'nullable|string|max:255',
                'dirigeants.*.ordre' => 'nullable|integer',
            ]);

            \Log::info('✅ Validation passed!');
            \Log::info('📦 Validated data:', $validated);

            $validated['statut'] = $validated['statut'] ?? 'actif';
            // Ensure statut is one of the ENUM values: actif, non_actif, suspendu
            if (!in_array($validated['statut'], ['actif', 'non_actif', 'suspendu'])) {
                $validated['statut'] = 'actif';
            }
            $validated['creation_username'] = auth()->user()->nom;
            $validated['creation_hostname'] = gethostname();

            \Log::info('📝 Data before create:', $validated);

            // Separate dirigeants from ecole data
            $dirigeants = $validated['dirigeants'] ?? [];
            unset($validated['dirigeants']);

            $ecole = Ecole::create($validated);

            \Log::info('🎉 École created successfully!', ['id' => $ecole->id, 'nom' => $ecole->nom]);

            // Save dirigeants (only if at least one field has a value)
            if (!empty($dirigeants) && is_array($dirigeants)) {
                \Log::info('💼 Saving dirigeants...', ['count' => count($dirigeants)]);
                $savedCount = 0;
                foreach ($dirigeants as $index => $dirigeant) {
                    // Only save if at least one field has data
                    if (!empty($dirigeant['nom']) || !empty($dirigeant['prenom']) || !empty($dirigeant['fonction'])) {
                        $ecole->dirigeants()->create([
                            'nom' => trim($dirigeant['nom'] ?? ''),
                            'prenom' => trim($dirigeant['prenom'] ?? ''),
                            'nom_restituer' => trim($dirigeant['nom_restituer'] ?? ''),
                            'fonction' => trim($dirigeant['fonction'] ?? ''),
                            'ordre' => intval($dirigeant['ordre'] ?? $index),
                        ]);
                        $savedCount++;
                    }
                }
                \Log::info("✅ Dirigeants saved! Count: $savedCount");
            } else {
                \Log::info('ℹ️ No dirigeants to save');
            }

            return redirect()
                ->route('parametrage.ecoles.index')
                ->with('success', 'École créée avec succès');
        } catch (\Exception $e) {
            \Log::error('❌ EcoleController@store - EXCEPTION:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erreur lors de la création de l\'école: ' . $e->getMessage());
        }
    }

    /**
     * Afficher les détails d'une école
     */
    public function show(Ecole $ecole)
    {
        try {
            $ecole->load(['campus', 'directeur', 'niveaux', 'classes', 'dirigeants']);

            $campuses = Campus::actif()
                ->orderBy('nom')
                ->get(['id', 'nom'])
                ->toArray();

            $directeurs = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['administrateur', 'directeur', 'super_admin']);
            })->orderBy('nom')
                ->get(['id', 'nom', 'email'])
                ->toArray();

            $paysList = Pays::actif()->get(['id', 'libelle', 'code'])->toArray();

            $typeEnseignement = [
                ['id' => 'primaire', 'libelle' => 'Primaire'],
                ['id' => 'secondaire', 'libelle' => 'Secondaire'],
                ['id' => 'superieur', 'libelle' => 'Supérieur'],
                ['id' => 'formation', 'libelle' => 'Formation professionnelle'],
                ['id' => 'autre', 'libelle' => 'Autre'],
            ];

            return Inertia::render('Parametrage::Ecoles/Show', [
                'ecole' => $ecole,
                'campuses' => $campuses,
                'directeurs' => $directeurs,
                'paysList' => $paysList,
                'typeEtablissements' => $typeEnseignement,
            ]);
        } catch (\Exception $e) {
            \Log::error('EcoleController@show - Error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Ecole $ecole)
    {
        try {
            $ecole->load('campus', 'directeur', 'dirigeants');

            $campuses = Campus::actif()
                ->with('institution')
                ->orderBy('nom')
                ->get()
                ->map(function ($campus) {
                    return [
                        'id' => $campus->id,
                        'nom' => $campus->nom . ' (' . $campus->institution->nom . ')',
                        'institution_id' => $campus->institution_id,
                    ];
                })
                ->toArray();

            $directeurs = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['administrateur', 'directeur', 'super_admin']);
            })->orderBy('nom')
                ->get(['id', 'nom', 'email'])
                ->toArray();

            $paysList = Pays::actif()->get(['id', 'libelle', 'code'])->toArray();

            $typeEnseignement = [
                ['id' => 'primaire', 'libelle' => 'Primaire'],
                ['id' => 'secondaire', 'libelle' => 'Secondaire'],
                ['id' => 'superieur', 'libelle' => 'Supérieur'],
                ['id' => 'formation', 'libelle' => 'Formation professionnelle'],
                ['id' => 'autre', 'libelle' => 'Autre'],
            ];

            return Inertia::render('Parametrage::Ecoles/Edit', [
                'ecole' => $ecole,
                'campuses' => $campuses,
                'directeurs' => $directeurs,
                'paysList' => $paysList,
                'typeEtablissements' => $typeEnseignement,
            ]);
        } catch (\Exception $e) {
            Log::error('ecolecontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    /**
     * Mettre à jour une école
     */
    public function update(Request $request, Ecole $ecole)
    {
        try {
            $validated = $request->validate([
                'campus_id' => 'required|exists:campuses,id',
                'code' => 'required|string|max:100|unique:ecoles,code,' . $ecole->id,
                'nom' => 'required|string|max:255',
                'type_enseignement' => 'nullable|in:primaire,secondaire,superieur,formation,autre',
                'directeur_id' => 'nullable|exists:users,id',
                'capacite_totale' => 'nullable|integer|min:1',
                'statut' => 'nullable|in:actif,non_actif,suspendu',
                // Adresse et localisation
                'adresse_siege' => 'nullable|string',
                'code_postal' => 'nullable|string|max:20',
                'boite_postale' => 'nullable|string|max:100',
                'ville' => 'nullable|string|max:100',
                'quartier' => 'nullable|string|max:100',
                'commune' => 'nullable|string|max:100',
                'departement' => 'nullable|string|max:100',
                'region' => 'nullable|string|max:100',
                'pays_id' => 'nullable|exists:pays,id',
                // Contacts - Téléphones
                'telephone_principal' => 'nullable|string|max:20',
                'telephone_2' => 'nullable|string|max:20',
                'telephone_3' => 'nullable|string|max:20',
                // Contacts - WhatsApp
                'whatsapp_1' => 'nullable|string|max:20',
                'whatsapp_2' => 'nullable|string|max:20',
                // Contacts - Autres
                'fax' => 'nullable|string|max:20',
                'email_principal' => 'nullable|email|max:255',
                'email_1' => 'nullable|email|max:255',
                'site_web' => 'nullable|url|max:255',
                'facebook' => 'nullable|string|max:255',
                'linkedin' => 'nullable|string|max:255',
                'twitter' => 'nullable|string|max:255',
                // Description
                'description' => 'nullable|string',
                'vision' => 'nullable|string',
                'mission' => 'nullable|string',
                // Dirigeants
                'dirigeants' => 'nullable|array',
                'dirigeants.*.nom' => 'nullable|string|max:255',
                'dirigeants.*.prenom' => 'nullable|string|max:255',
                'dirigeants.*.nom_restituer' => 'nullable|string|max:255',
                'dirigeants.*.fonction' => 'nullable|string|max:255',
                'dirigeants.*.ordre' => 'nullable|integer',
            ]);

            $validated['modification_username'] = auth()->user()->nom;
            $validated['modification_hostname'] = gethostname();

            // Separate dirigeants from ecole data
            $dirigeants = $validated['dirigeants'] ?? [];
            unset($validated['dirigeants']);

            $ecole->update($validated);

            // Update dirigeants - delete old and create new
            $ecole->dirigeants()->delete();

            if (!empty($dirigeants) && is_array($dirigeants)) {
                \Log::info('💼 Updating dirigeants...', ['count' => count($dirigeants)]);
                $savedCount = 0;
                foreach ($dirigeants as $index => $dirigeant) {
                    // Only save if at least one field has data
                    if (!empty($dirigeant['nom']) || !empty($dirigeant['prenom']) || !empty($dirigeant['fonction'])) {
                        $ecole->dirigeants()->create([
                            'nom' => trim($dirigeant['nom'] ?? ''),
                            'prenom' => trim($dirigeant['prenom'] ?? ''),
                            'nom_restituer' => trim($dirigeant['nom_restituer'] ?? ''),
                            'fonction' => trim($dirigeant['fonction'] ?? ''),
                            'ordre' => intval($dirigeant['ordre'] ?? $index),
                        ]);
                        $savedCount++;
                    }
                }
                \Log::info("✅ Dirigeants updated! Count: $savedCount");
            } else {
                \Log::info('ℹ️ No dirigeants to update');
            }

            return redirect()
                ->route('parametrage.ecoles.index')
                ->with('success', 'École modifiée avec succès');
        } catch (\Exception $e) {
            Log::error('ecolecontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    /**
     * Supprimer (soft delete) une école
     */
    public function destroy(Ecole $ecole)
    {
        try {
            $ecole->deletion_username = auth()->user()->nom;
            $ecole->deletion_hostname = gethostname();
            $ecole->save();
            $ecole->delete();

            return redirect()->route('parametrage.ecoles.index')->with('success', 'École supprimée avec succès');
        } catch (\Exception $e) {
            Log::error('ecolecontroller@error: ' . $e->getMessage());
            return redirect()->route('parametrage.ecoles.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    /**
     * Activer/Désactiver une école
     */
    public function activate(Ecole $ecole)
    {
        try {
            $newStatut = $ecole->statut === 'actif' ? 'non_actif' : 'actif';
            $ecole->statut = $newStatut;
            $ecole->modification_username = auth()->user()->nom;
            $ecole->modification_hostname = gethostname();
            $ecole->save();

            $message = $newStatut === 'actif' ? 'École activée' : 'École désactivée';
            return redirect()->route('parametrage.ecoles.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('EcoleController@activate: ' . $e->getMessage());
            return redirect()->route('parametrage.ecoles.index')->with('error', 'Erreur lors du changement de statut');
        }
    }

    /**
     * Toggle statut between actif and inactif
     */
    public function statut(Ecole $ecole)
    {
        try {
            \Log::info('🔵 [ECOLE] statut() START - ID: ' . $ecole->id);
            \Log::info('   Current statut: ' . $ecole->statut);
            \Log::info('   Fillable: ' . implode(', ', $ecole->getFillable()));

            $newStatut = $ecole->statut === 'actif' ? 'non_actif' : 'actif';
            \Log::info('   Toggling to: ' . $newStatut);

            $ecole->statut = $newStatut;
            \Log::info('   After assignment - statut: ' . $ecole->statut);
            \Log::info('   Auth ID: ' . auth()->id());

            $ecole->modification_username = auth()->user()->nom;
            $ecole->modification_hostname = gethostname();
            \Log::info('   About to save...');

            $saved = $ecole->save();
            \Log::info('   Save result: ' . ($saved ? 'SUCCESS' : 'FAILED'));

            $message = $newStatut === 'actif' ? 'Activée' : 'Désactivée';
            \Log::info('   ✅ École ' . $message . ' avec succès');
            return redirect()->route('parametrage.ecoles.index')->with('success', 'École ' . $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('❌ EXCEPTION in statut(): ' . $e->getMessage());
            \Log::error('   Code: ' . $e->getCode());
            \Log::error('   File: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('   Trace: ' . $e->getTraceAsString());
            return redirect()->route('parametrage.ecoles.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
