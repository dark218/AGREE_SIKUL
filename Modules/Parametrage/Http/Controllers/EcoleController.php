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
use Modules\Parametrage\Http\Controllers\Concerns\ProvidesParametrageLookups;
use Modules\Parametrage\Http\Requests\StoreEcoleRequest;
use Modules\Parametrage\Http\Requests\UpdateEcoleRequest;
use App\Models\User;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;

class EcoleController extends Controller
{
    use ValidatesRequests, ProvidesParametrageLookups;

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
            return Inertia::render('Parametrage::Ecoles/Create', $this->ecoleLookups());
        } catch (\Exception $e) {
            Log::error('EcoleController@create - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    /**
     * Créer une nouvelle école
     */
    public function store(StoreEcoleRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['statut'] = $validated['statut'] ?? 'actif';
            $validated['creation_username'] = auth()->user()->nom;
            $validated['creation_hostname'] = gethostname();

            $dirigeants = $validated['dirigeants'] ?? [];
            unset($validated['dirigeants']);

            $ecole = Ecole::create($validated);

            $this->syncDirigeants($ecole, $dirigeants);

            return redirect()
                ->route('parametrage.ecoles.index')
                ->with('success', 'École créée avec succès');
        } catch (\Exception $e) {
            Log::error('EcoleController@store - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors de la création de l\'école: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Persiste les dirigeants en best-effort (skip lignes vides).
     */
    private function syncDirigeants(Ecole $ecole, array $dirigeants, bool $replace = false): void
    {
        if ($replace) {
            $ecole->dirigeants()->delete();
        }
        foreach ($dirigeants as $index => $d) {
            $nom = trim($d['nom'] ?? '');
            $prenom = trim($d['prenom'] ?? '');
            $fonction = trim($d['fonction'] ?? '');
            if (!$nom && !$prenom && !$fonction) {
                continue;
            }
            $ecole->dirigeants()->create([
                'nom' => $nom,
                'prenom' => $prenom,
                'nom_restituer' => trim($d['nom_restituer'] ?? ''),
                'fonction' => $fonction,
                'ordre' => intval($d['ordre'] ?? $index),
            ]);
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
            return Inertia::render('Parametrage::Ecoles/Edit', array_merge(
                $this->ecoleLookups(),
                ['ecole' => $ecole]
            ));
        } catch (\Exception $e) {
            Log::error('EcoleController@edit - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    /**
     * Mettre à jour une école
     */
    public function update(UpdateEcoleRequest $request, Ecole $ecole)
    {
        try {
            $validated = $request->validated();
            $validated['modification_username'] = auth()->user()->nom;
            $validated['modification_hostname'] = gethostname();

            $dirigeants = $validated['dirigeants'] ?? [];
            unset($validated['dirigeants']);

            $ecole->update($validated);

            $this->syncDirigeants($ecole, $dirigeants, replace: true);

            return redirect()
                ->route('parametrage.ecoles.index')
                ->with('success', 'École modifiée avec succès');
        } catch (\Exception $e) {
            Log::error('EcoleController@update - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('error', 'Erreur lors de la modification: ' . $e->getMessage())->withInput();
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
