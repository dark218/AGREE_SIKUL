<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\Equipement;
use Modules\RessourcesLogistique\Entities\MaintenanceEquipement;

class MaintenanceEquipementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:maintenances-equipements-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:maintenances-equipements-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:maintenances-equipements-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:maintenances-equipements-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = MaintenanceEquipement::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%$search%")
                      ->orWhereHas('equipement', fn ($qe) => $qe->where('nom', 'like', "%$search%"));
                });
            }

            if ($request->filled('type_maintenance')) {
                $query->where('type_maintenance', $request->input('type_maintenance'));
            }

            $maintenances = $query
                ->with(['equipement', 'technicien'])
                ->orderByDesc('date_maintenance')
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('RessourcesLogistique::MaintenancesEquipements/Index', [
                'maintenances' => $maintenances,
                'filters'      => $request->only(['search', 'type_maintenance']),
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('RessourcesLogistique::MaintenancesEquipements/Create', [
                'equipements' => Equipement::orderBy('nom')->get(['id', 'nom']),
                'techniciens' => User::orderBy('nom')->get(['id', 'nom', 'prenoms']),
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'equipement_id'    => 'required|exists:equipements,id',
                'date_maintenance' => 'required|date',
                // Enum DB : preventive, corrective, inspection
                'type_maintenance' => 'required|in:preventive,corrective,inspection',
                'description'      => 'nullable|string',
                'cout_cents'       => 'nullable|integer|min:0',
                'technicien_id'    => 'nullable|exists:users,id',
            ]);

            MaintenanceEquipement::create($validated);

            return redirect()->route('maintenances-equipements.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function show(MaintenanceEquipement $maintenanceEquipement)
    {
        try {
            $maintenanceEquipement->load(['equipement', 'technicien']);

            return Inertia::render('RessourcesLogistique::MaintenancesEquipements/Show', [
                'maintenance' => $maintenanceEquipement,
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(MaintenanceEquipement $maintenanceEquipement)
    {
        try {
            return Inertia::render('RessourcesLogistique::MaintenancesEquipements/Edit', [
                'maintenance' => $maintenanceEquipement->load(['equipement', 'technicien']),
                'equipements' => Equipement::orderBy('nom')->get(['id', 'nom']),
                'techniciens' => User::orderBy('nom')->get(['id', 'nom', 'prenoms']),
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, MaintenanceEquipement $maintenanceEquipement)
    {
        try {
            $validated = $request->validate([
                'equipement_id'    => 'required|exists:equipements,id',
                'date_maintenance' => 'required|date',
                'type_maintenance' => 'required|in:preventive,corrective,inspection',
                'description'      => 'nullable|string',
                'cout_cents'       => 'nullable|integer|min:0',
                'technicien_id'    => 'nullable|exists:users,id',
            ]);

            $maintenanceEquipement->update($validated);

            return redirect()->route('maintenances-equipements.show', $maintenanceEquipement)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()])->withInput();
        }
    }

    public function destroy(MaintenanceEquipement $maintenanceEquipement)
    {
        try {
            $maintenanceEquipement->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(MaintenanceEquipement $maintenanceEquipement)
    {
        try {
            if ($maintenanceEquipement->trashed()) {
                $maintenanceEquipement->restore();
            } else {
                $maintenanceEquipement->delete();
            }

            return redirect()->route('maintenances-equipements.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
