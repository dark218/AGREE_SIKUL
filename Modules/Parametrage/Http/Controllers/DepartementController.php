<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Departement;
use Modules\Parametrage\Entities\Region;
use Modules\Parametrage\Entities\Pays;
use Illuminate\Foundation\Validation\ValidatesRequests;

class DepartementController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-departement-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-departement-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-departement-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-departement-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-departement-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Departement::withoutTrashed()->with(['region', 'pays']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('region_id')) {
                $query->where('region_id', $request->region_id);
            }

            if ($request->filled('pays_id')) {
                $query->where('pays_id', $request->pays_id);
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $departements = $query->paginate(10)->withQueryString();

            // Get all regions and pays for filter dropdowns
            $regions = Region::select('id', 'libelle', 'pays_id')->get();
            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Departements/Index', [
                'departements' => $departements,
                'regions' => $regions,
                'pays' => $pays,
                'filters' => $request->only(['code', 'libelle', 'region_id', 'pays_id']),
            ]);
        } catch (\Exception $e) {
            \Log::error('departementcontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $regions = Region::select('id', 'libelle', 'pays_id')->get();

            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Departements/Create', [
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            \Log::error('departementcontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:departements,code',
                'libelle' => 'required|string|max:255',
                'region_id' => 'required|exists:regions,id',
                'pays_id' => 'required|exists:pays,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            // Validate geographic hierarchy
            $this->validateHierarchy($request->region_id, $request->pays_id);

            $validated['created_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? 'actif';
            Departement::create($validated);

            return redirect()
                ->route('parametrage.departements.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            \Log::error('departementcontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(Departement $departement)
    {
        try {
            $departement->load(['region', 'pays']);

            $regions = Region::select('id', 'libelle', 'pays_id')->get();

            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Departements/Show', [
                'departement' => $departement,
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            \Log::error('departementcontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(Departement $departement)
    {
        try {
            $regions = Region::select('id', 'libelle', 'pays_id')->get();
            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Departements/Edit', [
                'item' => $departement,
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            \Log::error('departementcontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, Departement $departement)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:departements,code,' . $departement->id,
                'libelle' => 'required|string|max:255',
                'region_id' => 'required|exists:regions,id',
                'pays_id' => 'required|exists:pays,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            // Validate geographic hierarchy
            $this->validateHierarchy($request->region_id, $request->pays_id);

            $validated['updated_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? $departement->etat;
            $departement->update($validated);

            return redirect()
                ->route('parametrage.departements.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            \Log::error('departementcontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(Departement $departement)
    {
        try {
            $departement->deleted_by = auth()->id();
            $departement->save();
            $departement->delete();

            return redirect()->route('parametrage.departements.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            \Log::error('departementcontroller@error: ' . $e->getMessage());
            return redirect()->route('parametrage.departements.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(Departement $departement)
    {
        try {
            $newEtat = $departement->etat === 'actif' ? 'inactif' : 'actif';
            $departement->etat = $newEtat;
            $departement->updated_by = auth()->id();
            $departement->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.departements.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('departementcontroller@error: ' . $e->getMessage());
            return redirect()->route('parametrage.departements.index')->with('error', 'Erreur lors du changement de statut');
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
