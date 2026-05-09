<?php

namespace Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Services\Entities\InscriptionTransport;

class InscriptionTransportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:inscriptions-transport-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:inscriptions-transport-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:inscriptions-transport-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:inscriptions-transport-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = InscriptionTransport::query();

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

            $inscriptions = $query->with(['apprenant', 'serviceTransport'])->paginate(10)->withQueryString();

            return Inertia::render('Services::InscriptionsTransports/Index', [
                'inscriptionsTransports' => $inscriptions,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "InscriptionTransportController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            $apprenants = \Modules\Parametrage\Entities\Apprenant::with('user')->get()->map(function($apprenant) {
                return [
                    'id' => $apprenant->id,
                    'nom' => $apprenant->user?->nom,
                    'prenoms' => $apprenant->user?->prenoms,
                ];
            })->toArray();

            $servicesTransports = ServicesTransport::all()->map(function($service) {
                return [
                    'id' => $service->id,
                    'zone' => $service->zone,
                    'ligne' => $service->ligne,
                ];
            })->toArray();

            $anneeScolaires = \Modules\Parametrage\Entities\AnneeScolaire::where('etat', 'actif')->get()->map(function($annee) {
                return [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ];
            })->toArray();

            return Inertia::render('Services::InscriptionsTransports/Create', [
                'apprenants' => $apprenants,
                'servicesTransports' => $servicesTransports,
                'anneeScolaires' => $anneeScolaires,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "InscriptionTransportController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'service_transport_id' => 'required|exists:services_transport,id',
                'date_inscription' => 'required|date',
                'date_debut' => 'required|date',
                'date_fin' => 'nullable|date|after:date_debut',
                'point_prise_charge' => 'required|string|max:255',
                'observations' => 'nullable|string',
                'statut' => 'required|in:active,suspendue,terminee,annulee',
            ]);

            InscriptionTransport::create($validated);

            return redirect()->route('inscriptions-transport.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "InscriptionTransportController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(InscriptionTransport $inscriptionTransport)
    {
        try {
            $inscriptionTransport->load('apprenant', 'serviceTransport');

            return Inertia::render('Services::InscriptionsTransports/Show', [
                'item' => $inscriptionTransport,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "InscriptionTransportController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(InscriptionTransport $inscriptionTransport)
    {
        try {
            $apprenants = \Modules\Parametrage\Entities\Apprenant::with('user')->get()->map(function($apprenant) {
                return [
                    'id' => $apprenant->id,
                    'nom' => $apprenant->user?->nom,
                    'prenoms' => $apprenant->user?->prenoms,
                ];
            })->toArray();

            $servicesTransports = ServicesTransport::all()->map(function($service) {
                return [
                    'id' => $service->id,
                    'zone' => $service->zone,
                    'ligne' => $service->ligne,
                ];
            })->toArray();

            $anneeScolaires = \Modules\Parametrage\Entities\AnneeScolaire::where('etat', 'actif')->get()->map(function($annee) {
                return [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ];
            })->toArray();

            return Inertia::render('Services::InscriptionsTransports/Edit', [
                'item' => $inscriptionTransport->load('apprenant', 'serviceTransport'),
                'apprenants' => $apprenants,
                'servicesTransports' => $servicesTransports,
                'anneeScolaires' => $anneeScolaires,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "InscriptionTransportController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, InscriptionTransport $inscriptionTransport)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'service_transport_id' => 'required|exists:services_transport,id',
                'date_inscription' => 'required|date',
                'date_debut' => 'required|date',
                'date_fin' => 'nullable|date|after:date_debut',
                'point_prise_charge' => 'required|string|max:255',
                'observations' => 'nullable|string',
                'statut' => 'required|in:active,suspendue,terminee,annulee',
            ]);

            $inscriptionTransport->update($validated);

            return redirect()->route('inscriptions-transport.show', $inscriptionTransport)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "InscriptionTransportController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(InscriptionTransport $inscriptionTransport)
    {
        try {
            $inscriptionTransport->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "InscriptionTransportController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(InscriptionTransport $inscriptionTransport)
    {
        try {
            if ($inscriptionTransport->trashed()) {
                $inscriptionTransport->restore();
            } else {
                $inscriptionTransport->delete();
            }

            return redirect()->route('inscriptions-transport.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Services", "InscriptionTransportController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
