<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\MaintenanceEquipement;

class MaintenanceEquipementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:maintenances-equipements-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:maintenances-equipements-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:maintenances-equipements-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:maintenances-equipements-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = MaintenanceEquipement::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('titre', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $maintenances = $query->with(['equipement', 'technicien'])->paginate(10)->withQueryString()
                ->through(fn ($maintenance) => [
                    'id' => $maintenance->id,
                    'titre' => $maintenance->titre,
                    'description' => $maintenance->description,
                    'statut' => $maintenance->statut,
                    'date_debut' => $maintenance->date_debut?->toDateString(),
                    'date_fin' => $maintenance->date_fin?->toDateString(),
                    'equipement' => $maintenance->equipement?->nom,
                    'technicien' => $maintenance->technicien?->nom,
                ]);

            return Inertia::render('RessourcesLogistique::MaintenancesEquipements/Index', [
                'maintenances' => $maintenances,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('RessourcesLogistique::MaintenancesEquipements/Create');
        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'equipement_id' => 'required|exists:equipements,id',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:preventive,corrective,urgente',
                'date_debut' => 'required|date',
                'date_fin' => 'nullable|date|after_or_equal:date_debut',
                'cout_cents' => 'nullable|integer|min:0',
                'technicien_id' => 'nullable|exists:users,id',
                'observations' => 'nullable|string',
                'statut' => 'required|in:planifiee,en_cours,terminee,suspendue',
            ]);

            MaintenanceEquipement::create($validated);

            return redirect()->route('maintenances-equipements.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(MaintenanceEquipement $maintenanceEquipement)
    {
        try {
            $maintenanceEquipement->load('equipement', 'technicien');

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
                'maintenance' => $maintenanceEquipement->load('equipement', 'technicien'),
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
                'equipement_id' => 'required|exists:equipements,id',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:preventive,corrective,urgente',
                'date_debut' => 'required|date',
                'date_fin' => 'nullable|date|after_or_equal:date_debut',
                'cout_cents' => 'nullable|integer|min:0',
                'technicien_id' => 'nullable|exists:users,id',
                'observations' => 'nullable|string',
                'statut' => 'required|in:planifiee,en_cours,terminee,suspendue',
            ]);

            $maintenanceEquipement->update($validated);

            return redirect()->route('maintenances-equipements.show', $maintenanceEquipement)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Inventaire", "MaintenanceEquipementController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
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
