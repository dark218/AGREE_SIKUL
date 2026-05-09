<?php

namespace Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Services\Entities\ServicesTransport;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;

class ServiceTransportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:services-transport-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:services-transport-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:services-transport-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:services-transport-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:services-transport-statut', ['only' => ['statut']]);
    }

    /**
     * Display a listing of services transport.
     */
    public function index(Request $request)
    {
        try {
            \Log::info('🚀 ServiceTransportController@index - START');
            \Log::info('User: ' . auth()->user()?->email);

            $filters = [
                'zone' => $request->input('zone'),
                'ligne' => $request->input('ligne'),
                'ecole_id' => $request->input('ecole_id'),
                'annee_scolaire_id' => $request->input('annee_scolaire_id'),
                'etat' => $request->input('etat'),
            ];

            \Log::info('Filters applied: ' . json_encode($filters));

            $query = ServicesTransport::query()
                ->with(['anneeScolaire', 'ecole'])
                ->orderBy('created_at', 'DESC');

            if (!empty($filters['zone'])) {
                $query->where('zone', 'LIKE', '%' . $filters['zone'] . '%');
            }

            if (!empty($filters['ligne'])) {
                $query->where('ligne', 'LIKE', '%' . $filters['ligne'] . '%');
            }

            if (!empty($filters['ecole_id'])) {
                $query->where('ecole_id', $filters['ecole_id']);
            }

            if (!empty($filters['annee_scolaire_id'])) {
                $query->where('annee_scolaire_id', $filters['annee_scolaire_id']);
            }

            if (!empty($filters['etat'])) {
                $query->where('etat', $filters['etat']);
            }

            $transport = $query->paginate(10)->withQueryString();
            $ecoles = Ecole::where('statut', 'actif')->get(['id', 'nom']);
            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get(['id', 'libelle']);

            \Log::info('📊 Data loaded: ' . $transport->count() . ' transports, ' . $ecoles->count() . ' schools, ' . $anneesScolaires->count() . ' school years');
            \Log::info('📝 Rendering: Services::ServicesTransports/Index');

            $response = Inertia::render('Services::ServicesTransports/Index', [
                'transport' => $transport,
                'filters' => $filters,
                'ecoles' => $ecoles,
                'anneesScolaires' => $anneesScolaires,
                'title' => 'Gestion des Transports',
            ]);

            \Log::info('✅ Inertia::render completed successfully');
            return $response;
        } catch (\Throwable $th) {
            \Log::error("❌ Error loading services transport: " . $th->getMessage());
            \Log::error("Stack trace: " . $th->getTraceAsString());
            return redirect()->route('home')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for creating a new services transport.
     */
    public function create()
    {
        try {
            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get(['id', 'libelle']);
            $ecoles = Ecole::where('statut', 'actif')->get(['id', 'nom']);
            $campuses = Campus::where('statut', 'actif')->get(['id', 'nom']);

            return Inertia::render('Services::ServicesTransports/Create', [
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
            ]);
        } catch (\Throwable $th) {
            \Log::error("Error loading create form: " . $th->getMessage());
            return redirect()->route('services-transport.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Store a newly created services transport.
     */
    public function store(Request $request)
    {
        $request->validate([
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            'ecole_id' => 'nullable|exists:ecoles,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'zone' => 'nullable|string|max:255',
            'ligne' => 'nullable|string|max:255',
            'point_depart' => 'nullable|string|max:255',
            'point_1' => 'nullable|string|max:255',
            'point_2' => 'nullable|string|max:255',
            'point_3' => 'nullable|string|max:255',
            'point_4' => 'nullable|string|max:255',
            'point_5' => 'nullable|string|max:255',
            'point_6' => 'nullable|string|max:255',
            'point_7' => 'nullable|string|max:255',
            'point_8' => 'nullable|string|max:255',
            'point_9' => 'nullable|string|max:255',
            'point_10' => 'nullable|string|max:255',
            'tarif_mensuel' => 'nullable|numeric|min:0',
            'tarif_trimestriel' => 'nullable|numeric|min:0',
            'tarif_semestriel' => 'nullable|numeric|min:0',
            'tarif_annuel' => 'nullable|numeric|min:0',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            ServicesTransport::create([
                'annee_scolaire_id' => $request->annee_scolaire_id,
                'ecole_id' => $request->ecole_id,
                'campus_id' => $request->campus_id,
                'zone' => $request->zone,
                'ligne' => $request->ligne,
                'point_depart' => $request->point_depart,
                'point_1' => $request->point_1,
                'point_2' => $request->point_2,
                'point_3' => $request->point_3,
                'point_4' => $request->point_4,
                'point_5' => $request->point_5,
                'point_6' => $request->point_6,
                'point_7' => $request->point_7,
                'point_8' => $request->point_8,
                'point_9' => $request->point_9,
                'point_10' => $request->point_10,
                'tarif_mensuel' => $request->tarif_mensuel,
                'tarif_trimestriel' => $request->tarif_trimestriel,
                'tarif_semestriel' => $request->tarif_semestriel,
                'tarif_annuel' => $request->tarif_annuel,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'etat' => $request->etat ?? 'actif',
                'creation_username' => auth()->user()->name ?? '',
            ]);

            return redirect()
                ->route('services-transport.index')
                ->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            \Log::error("Error creating services transport: " . $e->getMessage());
            return redirect()
                ->route('services-transport.create')
                ->with('error', __('enregistrementechec'))
                ->withInput();
        }
    }

    /**
     * Show the specified services transport.
     */
    public function show(ServicesTransport $service)
    {
        try {
            \Log::info("📍 ServiceTransportController@show START");
            \Log::info("   Route params: " . json_encode(request()->route()->parameters()));
            \Log::info("   Service object exists: " . ($service ? 'YES' : 'NO'));
            \Log::info("   Service ID: " . $service->id);

            // Manual fallback if implicit binding fails
            if (!$service->exists) {
                $id = request()->route('service');
                \Log::info("   ⚠️ Implicit binding failed, using manual fallback with ID: " . $id);
                $service = ServicesTransport::find($id);
                \Log::info("   ✓ Manual fallback result - ID: " . ($service?->id ?? 'NULL'));
            }

            \Log::info("   Service zone: " . $service->zone);
            \Log::info("   Service all attributes: " . json_encode($service->getAttributes()));
            $service->load(['anneeScolaire', 'ecole', 'campus']);
            \Log::info("✓ Loaded relationships");
            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get(['id', 'libelle']);
            $ecoles = Ecole::where('statut', 'actif')->get(['id', 'nom']);
            $campuses = Campus::where('statut', 'actif')->get(['id', 'nom']);

            // Map to plain array for Inertia serialization
            $item = [
                'id' => $service->id,
                'annee_scolaire_id' => $service->annee_scolaire_id,
                'ecole_id' => $service->ecole_id,
                'campus_id' => $service->campus_id,
                'zone' => $service->zone,
                'ligne' => $service->ligne,
                'point_depart' => $service->point_depart,
                'point_1' => $service->point_1,
                'point_2' => $service->point_2,
                'point_3' => $service->point_3,
                'point_4' => $service->point_4,
                'point_5' => $service->point_5,
                'point_6' => $service->point_6,
                'point_7' => $service->point_7,
                'point_8' => $service->point_8,
                'point_9' => $service->point_9,
                'point_10' => $service->point_10,
                'tarif_mensuel' => $service->tarif_mensuel,
                'tarif_trimestriel' => $service->tarif_trimestriel,
                'tarif_semestriel' => $service->tarif_semestriel,
                'tarif_annuel' => $service->tarif_annuel,
                'date_debut' => $service->date_debut?->toDateString(),
                'date_fin' => $service->date_fin?->toDateString(),
                'etat' => $service->etat,
                'anneeScolaire' => $service->anneeScolaire,
                'ecole' => $service->ecole,
                'campus' => $service->campus,
            ];

            \Log::info("📦 Item mapped: " . json_encode($item));

            return Inertia::render('Services::ServicesTransports/Show', [
                'item' => $item,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
            ]);
        } catch (\Throwable $th) {
            \Log::error("Error loading services transport: " . $th->getMessage());
            return redirect()->route('home')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Show the form for editing the specified services transport.
     */
    public function edit(ServicesTransport $service)
    {
        try {
            // Manual fallback if implicit binding fails
            if (!$service->exists) {
                $id = request()->route('service');
                $service = ServicesTransport::find($id);
            }

            $service->load(['anneeScolaire', 'ecole', 'campus']);
            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get(['id', 'libelle']);
            $ecoles = Ecole::where('statut', 'actif')->get(['id', 'nom']);
            $campuses = Campus::where('statut', 'actif')->get(['id', 'nom']);

            // Map to plain array for Inertia serialization
            $item = [
                'id' => $service->id,
                'annee_scolaire_id' => $service->annee_scolaire_id,
                'ecole_id' => $service->ecole_id,
                'campus_id' => $service->campus_id,
                'zone' => $service->zone,
                'ligne' => $service->ligne,
                'point_depart' => $service->point_depart,
                'point_1' => $service->point_1,
                'point_2' => $service->point_2,
                'point_3' => $service->point_3,
                'point_4' => $service->point_4,
                'point_5' => $service->point_5,
                'point_6' => $service->point_6,
                'point_7' => $service->point_7,
                'point_8' => $service->point_8,
                'point_9' => $service->point_9,
                'point_10' => $service->point_10,
                'tarif_mensuel' => $service->tarif_mensuel,
                'tarif_trimestriel' => $service->tarif_trimestriel,
                'tarif_semestriel' => $service->tarif_semestriel,
                'tarif_annuel' => $service->tarif_annuel,
                'date_debut' => $service->date_debut?->toDateString(),
                'date_fin' => $service->date_fin?->toDateString(),
                'etat' => $service->etat,
                'anneeScolaire' => $service->anneeScolaire,
                'ecole' => $service->ecole,
                'campus' => $service->campus,
            ];

            return Inertia::render('Services::ServicesTransports/Edit', [
                'item' => $item,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
            ]);
        } catch (\Throwable $th) {
            \Log::error("Error loading edit form: " . $th->getMessage());
            return redirect()->route('services-transport.index')->with('error', __('error_loading_form'));
        }
    }

    /**
     * Update the specified services transport in storage.
     */
    public function update(Request $request, ServicesTransport $service)
    {
        $request->validate([
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            'ecole_id' => 'nullable|exists:ecoles,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'zone' => 'nullable|string|max:255',
            'ligne' => 'nullable|string|max:255',
            'point_depart' => 'nullable|string|max:255',
            'point_1' => 'nullable|string|max:255',
            'point_2' => 'nullable|string|max:255',
            'point_3' => 'nullable|string|max:255',
            'point_4' => 'nullable|string|max:255',
            'point_5' => 'nullable|string|max:255',
            'point_6' => 'nullable|string|max:255',
            'point_7' => 'nullable|string|max:255',
            'point_8' => 'nullable|string|max:255',
            'point_9' => 'nullable|string|max:255',
            'point_10' => 'nullable|string|max:255',
            'tarif_mensuel' => 'nullable|numeric|min:0',
            'tarif_trimestriel' => 'nullable|numeric|min:0',
            'tarif_semestriel' => 'nullable|numeric|min:0',
            'tarif_annuel' => 'nullable|numeric|min:0',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            $service->update([
                'annee_scolaire_id' => $request->annee_scolaire_id,
                'ecole_id' => $request->ecole_id,
                'campus_id' => $request->campus_id,
                'zone' => $request->zone,
                'ligne' => $request->ligne,
                'point_depart' => $request->point_depart,
                'point_1' => $request->point_1,
                'point_2' => $request->point_2,
                'point_3' => $request->point_3,
                'point_4' => $request->point_4,
                'point_5' => $request->point_5,
                'point_6' => $request->point_6,
                'point_7' => $request->point_7,
                'point_8' => $request->point_8,
                'point_9' => $request->point_9,
                'point_10' => $request->point_10,
                'tarif_mensuel' => $request->tarif_mensuel,
                'tarif_trimestriel' => $request->tarif_trimestriel,
                'tarif_semestriel' => $request->tarif_semestriel,
                'tarif_annuel' => $request->tarif_annuel,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'etat' => $request->etat ?? 'actif',
                'modification_username' => auth()->user()->name ?? '',
            ]);

            return redirect()
                ->route('services-transport.index')
                ->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            \Log::error("Error updating services transport: " . $e->getMessage());
            return redirect()
                ->route('services-transport.edit', $service->id)
                ->with('error', __('enregistrementechec'))
                ->withInput();
        }
    }

    /**
     * Toggle status of services transport.
     */
    public function statut(ServicesTransport $service)
    {
        try {
            $service->etat = $service->etat === 'actif' ? 'inactif' : 'actif';
            $service->modification_username = auth()->user()->name ?? '';
            $service->save();

            return redirect()
                ->route('services-transport.index')
                ->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            \Log::error("Error toggling status: " . $e->getMessage());
            return redirect()
                ->route('services-transport.index')
                ->with('error', __('enregistrementechec'));
        }
    }

    /**
     * Remove the specified services transport from storage.
     */
    public function destroy(ServicesTransport $service)
    {
        try {
            $service->delete();

            return redirect()
                ->route('services-transport.index')
                ->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            \Log::error("Error deleting services transport: " . $e->getMessage());
            return redirect()
                ->route('services-transport.index')
                ->with('error', __('enregistrementechec'));
        }
    }
}
