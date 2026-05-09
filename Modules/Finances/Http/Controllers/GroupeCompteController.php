<?php

namespace Modules\Finances\Http\Controllers;

use Modules\Finances\Entities\GroupeCompte;
use Modules\Finances\Entities\PlanCompte;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupeCompteController extends Controller
{
    public function index(Request $request)
    {
        $query = GroupeCompte::query();

        if ($request->filled('code_groupe')) {
            $query->where('code_groupe', 'like', '%' . $request->code_groupe . '%');
        }
        if ($request->filled('libelle_groupes')) {
            $query->where('libelle_groupes', 'like', '%' . $request->libelle_groupes . '%');
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        $groupes = $query->paginate(10);

        return Inertia::render('Finances::GroupesComptes/Index', [
            'groupes_comptes' => $groupes,
            'filters' => $request->only(['code_groupe', 'libelle_groupes', 'etat'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Finances::GroupesComptes/Create', [
            'title' => 'Créer un groupe de comptes'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code_groupe' => 'required|unique:groupes_comptes',
            'libelle_groupes' => 'required',
            'nombre_comptes' => 'nullable|integer',
            'liste_comptes' => 'nullable|string',
            'description' => 'nullable|string',
            'etat' => 'required|in:actif,inactif',
            'plan_comptes' => 'nullable|array'
        ]);

        $validated['creation_username'] = auth()->user()->name;

        // Extract plan_comptes before saving groupe
        $planComptes = $validated['plan_comptes'] ?? [];
        unset($validated['plan_comptes']);

        $groupeCompte = GroupeCompte::create($validated);

        // Save plan_comptes if provided
        if (!empty($planComptes)) {
            foreach ($planComptes as $compte) {
                PlanCompte::create([
                    'groupe_comptes_id' => $groupeCompte->id,
                    'numero_compte' => $compte['numero_compte'],
                    'libelle_compte' => $compte['libelle_compte'],
                    'libelle_court' => $compte['libelle_court'] ?? null,
                    'compte_parent_id' => $compte['compte_parent_id'] ?? null,
                    'etat' => $compte['etat'] ?? 'actif',
                    'creation_username' => auth()->user()->name,
                ]);
            }
        }

        return redirect()->route('finances.groupes-comptes.index')
            ->with('success', 'Groupe de comptes créé avec succès');
    }

    public function show(GroupeCompte $groupeCompte)
    {
        $planComptes = $groupeCompte->planComptes()->get();

        return Inertia::render('Finances::GroupesComptes/Show', [
            'groupe_compte' => $groupeCompte,
            'planComptes' => $planComptes,
            'title' => 'Voir le groupe de comptes'
        ]);
    }

    public function edit(GroupeCompte $groupeCompte)
    {
        $planComptes = $groupeCompte->planComptes()->get();

        return Inertia::render('Finances::GroupesComptes/Edit', [
            'groupe_compte' => $groupeCompte,
            'planComptes' => $planComptes,
            'title' => 'Modifier le groupe de comptes'
        ]);
    }

    public function update(Request $request, GroupeCompte $groupeCompte)
    {
        $validated = $request->validate([
            'code_groupe' => 'required|unique:groupes_comptes,code_groupe,' . $groupeCompte->id,
            'libelle_groupes' => 'required',
            'nombre_comptes' => 'nullable|integer',
            'liste_comptes' => 'nullable|string',
            'description' => 'nullable|string',
            'etat' => 'required|in:actif,inactif',
            'plan_comptes' => 'nullable|array'
        ]);

        $validated['modification_username'] = auth()->user()->name;

        // Extract plan_comptes before updating groupe
        $planComptes = $validated['plan_comptes'] ?? [];
        unset($validated['plan_comptes']);

        $groupeCompte->update($validated);

        // Update plan_comptes
        if (!empty($planComptes)) {
            // Delete existing comptes that were added (not from database)
            $existingIds = $groupeCompte->planComptes()->pluck('id')->toArray();
            $newComptesIds = array_filter(array_map(fn($c) => $c['id'] ?? null, $planComptes), fn($id) => is_numeric($id));

            // Delete comptes that are no longer in the list
            $toDelete = array_diff($existingIds, $newComptesIds);
            if (!empty($toDelete)) {
                PlanCompte::whereIn('id', $toDelete)->delete();
            }

            // Add new comptes
            foreach ($planComptes as $compte) {
                if (!isset($compte['id']) || !is_numeric($compte['id'])) {
                    // Only create if it's a new compte (not from DB)
                    PlanCompte::create([
                        'groupe_comptes_id' => $groupeCompte->id,
                        'numero_compte' => $compte['numero_compte'],
                        'libelle_compte' => $compte['libelle_compte'],
                        'libelle_court' => $compte['libelle_court'] ?? null,
                        'compte_parent_id' => $compte['compte_parent_id'] ?? null,
                        'etat' => $compte['etat'] ?? 'actif',
                        'creation_username' => auth()->user()->name,
                    ]);
                }
            }
        }

        return redirect()->route('finances.groupes-comptes.index')
            ->with('success', 'Groupe de comptes modifié avec succès');
    }

    public function destroy(GroupeCompte $groupeCompte)
    {
        $groupeCompte->delete();

        return redirect()->route('finances.groupes-comptes.index')
            ->with('success', 'Groupe de comptes supprimé avec succès');
    }

    public function statut(GroupeCompte $groupeCompte)
    {
        $groupeCompte->etat = $groupeCompte->etat === 'actif' ? 'inactif' : 'actif';
        $groupeCompte->save();

        return redirect()->route('finances.groupes-comptes.index');
    }
}
