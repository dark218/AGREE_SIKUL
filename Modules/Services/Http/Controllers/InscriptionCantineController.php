<?php

namespace Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Services\Entities\InscriptionCantine;
use Modules\Services\Entities\ServiceCantine;

class InscriptionCantineController extends Controller
{
    public function __construct()
    {
        \Log::info('InscriptionCantineController::__construct() initializing middleware');
        $this->middleware('permission.check:inscriptions-cantine-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:inscriptions-cantine-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:inscriptions-cantine-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:inscriptions-cantine-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = InscriptionCantine::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->whereHas('user', function ($user) use ($search) {
                        $user->where('nom', 'like', "%$search%")
                            ->orWhere('prenoms', 'like', "%$search%");
                    });
                });
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $inscriptions = $query->with(['apprenant', 'serviceCantine'])->paginate(10)->withQueryString();

            // Map relationships to plain arrays for Inertia serialization
            $inscriptions = $inscriptions->through(function ($inscription) {
                // Load user relationship for apprenant if needed
                if ($inscription->apprenant && !$inscription->apprenant->relationLoaded('user')) {
                    $inscription->apprenant->load('user');
                }

                return [
                    'id' => $inscription->id,
                    'apprenant_id' => $inscription->apprenant_id,
                    'apprenant' => $inscription->apprenant ? [
                        'id' => $inscription->apprenant->id,
                        'nom' => $inscription->apprenant->user?->nom,
                        'prenoms' => $inscription->apprenant->user?->prenoms,
                    ] : null,
                    'service_cantine_id' => $inscription->service_cantine_id,
                    'serviceCantine' => $inscription->serviceCantine ? [
                        'id' => $inscription->serviceCantine->id,
                        'nom' => $inscription->serviceCantine->nom,
                    ] : null,
                    'date_inscription' => $inscription->date_inscription,
                    'date_debut' => $inscription->date_debut,
                    'date_fin' => $inscription->date_fin,
                    'nombre_jours' => $inscription->nombre_jours,
                    'observations' => $inscription->observations,
                    'statut' => $inscription->statut,
                ];
            });

            return Inertia::render('Services::InscriptionsCantines/Index', [
                'inscriptionsCantines' => $inscriptions,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "InscriptionCantineController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            \Log::info('InscriptionCantineController::create() called');

            // Load apprenants and services
            $apprenants = \Modules\Parametrage\Entities\Apprenant::with('user')
                ->where('statut', 'actif')
                ->get()
                ->map(function ($apprenant) {
                    return [
                        'id' => $apprenant->id,
                        'nom' => $apprenant->user?->nom ?? '-',
                        'prenoms' => $apprenant->user?->prenoms ?? '',
                    ];
                })
                ->toArray();

            $servicesCantines = ServiceCantine::where('statut', 'actif')
                ->get(['id', 'nom'])
                ->toArray();

            $anneeScolaires = \Modules\Parametrage\Entities\AnneeScolaire::where('etat', 'actif')
                ->get(['id', 'libelle'])
                ->toArray();

            return Inertia::render('Services::InscriptionsCantines/Create', [
                'apprenants' => $apprenants,
                'servicesCantines' => $servicesCantines,
                'anneeScolaires' => $anneeScolaires,
            ]);
        } catch (\Throwable $th) {
            \Log::error('InscriptionCantineController::create() ERROR', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ]);
            log_error("Services", "InscriptionCantineController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('InscriptionCantineController::store() called');
            \Log::info('Request data:', $request->all());

            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'service_cantine_id' => 'required|exists:services_cantines,id',
                'date_inscription' => 'required|date',
                'date_debut' => 'required|date',
                'date_fin' => 'nullable|date|after:date_debut',
                'nombre_jours' => 'nullable|integer|min:0',
                'observations' => 'nullable|string',
                'statut' => 'required|in:active,suspendue,terminee,annulee',
            ]);

            \Log::info('Validation passed. Data:', $validated);

            $created = InscriptionCantine::create($validated);
            \Log::info('InscriptionCantine created successfully', ['id' => $created->id]);

            return redirect()->route('inscriptions-cantine.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            \Log::error('InscriptionCantineController::store() ERROR', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ]);
            log_error("Services", "InscriptionCantineController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(InscriptionCantine $inscriptionCantine)
    {
        try {
            // MANUAL FIX: If model is empty, load it from route parameter
            if (!$inscriptionCantine->exists) {
                $id = request()->route('inscriptionCantine');
                $inscriptionCantine = InscriptionCantine::find($id);
            }

            $inscriptionCantine->load('apprenant.user', 'serviceCantine', 'passages');

            // Load reference data for the form
            $apprenants = \Modules\Parametrage\Entities\Apprenant::with('user')
                ->where('statut', 'actif')
                ->get()
                ->map(function ($apprenant) {
                    return [
                        'id' => $apprenant->id,
                        'nom' => $apprenant->user?->nom ?? '-',
                        'prenoms' => $apprenant->user?->prenoms ?? '',
                    ];
                })
                ->toArray();

            $servicesCantines = ServiceCantine::where('statut', 'actif')
                ->get(['id', 'nom'])
                ->toArray();

            $anneeScolaires = \Modules\Parametrage\Entities\AnneeScolaire::where('etat', 'actif')
                ->get(['id', 'libelle'])
                ->toArray();

            // Map to plain array for proper Inertia serialization
            $mapped = [
                'id' => $inscriptionCantine->id,
                'annee_scolaire_id' => $inscriptionCantine->annee_scolaire_id,
                'apprenant_id' => $inscriptionCantine->apprenant_id,
                'apprenant' => $inscriptionCantine->apprenant ? [
                    'id' => $inscriptionCantine->apprenant->id,
                    'nom' => $inscriptionCantine->apprenant->user?->nom,
                    'prenoms' => $inscriptionCantine->apprenant->user?->prenoms,
                ] : null,
                'service_cantine_id' => $inscriptionCantine->service_cantine_id,
                'serviceCantine' => $inscriptionCantine->serviceCantine ? [
                    'id' => $inscriptionCantine->serviceCantine->id,
                    'nom' => $inscriptionCantine->serviceCantine->nom,
                ] : null,
                'date_inscription' => $inscriptionCantine->date_inscription,
                'date_debut' => $inscriptionCantine->date_debut,
                'date_fin' => $inscriptionCantine->date_fin,
                'nombre_jours' => $inscriptionCantine->nombre_jours,
                'observations' => $inscriptionCantine->observations,
                'statut' => $inscriptionCantine->statut,
            ];

            return Inertia::render('Services::InscriptionsCantines/Show', [
                'inscription' => $mapped,
                'apprenants' => $apprenants,
                'servicesCantines' => $servicesCantines,
                'anneeScolaires' => $anneeScolaires,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "InscriptionCantineController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(InscriptionCantine $inscriptionCantine)
    {
        try {
            // MANUAL FIX: If model is empty, load it from route parameter
            if (!$inscriptionCantine->exists) {
                $id = request()->route('inscriptionCantine');
                $inscriptionCantine = InscriptionCantine::find($id);
            }

            // Load apprenants and services
            $apprenants = \Modules\Parametrage\Entities\Apprenant::with('user')
                ->where('statut', 'actif')
                ->get()
                ->map(function ($apprenant) {
                    return [
                        'id' => $apprenant->id,
                        'nom' => $apprenant->user?->nom ?? '-',
                        'prenoms' => $apprenant->user?->prenoms ?? '',
                    ];
                })
                ->toArray();

            $servicesCantines = ServiceCantine::where('statut', 'actif')
                ->get(['id', 'nom'])
                ->toArray();

            $anneeScolaires = \Modules\Parametrage\Entities\AnneeScolaire::where('etat', 'actif')
                ->get(['id', 'libelle'])
                ->toArray();

            return Inertia::render('Services::InscriptionsCantines/Edit', [
                'item' => $inscriptionCantine->load('apprenant', 'serviceCantine'),
                'apprenants' => $apprenants,
                'servicesCantines' => $servicesCantines,
                'anneeScolaires' => $anneeScolaires,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "InscriptionCantineController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, InscriptionCantine $inscriptionCantine)
    {
        try {
            // MANUAL FIX: If model is empty, load it from route parameter
            if (!$inscriptionCantine->exists) {
                $id = request()->route('inscriptionCantine');
                $inscriptionCantine = InscriptionCantine::find($id);
            }

            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'service_cantine_id' => 'required|exists:services_cantines,id',
                'date_inscription' => 'required|date',
                'date_debut' => 'required|date',
                'date_fin' => 'nullable|date|after:date_debut',
                'nombre_jours' => 'nullable|integer|min:0',
                'observations' => 'nullable|string',
                'statut' => 'required|in:active,suspendue,terminee,annulee',
            ]);

            $inscriptionCantine->update($validated);

            return redirect()->route('inscriptions-cantine.show', $inscriptionCantine)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "InscriptionCantineController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(InscriptionCantine $inscriptionCantine)
    {
        try {
            // MANUAL FIX: If model is empty, load it from route parameter
            if (!$inscriptionCantine->exists) {
                $id = request()->route('inscriptionCantine');
                $inscriptionCantine = InscriptionCantine::find($id);
            }

            $inscriptionCantine->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "InscriptionCantineController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(InscriptionCantine $inscriptionCantine)
    {
        try {
            // MANUAL FIX: If model is empty, load it from route parameter
            if (!$inscriptionCantine->exists) {
                $id = request()->route('inscriptionCantine');
                $inscriptionCantine = InscriptionCantine::find($id);
            }

            if ($inscriptionCantine->trashed()) {
                $inscriptionCantine->restore();
            } else {
                $inscriptionCantine->delete();
            }

            return redirect()->route('inscriptions-cantine.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Services", "InscriptionCantineController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
