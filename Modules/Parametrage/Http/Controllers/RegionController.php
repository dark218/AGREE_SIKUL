<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Region;
use Modules\Parametrage\Entities\Pays;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-region-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-region-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-region-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-region-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-region-activate', ['only' => ['activate']]);
    }

    // INDEX - Lister tous les enregistrements avec pagination/filtres
    public function index(Request $request)
    {
        try {
            $query = Region::withoutTrashed()->with('pays');

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('pays_id')) {
                $query->where('pays_id', $request->pays_id);
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $regions = $query->paginate(10)->withQueryString();

            // Get all pays for filter dropdown
            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Regions/Index', [
                'regions' => $regions,
                'pays' => $pays,
                'filters' => $request->only(['code', 'libelle', 'pays_id']),
            ]);
        } catch (\Exception $e) {
            Log::error('regioncontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    // CREATE - Afficher le formulaire de création
    public function create()
    {
        try {
            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Regions/Create', [
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            Log::error('regioncontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    // STORE - Créer un nouvel enregistrement
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:regions,code',
            'libelle' => 'required|string|max:255',
            'pays_id' => 'required|exists:pays,id',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            $validated['created_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? 'actif';
            Region::create($validated);

            return redirect()
                ->route('parametrage.regions.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            Log::error('regioncontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    // SHOW - Afficher les détails
    public function show(Region $region)
    {
        try {
            $region->load('pays');
            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Regions/Show', [
                'region' => $region,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    // EDIT - Afficher le formulaire d'édition
    public function edit(Region $region)
    {
        try {
            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Regions/Edit', [
                'region' => $region,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    // UPDATE - Modifier un enregistrement
    public function update(Request $request, Region $region)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:regions,code,' . $region->id,
            'libelle' => 'required|string|max:255',
            'pays_id' => 'required|exists:pays,id',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            $validated['updated_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? $region->etat;
            $region->update($validated);

            return redirect()
                ->route('parametrage.regions.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    // DESTROY - Supprimer (soft delete)
    public function destroy(Region $region)
    {
        try {
            $region->deleted_by = auth()->id();
            $region->save();
            $region->delete();

            return redirect()->route('parametrage.regions.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.regions.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    // ACTIVATE - Activer/Désactiver
    public function activate(Region $region)
    {
        try {
            $newEtat = $region->etat === 'actif' ? 'inactif' : 'actif';
            $region->etat = $newEtat;
            $region->updated_by = auth()->id();
            $region->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.regions.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.regions.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
