<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\TypeEtablissementSpe;
use Illuminate\Foundation\Validation\ValidatesRequests;

class TypeEtablissementSpeController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-typeetablissementspe-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:parametrage-typeetablissementspe-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-typeetablissementspe-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-typeetablissementspe-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-typeetablissementspe-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = TypeEtablissementSpe::query();

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $typesEtablissementsSpe = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::TypesEtablissementSpe/Index', [
                'typesEtablissementsSpe' => $typesEtablissementsSpe,
                'filters' => $request->only(['code', 'libelle', 'etat']),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return Inertia::render('Parametrage::TypesEtablissementSpe/Create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:types_etablissements_spe,code',
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();

            TypeEtablissementSpe::create($validated);

            return redirect()->route('parametrage.types_etablissement_spe.index')->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function show(TypeEtablissementSpe $typesEtablissementSpe)
    {
        return Inertia::render('Parametrage::TypesEtablissementSpe/Show', ['item' => $typesEtablissementSpe]);
    }

    public function edit(TypeEtablissementSpe $typesEtablissementSpe)
    {
        return Inertia::render('Parametrage::TypesEtablissementSpe/Edit', ['item' => $typesEtablissementSpe]);
    }

    public function update(Request $request, TypeEtablissementSpe $typesEtablissementSpe)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:types_etablissements_spe,code,' . $typesEtablissementSpe->id,
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['updated_by'] = auth()->id();
            $typesEtablissementSpe->update($validated);

            return redirect()->route('parametrage.types_etablissement_spe.index')->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function destroy(TypeEtablissementSpe $typesEtablissementSpe)
    {
        try {
            $typesEtablissementSpe->deleted_by = auth()->id();
            $typesEtablissementSpe->save();
            $typesEtablissementSpe->delete();

            return redirect()->route('parametrage.types_etablissement_spe.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            return redirect()->route('parametrage.types_etablissement_spe.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function activate(TypeEtablissementSpe $typesEtablissementSpe)
    {
        try {
            $newEtat = $typesEtablissementSpe->etat === 'actif' ? 'inactif' : 'actif';
            $typesEtablissementSpe->etat = $newEtat;
            $typesEtablissementSpe->updated_by = auth()->id();
            $typesEtablissementSpe->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.types_etablissement_spe.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            return redirect()->route('parametrage.types_etablissement_spe.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
