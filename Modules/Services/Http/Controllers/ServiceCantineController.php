<?php

namespace Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Services\Entities\ServiceCantine;

class ServiceCantineController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:services_cantines-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:services_cantines-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:services_cantines-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:services_cantines-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = ServiceCantine::query();

            if ($request->filled('annee_scolaire_id')) {
                $query->where('annee_scolaire_id', $request->input('annee_scolaire_id'));
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $services = $query->with(['anneeScolaire', 'niveau', 'cycleEnseignement', 'ecole', 'campus'])
                ->paginate(10);

            // Transform paginated data to include relationships
            $transformedData = $services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'nom' => $service->nom,
                    'code' => $service->code,
                    'prix_cents' => $service->prix_cents,
                    'description' => $service->description,
                    'capacite' => $service->capacite,
                    'responsable_id' => $service->responsable_id,
                    'annee_scolaire_id' => $service->annee_scolaire_id,
                    'niveau_id' => $service->niveau_id,
                    'cycle_enseignement_id' => $service->cycle_enseignement_id,
                    'ecole_id' => $service->ecole_id,
                    'campus_id' => $service->campus_id,
                    'tarif_mensuel' => $service->tarif_mensuel,
                    'tarif_trimestriel' => $service->tarif_trimestriel,
                    'tarif_semestriel' => $service->tarif_semestriel,
                    'tarif_annuel' => $service->tarif_annuel,
                    'date_debut' => $service->date_debut,
                    'date_fin' => $service->date_fin,
                    'statut' => $service->statut,
                    'anneeScolaire' => $service->anneeScolaire,
                    'niveau' => $service->niveau,
                    'cycleEnseignement' => $service->cycleEnseignement,
                    'ecole' => $service->ecole,
                    'campus' => $service->campus,
                ];
            });

            // Rebuild the paginator with transformed data
            $services = $services->setCollection(collect($transformedData))->withQueryString();

            $anneeScolaires = \Modules\Parametrage\Entities\AnneeScolaire::select('id', 'libelle')->orderBy('libelle')->get();

            return Inertia::render('Services::ServicesCantines/Index', [
                'servicesCantines' => $services,
                'anneeScolaires' => $anneeScolaires,
                'filters' => $request->only(['annee_scolaire_id', 'etat']),
            ]);
        } catch (\Throwable $th) {
            \Log::error('=== ERROR in ServiceCantineController::index ===');
            \Log::error('Exception: ' . get_class($th));
            \Log::error('Message: ' . $th->getMessage());
            \Log::error('File: ' . $th->getFile() . ':' . $th->getLine());
            \Log::error('Trace: ' . $th->getTraceAsString());
            log_error("Services", "ServiceCantineController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            \Log::info('=== ServiceCantineController::create called ===');
            \Log::info('Auth::check(): ' . (auth()->check() ? 'TRUE' : 'FALSE'));
            \Log::info('Auth::id(): ' . (auth()->id() ?? 'NULL'));
            \Log::info('Session data keys: ' . json_encode(array_keys(session()->all())));
            \Log::info('Login_id in session: ' . (session('login_id') ?? 'NULL'));

            // Essayez de charger l'utilisateur manuellement si auth est vide
            if (!auth()->check() && session('login_id')) {
                \Log::info('⚠️ Auth check failed, but session has login_id. Attempting manual user load...');
                $user = \App\Models\User::find(session('login_id'));
                if ($user) {
                    auth()->login($user);
                    \Log::info('✓ User manually loaded: ' . $user->email);
                } else {
                    \Log::warning('✗ User not found for session login_id: ' . session('login_id'));
                }
            }

            $anneeScolaires = \Modules\Parametrage\Entities\AnneeScolaire::select('id', 'libelle')->orderBy('libelle')->get();
            $niveaux = \Modules\Parametrage\Entities\Niveau::select('id', 'libelle')->orderBy('libelle')->get();
            $cycles = \Modules\Parametrage\Entities\CycleEnseignement::select('id', 'libelle')->orderBy('libelle')->get();
            $ecoles = \Modules\Parametrage\Entities\Ecole::select('id', 'nom as libelle')->orderBy('nom')->get();
            $campuses = \Modules\Parametrage\Entities\Campus::select('id', 'nom as libelle')->orderBy('nom')->get();

            \Log::info('📊 DATA LOADED - AnneeScolaires: ' . $anneeScolaires->count() . ', Niveaux: ' . $niveaux->count() . ', Cycles: ' . $cycles->count() . ', Ecoles: ' . $ecoles->count() . ', Campuses: ' . $campuses->count());
            \Log::info('🎯 About to render: Services::ServicesCantines/Create');

            return Inertia::render('Services::ServicesCantines/Create', [
                'anneeScolaires' => $anneeScolaires,
                'niveaux' => $niveaux,
                'cycles' => $cycles,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ EXCEPTION in ServiceCantineController::create');
            \Log::error('Exception Class: ' . get_class($th));
            \Log::error('Message: ' . $th->getMessage());
            \Log::error('File: ' . $th->getFile() . ':' . $th->getLine());
            \Log::error('Trace: ' . $th->getTraceAsString());
            log_error("Services", "ServiceCantineController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('=== ServiceCantineController::store DÉBUT ===');
            \Log::info('Request data:', $request->all());

            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:services_cantines',
                'nom' => 'required|string|max:255',
                'prix_cents' => 'required|integer|min:0',
                'description' => 'nullable|string',
                'capacite' => 'nullable|integer|min:0',
                'responsable_id' => 'nullable|exists:users,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'niveau_id' => 'nullable|exists:niveaux,id',
                'cycle_enseignement_id' => 'nullable|exists:cycles_enseignement,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'tarif_mensuel' => 'nullable|integer|min:0',
                'tarif_trimestriel' => 'nullable|integer|min:0',
                'tarif_semestriel' => 'nullable|integer|min:0',
                'tarif_annuel' => 'nullable|integer|min:0',
                'date_debut' => 'nullable|date',
                'date_fin' => 'nullable|date',
                'statut' => 'required|in:actif,inactif',
            ]);

            \Log::info('✓ Validation passed. Validated data:', $validated);

            $created = ServiceCantine::create($validated);

            \Log::info('✓ ServiceCantine created successfully. ID: ' . $created->id);
            \Log::info('=== ServiceCantineController::store SUCCESS ===');

            return redirect()->route('services-cantine.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            \Log::error('❌ VALIDATION ERROR in store');
            \Log::error('Errors:', $ve->errors());
            return back()->withErrors($ve->errors())->withInput();
        } catch (\Throwable $th) {
            \Log::error('❌ EXCEPTION in ServiceCantineController::store');
            \Log::error('Exception Class: ' . get_class($th));
            \Log::error('Message: ' . $th->getMessage());
            \Log::error('File: ' . $th->getFile() . ':' . $th->getLine());
            \Log::error('Trace: ' . $th->getTraceAsString());
            log_error("Services", "ServiceCantineController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'))->withInput();
        }
    }

    public function show(ServiceCantine $serviceCantine)
    {
        try {
            $anneeScolaires = \Modules\Parametrage\Entities\AnneeScolaire::select('id', 'libelle')->orderBy('libelle')->get();
            $niveaux = \Modules\Parametrage\Entities\Niveau::select('id', 'libelle')->orderBy('libelle')->get();
            $cycles = \Modules\Parametrage\Entities\CycleEnseignement::select('id', 'libelle')->orderBy('libelle')->get();
            $ecoles = \Modules\Parametrage\Entities\Ecole::select('id', 'nom as libelle')->orderBy('nom')->get();
            $campuses = \Modules\Parametrage\Entities\Campus::select('id', 'nom as libelle')->orderBy('nom')->get();

            $serviceCantine->load('responsable', 'menus', 'inscriptions', 'anneeScolaire', 'niveau', 'cycleEnseignement', 'ecole', 'campus');

            // Transform data to include all fields and relationships
            $data = [
                'id' => $serviceCantine->id,
                'nom' => $serviceCantine->nom,
                'code' => $serviceCantine->code,
                'prix_cents' => $serviceCantine->prix_cents,
                'description' => $serviceCantine->description,
                'capacite' => $serviceCantine->capacite,
                'responsable_id' => $serviceCantine->responsable_id,
                'annee_scolaire_id' => $serviceCantine->annee_scolaire_id,
                'niveau_id' => $serviceCantine->niveau_id,
                'cycle_enseignement_id' => $serviceCantine->cycle_enseignement_id,
                'ecole_id' => $serviceCantine->ecole_id,
                'campus_id' => $serviceCantine->campus_id,
                'tarif_mensuel' => $serviceCantine->tarif_mensuel,
                'tarif_trimestriel' => $serviceCantine->tarif_trimestriel,
                'tarif_semestriel' => $serviceCantine->tarif_semestriel,
                'tarif_annuel' => $serviceCantine->tarif_annuel,
                'date_debut' => $serviceCantine->date_debut,
                'date_fin' => $serviceCantine->date_fin,
                'statut' => $serviceCantine->statut,
                'anneeScolaire' => $serviceCantine->anneeScolaire,
                'niveau' => $serviceCantine->niveau,
                'cycleEnseignement' => $serviceCantine->cycleEnseignement,
                'ecole' => $serviceCantine->ecole,
                'campus' => $serviceCantine->campus,
                'responsable' => $serviceCantine->responsable,
                'menus' => $serviceCantine->menus,
                'inscriptions' => $serviceCantine->inscriptions,
            ];

            return Inertia::render('Services::ServicesCantines/Show', [
                'item' => $data,
                'anneeScolaires' => $anneeScolaires,
                'niveaux' => $niveaux,
                'cycles' => $cycles,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "ServiceCantineController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(ServiceCantine $serviceCantine)
    {
        try {
            $anneeScolaires = \Modules\Parametrage\Entities\AnneeScolaire::select('id', 'libelle')->orderBy('libelle')->get();
            $niveaux = \Modules\Parametrage\Entities\Niveau::select('id', 'libelle')->orderBy('libelle')->get();
            $cycles = \Modules\Parametrage\Entities\CycleEnseignement::select('id', 'libelle')->orderBy('libelle')->get();
            $ecoles = \Modules\Parametrage\Entities\Ecole::select('id', 'nom as libelle')->orderBy('nom')->get();
            $campuses = \Modules\Parametrage\Entities\Campus::select('id', 'nom as libelle')->orderBy('nom')->get();

            $serviceCantine->load('responsable', 'anneeScolaire', 'niveau', 'cycleEnseignement', 'ecole', 'campus');

            // Transform data to include all fields and relationships
            $data = [
                'id' => $serviceCantine->id,
                'nom' => $serviceCantine->nom,
                'code' => $serviceCantine->code,
                'prix_cents' => $serviceCantine->prix_cents,
                'description' => $serviceCantine->description,
                'capacite' => $serviceCantine->capacite,
                'responsable_id' => $serviceCantine->responsable_id,
                'annee_scolaire_id' => $serviceCantine->annee_scolaire_id,
                'niveau_id' => $serviceCantine->niveau_id,
                'cycle_enseignement_id' => $serviceCantine->cycle_enseignement_id,
                'ecole_id' => $serviceCantine->ecole_id,
                'campus_id' => $serviceCantine->campus_id,
                'tarif_mensuel' => $serviceCantine->tarif_mensuel,
                'tarif_trimestriel' => $serviceCantine->tarif_trimestriel,
                'tarif_semestriel' => $serviceCantine->tarif_semestriel,
                'tarif_annuel' => $serviceCantine->tarif_annuel,
                'date_debut' => $serviceCantine->date_debut,
                'date_fin' => $serviceCantine->date_fin,
                'statut' => $serviceCantine->statut,
                'anneeScolaire' => $serviceCantine->anneeScolaire,
                'niveau' => $serviceCantine->niveau,
                'cycleEnseignement' => $serviceCantine->cycleEnseignement,
                'ecole' => $serviceCantine->ecole,
                'campus' => $serviceCantine->campus,
                'responsable' => $serviceCantine->responsable,
            ];

            return Inertia::render('Services::ServicesCantines/Edit', [
                'item' => $data,
                'anneeScolaires' => $anneeScolaires,
                'niveaux' => $niveaux,
                'cycles' => $cycles,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "ServiceCantineController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, ServiceCantine $serviceCantine)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:services_cantines,code,' . $serviceCantine->id,
                'nom' => 'required|string|max:255',
                'prix_cents' => 'required|integer|min:0',
                'description' => 'nullable|string',
                'capacite' => 'nullable|integer|min:0',
                'responsable_id' => 'nullable|exists:users,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'niveau_id' => 'nullable|exists:niveaux,id',
                'cycle_enseignement_id' => 'nullable|exists:cycles_enseignement,id',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'tarif_mensuel' => 'nullable|integer|min:0',
                'tarif_trimestriel' => 'nullable|integer|min:0',
                'tarif_semestriel' => 'nullable|integer|min:0',
                'tarif_annuel' => 'nullable|integer|min:0',
                'date_debut' => 'nullable|date',
                'date_fin' => 'nullable|date',
                'statut' => 'required|in:actif,inactif',
            ]);

            $serviceCantine->update($validated);

            return redirect()->route('services-cantine.show', $serviceCantine)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "ServiceCantineController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(ServiceCantine $serviceCantine)
    {
        try {
            $serviceCantine->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "ServiceCantineController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(ServiceCantine $serviceCantine)
    {
        try {
            if ($serviceCantine->trashed()) {
                $serviceCantine->restore();
            } else {
                $serviceCantine->delete();
            }

            return redirect()->route('services-cantine.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Services", "ServiceCantineController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
