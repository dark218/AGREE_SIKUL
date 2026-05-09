<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Zone;
use Modules\Parametrage\Entities\Region;
use Modules\Parametrage\Entities\Pays;
use Illuminate\Support\Facades\Log;

class ZoneController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:parametrage-zone-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:parametrage-zone-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-zone-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-zone-delete', ['only' => ['destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Zone::withoutTrashed()->with(['region', 'pays']);

            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('libelle', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $zones = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::Zones/Index', [
                'zones' => $zones,
                'filters' => $request->only(['search', 'etat']),
            ]);
        } catch (\Exception $e) {
            Log::error('zonecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $regions = Region::select('id', 'libelle')->get();

            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Zones/Create', [
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            Log::error('zonecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:zones,code',
            'libelle' => 'required|string|max:255',
            'type_zone' => 'required|string|max:100',
            'coordinates' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'region_id' => 'nullable|exists:regions,id',
            'pays_id' => 'nullable|exists:pays,id',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            // Validate geographic hierarchy
            $this->validateHierarchy($request->region_id, $request->pays_id);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();
            Zone::create($validated);

            return redirect()
                ->route('parametrage.zones.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            Log::error('zonecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(Zone $zone)
    {
        try {
            $regions = Region::select('id', 'libelle')->get();

            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Zones/Show', [
                'zone' => $zone->load(['region', 'pays']),
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            Log::error('zonecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(Zone $zone)
    {
        try {
            $regions = Region::select('id', 'libelle')->get();

            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Zones/Edit', [
                'item' => $zone->load(['region', 'pays']),
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            Log::error('zonecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:zones,code,' . $zone->id,
            'libelle' => 'required|string|max:255',
            'type_zone' => 'required|string|max:100',
            'coordinates' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'region_id' => 'nullable|exists:regions,id',
            'pays_id' => 'nullable|exists:pays,id',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            // Validate geographic hierarchy
            $this->validateHierarchy($request->region_id, $request->pays_id);

            $validated['etat'] = $validated['etat'] ?? $zone->etat;
            $validated['updated_by'] = auth()->id();
            $zone->update($validated);

            return redirect()
                ->route('parametrage.zones.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            Log::error('zonecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(Zone $zone)
    {
        try {
            $zone->deleted_by = auth()->id();
            $zone->save();
            $zone->delete();

            return redirect()->route('parametrage.zones.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            Log::error('zonecontroller@error: ' . $e->getmessage());
            return redirect()->route('parametrage.zones.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(Zone $zone)
    {
        try {
            $newEtat = $zone->etat === 'actif' ? 'inactif' : 'actif';
            $zone->etat = $newEtat;
            $zone->updated_by = auth()->id();
            $zone->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.zones.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            Log::error('zonecontroller@error: ' . $e->getmessage());
            return redirect()->route('parametrage.zones.index')->with('error', 'Erreur lors du changement de statut');
        }
    }

    private function validateHierarchy($regionId, $paysId)
    {
        $region = Region::find($regionId);

        if (!$region) {
            throw new \Exception('Région invalide.');
        }

        if ($region->pays_id != $paysId) {
            throw new \Exception('La région n\'appartient pas au pays sélectionné.');
        }
    }
}