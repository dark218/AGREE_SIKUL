<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Commune;
use Modules\Parametrage\Entities\Departement;
use Modules\Parametrage\Entities\Region;
use Modules\Parametrage\Entities\Pays;
use Illuminate\Foundation\Validation\ValidatesRequests;

class CommuneController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-commune-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:parametrage-commune-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-commune-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-commune-delete', ['only' => ['destroy', 'activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Commune::withoutTrashed()->with(['departement', 'region', 'pays']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
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

            $communes = $query->paginate(10)->withQueryString();

            $departements = Departement::select('id', 'libelle')->get();
            $regions = Region::select('id', 'libelle')->get();
            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Communes/Index', [
                'communes' => $communes,
                'filters' => $request->only(['code', 'libelle', 'departement_id', 'region_id', 'pays_id', 'etat']),
                'departements' => $departements,
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            \Log::error('CommuneController@index: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $departements = Departement::select('id', 'libelle')->get();
            $regions = Region::select('id', 'libelle')->get();
            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Communes/Create', [
                'departements' => $departements,
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            \Log::error('CommuneController@index: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('🔵 [COMMUNE] store() - REQUEST DATA', [
                'all_data' => $request->all(),
                'user_id' => auth()->id(),
            ]);

            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:communes,code',
                'libelle' => 'required|string|max:255',
                'departement_id' => 'required|exists:departements,id',
                'region_id' => 'required|exists:regions,id',
                'pays_id' => 'required|exists:pays,id',
                'etat' => 'required|in:actif,inactif',
            ]);

            \Log::info('✅ [COMMUNE] store() - VALIDATION PASSED', [
                'validated_data' => $validated,
            ]);

            // Validate geographic hierarchy
            $this->validateHierarchy(
                $request->departement_id,
                $request->region_id,
                $request->pays_id
            );

            $validated['created_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? 'actif';

            \Log::info('📝 [COMMUNE] store() - BEFORE CREATE', [
                'final_data' => $validated,
            ]);

            Commune::create($validated);

            \Log::info('✅ [COMMUNE] store() - CREATED SUCCESSFULLY');

            return redirect()
                ->route('parametrage.communes.index')
                ->with('success', 'Créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ [COMMUNE] store() - VALIDATION ERROR', [
                'errors' => $e->errors(),
            ]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('❌ [COMMUNE] store() - EXCEPTION', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    public function show(Commune $commune)
    {
        try {
            $departements = Departement::select('id', 'libelle')->get();
            $regions = Region::select('id', 'libelle')->get();
            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Communes/Show', [
                'commune' => $commune->load(['departement', 'region', 'pays']),
                'departements' => $departements,
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            \Log::error('CommuneController@index: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(Commune $commune)
    {
        try {
            $departements = Departement::select('id', 'libelle')->get();
            $regions = Region::select('id', 'libelle')->get();
            $pays = Pays::select('id', 'libelle')->get();

            return Inertia::render('Parametrage::Communes/Edit', [
                'commune' => $commune->load(['departement', 'region', 'pays']),
                'departements' => $departements,
                'regions' => $regions,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            \Log::error('CommuneController@index: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, Commune $commune)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:communes,code,' . $commune->id,
                'libelle' => 'required|string|max:255',
                'departement_id' => 'required|exists:departements,id',
                'region_id' => 'required|exists:regions,id',
                'pays_id' => 'required|exists:pays,id',
                'etat' => 'required|in:actif,inactif',
            ]);

            // Validate geographic hierarchy
            $this->validateHierarchy(
                $request->departement_id,
                $request->region_id,
                $request->pays_id
            );

            $validated['updated_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? $commune->etat;
            $commune->update($validated);

            return redirect()
                ->route('parametrage.communes.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            \Log::error('CommuneController@index: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(Commune $commune)
    {
        \Log::info('🔥 DESTROY CALLED - Commune ID: ' . $commune->id);

        try {
            $commune->deleted_by = auth()->id();
            $commune->save();
            $commune->forceDelete();

            \Log::info('✅ DESTROY SUCCESS');
            return redirect()->route('parametrage.communes.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            \Log::error('❌ DESTROY ERROR: ' . $e->getMessage());
            \Log::error('CommuneController@index: ' . $e->getMessage());
            return redirect()->route('parametrage.communes.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(Commune $commune)
    {
        \Log::info('🔥 ACTIVATE CALLED - Commune ID: ' . $commune->id . ', Etat: ' . $commune->etat);

        try {
            // Toggle between actif and inactif
            $newStatus = $commune->etat === 'actif' ? 'inactif' : 'actif';
            $commune->etat = $newStatus;
            $commune->updated_by = auth()->id();
            $commune->save();

            \Log::info('✅ ACTIVATE SUCCESS - New etat: ' . $newStatus);
            $message = $newStatus === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.communes.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            \Log::error('❌ ACTIVATE ERROR: ' . $e->getMessage());
            \Log::error('CommuneController@index: ' . $e->getMessage());
            return redirect()->route('parametrage.communes.index')->with('error', 'Erreur lors du changement de statut');
        }
    }

    private function validateHierarchy($departementId, $regionId, $paysId)
    {
        $departement = Departement::find($departementId);
        $region = Region::find($regionId);

        if (!$departement || !$region) {
            throw new \Exception('Département ou région invalide.');
        }

        if ($departement->region_id != $regionId) {
            throw new \Exception('Le département ne appartient pas à la région sélectionnée.');
        }

        if ($departement->pays_id != $paysId) {
            throw new \Exception('Le département n\'appartient pas au pays sélectionné.');
        }

        if ($region->pays_id != $paysId) {
            throw new \Exception('La région n\'appartient pas au pays sélectionné.');
        }
    }

}
