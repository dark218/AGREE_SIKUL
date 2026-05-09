<?php

namespace Modules\Finances\Http\Controllers;

use Modules\Finances\Entities\LigneRecette;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LigneRecetteController extends Controller
{
    public function index(Request $request)
    {
        $query = LigneRecette::query();

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        $lignes = $query->paginate(10);

        return Inertia::render('Finances::LignesRecettes/Index', [
            'lignes_recettes' => $lignes,
            'filters' => $request->only(['code', 'etat'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Finances::LignesRecettes/Create', [
            'title' => 'Créer une ligne de recettes'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:lignes_recettes',
            'libelle' => 'required',
            'compte_comptable' => 'nullable',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['creation_username'] = auth()->user()->name;
        LigneRecette::create($validated);

        return redirect()->route('finances.lignes-recettes.index')
            ->with('success', 'Ligne de recettes créée');
    }

    public function show(LigneRecette $ligneRecette)
    {
        return Inertia::render('Finances::LignesRecettes/Show', [
            'ligne_recette' => $ligneRecette,
            'title' => 'Voir la ligne'
        ]);
    }

    public function edit(LigneRecette $ligneRecette)
    {
        return Inertia::render('Finances::LignesRecettes/Edit', [
            'ligne_recette' => $ligneRecette,
            'title' => 'Modifier la ligne'
        ]);
    }

    public function update(Request $request, LigneRecette $ligneRecette)
    {
        $validated = $request->validate([
            'code' => 'required|unique:lignes_recettes,code,' . $ligneRecette->id,
            'libelle' => 'required',
            'compte_comptable' => 'nullable',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['modification_username'] = auth()->user()->name;
        $ligneRecette->update($validated);

        return redirect()->route('finances.lignes-recettes.index')
            ->with('success', 'Ligne de recettes modifiée');
    }

    public function destroy(LigneRecette $ligneRecette)
    {
        $ligneRecette->delete();
        return redirect()->route('finances.lignes-recettes.index')
            ->with('success', 'Ligne de recettes supprimée');
    }

    public function statut(LigneRecette $ligneRecette)
    {
        $ligneRecette->etat = $ligneRecette->etat === 'actif' ? 'inactif' : 'actif';
        $ligneRecette->save();
        return redirect()->route('finances.lignes-recettes.index');
    }
}
