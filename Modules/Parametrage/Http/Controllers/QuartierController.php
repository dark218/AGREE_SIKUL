<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Quartier;
use Modules\Parametrage\Entities\Commune;
use Modules\Parametrage\Entities\Departement;
use Modules\Parametrage\Entities\Region;
use Modules\Parametrage\Entities\Pays;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;

class QuartierController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-quartier-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:parametrage-quartier-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-quartier-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-quartier-delete', ['only' => ['destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Quartier::withoutTrashed()->with(['commune', 'departement', 'region', 'pays']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('commune_id')) {
                $query->where('commune_id', $request->commune_id);
            }

            if ($request->filled('departement_id')) {
                $query->where('departement_id', $request->departement_id);
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

            $quartiers = $query->paginate(10)->withQueryString();

            $communes = Commune::select('id', 'libelle')->get();

            $departements = Departement::select('id', 'libelle')->get();

            $regions = Region::select('id', 'libelle')->get();

            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Quartiers/Index', [
                'quartiers' => $quartiers,
                'filters' => $request->only(['code', 'libelle', 'commune_id', 'departement_id', 'region_id', 'pays_id']),
                'communes' => $communes,
                'departements' => $departements,
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            Log::error('quartiercontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $communes = Commune::select('id', 'libelle', 'departement_id')->with('departement:id,region_id,pays_id')->get();

            $departements = Departement::select('id', 'libelle', 'region_id', 'pays_id')->get();

            $regions = Region::select('id', 'libelle', 'pays_id')->get();

            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Quartiers/Create', [
                'communes' => $communes,
                'departements' => $departements,
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            Log::error('quartiercontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:quartiers,code',
                'libelle' => 'required|string|max:255',
                'ville' => 'nullable|string|max:100',
                'commune_id' => 'required|exists:communes,id',
                'departement_id' => 'nullable|exists:departements,id',
                'region_id' => 'nullable|exists:regions,id',
                'pays_id' => 'nullable|exists:pays,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            // Validate geographic hierarchy
            $this->validateHierarchy(
                $request->commune_id,
                $request->departement_id,
                $request->region_id,
                $request->pays_id
            );

            $validated['created_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? 'actif';
            Quartier::create($validated);

            return redirect()
                ->route('parametrage.quartiers.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            Log::error('quartiercontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(Quartier $quartier)
    {
        try {
            $communes = Commune::select('id', 'libelle', 'departement_id')->with('departement:id,region_id,pays_id')->get();

            $departements = Departement::select('id', 'libelle', 'region_id', 'pays_id')->get();

            $regions = Region::select('id', 'libelle', 'pays_id')->get();

            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Quartiers/Show', [
                'quartier' => $quartier,
                'communes' => $communes,
                'departements' => $departements,
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            Log::error('quartiercontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(Quartier $quartier)
    {
        try {
            $communes = Commune::select('id', 'libelle', 'departement_id')->with('departement:id,region_id,pays_id')->get();

            $departements = Departement::select('id', 'libelle', 'region_id', 'pays_id')->get();

            $regions = Region::select('id', 'libelle', 'pays_id')->get();

            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Quartiers/Edit', [
                'quartier' => $quartier,
                'communes' => $communes,
                'departements' => $departements,
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            Log::error('quartiercontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, Quartier $quartier)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:quartiers,code,' . $quartier->id,
                'libelle' => 'required|string|max:255',
                'ville' => 'nullable|string|max:100',
                'commune_id' => 'required|exists:communes,id',
                'departement_id' => 'nullable|exists:departements,id',
                'region_id' => 'nullable|exists:regions,id',
                'pays_id' => 'nullable|exists:pays,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            // Validate geographic hierarchy
            $this->validateHierarchy(
                $request->commune_id,
                $request->departement_id,
                $request->region_id,
                $request->pays_id
            );

            $validated['updated_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? $quartier->etat;
            $quartier->update($validated);

            return redirect()
                ->route('parametrage.quartiers.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            Log::error('quartiercontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(Quartier $quartier)
    {
        try {
            $quartier->deleted_by = auth()->id();
            $quartier->save();
            $quartier->delete();

            return redirect()->route('parametrage.quartiers.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            Log::error('quartiercontroller@error: ' . $e->getmessage());
            return redirect()->route('parametrage.quartiers.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(Quartier $quartier)
    {
        try {
            $newEtat = $quartier->etat === 'actif' ? 'inactif' : 'actif';
            $quartier->etat = $newEtat;
            $quartier->updated_by = auth()->id();
            $quartier->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.quartiers.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            Log::error('quartiercontroller@error: ' . $e->getmessage());
            return redirect()->route('parametrage.quartiers.index')->with('error', 'Erreur lors du changement de statut');
        }
    }

    private function validateHierarchy($communeId, $departementId, $regionId, $paysId)
    {
        $commune = Commune::find($communeId);
        $departement = Departement::find($departementId);
        $region = Region::find($regionId);

        if (!$commune || !$departement || !$region) {
            throw new \Exception('Commune, département ou région invalide.');
        }

        if ($commune->departement_id != $departementId) {
            throw new \Exception('La commune n\'appartient pas au département sélectionné.');
        }

        if ($departement->region_id != $regionId) {
            throw new \Exception('Le département n\'appartient pas à la région sélectionnée.');
        }

        if ($departement->pays_id != $paysId) {
            throw new \Exception('Le département n\'appartient pas au pays sélectionné.');
        }

        if ($region->pays_id != $paysId) {
            throw new \Exception('La région n\'appartient pas au pays sélectionné.');
        }
    }
}
