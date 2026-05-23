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
use Modules\Parametrage\Http\Controllers\Concerns\ProvidesParametrageLookups;
use Modules\Parametrage\Http\Requests\StoreInstitutionRequest;
use Modules\Parametrage\Http\Requests\UpdateInstitutionRequest;
use App\Models\User;
use Illuminate\Foundation\Validation\ValidatesRequests;

class InstitutionController extends Controller
{
    use ValidatesRequests, ProvidesParametrageLookups;

    /**
     * Génère un code institution unique à partir du nom.
     * Format : INST-{SLUG}-{N} où N s'incrémente si collision.
     */
    private function generateInstitutionCode(string $nom): string
    {
        $slug = strtoupper(\Illuminate\Support\Str::slug($nom, ''));
        $slug = substr($slug, 0, 20) ?: 'INSTITUTION';
        $base = 'INST-' . $slug;
        $candidate = $base;
        $i = 1;
        while (Institution::where('code', $candidate)->exists()) {
            $i++;
            $candidate = $base . '-' . $i;
        }
        return $candidate;
    }

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
            return Inertia::render('Parametrage::Institutions/Create', $this->institutionLookups());
        } catch (\Exception $e) {
            \Log::error('InstitutionController@create - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    /**
     * Créer une nouvelle institution
     */
    public function store(StoreInstitutionRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['statut'] = $validated['statut'] ?? 'actif';
            $validated['langue_principale'] = $validated['langue_principale'] ?? 'fr';
            $validated['creation_username'] = auth()->user()->nom;
            $validated['creation_hostname'] = gethostname();

            // Auto-génération du code si vide (slug du nom + suffixe unique)
            if (empty($validated['code'])) {
                $validated['code'] = $this->generateInstitutionCode($validated['nom']);
            }

            // Normalisation site web : préfixe https:// si l'utilisateur a tapé "www.xxx.com"
            if (!empty($validated['site_web']) && !preg_match('#^https?://#i', $validated['site_web'])) {
                $validated['site_web'] = 'https://' . ltrim($validated['site_web'], '/');
            }

            Institution::create($validated);

            return redirect()
                ->route('parametrage.institution.index')
                ->with('success', 'Institution créée avec succès');
        } catch (\Exception $e) {
            \Log::error('InstitutionController@store - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors de la création de l\'institution: ' . $e->getMessage())->withInput();
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
            $institution->load('pays', 'directeurGeneral');
            $institutionData = $institution->toArray();
            if ($institution->date_creation) {
                $institutionData['date_creation'] = $institution->date_creation->format('Y-m-d');
            }

            return Inertia::render('Parametrage::Institutions/Edit', array_merge(
                $this->institutionLookups(),
                ['institution' => $institutionData]
            ));
        } catch (\Exception $e) {
            \Log::error('InstitutionController@edit - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour une institution
     */
    public function update(UpdateInstitutionRequest $request, Institution $institution)
    {
        try {
            $validated = $request->validated();
            $validated['modification_username'] = auth()->user()->nom;
            $validated['modification_hostname'] = gethostname();

            // Garde le code existant si l'utilisateur n'en envoie pas un nouveau
            if (empty($validated['code'])) {
                $validated['code'] = $institution->code ?: $this->generateInstitutionCode($validated['nom']);
            }

            // Normalisation site web
            if (!empty($validated['site_web']) && !preg_match('#^https?://#i', $validated['site_web'])) {
                $validated['site_web'] = 'https://' . ltrim($validated['site_web'], '/');
            }

            $institution->update($validated);

            return redirect()
                ->route('parametrage.institution.index')
                ->with('success', 'Institution modifiée avec succès');
        } catch (\Exception $e) {
            \Log::error('InstitutionController@update - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors de la modification: ' . $e->getMessage())->withInput();
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
