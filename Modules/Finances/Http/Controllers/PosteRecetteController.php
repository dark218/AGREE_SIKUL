<?php

namespace Modules\Finances\Http\Controllers;

use Modules\Finances\Entities\PosteRecette;
use Modules\Finances\Entities\LigneRecette;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PosteRecetteController extends Controller
{
    public function index(Request $request)
    {
        $query = PosteRecette::query();

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        $postes = $query->paginate(10);

        return Inertia::render('Finances::PostesRecettes/Index', [
            'postes_recettes' => $postes,
            'filters' => $request->only(['code', 'etat'])
        ]);
    }

    public function create()
    {
        $lignesRecettes = \Modules\Finances\Entities\LigneRecette::where('etat', 'actif')->get()->map(function($item) {
            return [
                'id' => $item->id,
                'libelle' => $item->libelle
            ];
        })->toArray();

        return Inertia::render('Finances::PostesRecettes/Create', [
            'title' => 'Créer un poste de recettes',
            'lignes_recettes' => $lignesRecettes
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:postes_recettes',
            'libelle' => 'required',
            'compte_comptable' => 'nullable',
            'ligne_recette_id' => 'nullable|exists:lignes_recettes,id',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['creation_username'] = auth()->user()->name;
        PosteRecette::create($validated);

        return redirect()->route('finances.postes-recettes.index')
            ->with('success', 'Poste de recettes créé');
    }

    public function show(PosteRecette $posteRecette)
    {
        $lignesRecettes = LigneRecette::where('etat', 'actif')->get()->map(function($item) {
            return [
                'id' => $item->id,
                'libelle' => $item->libelle
            ];
        })->toArray();

        return Inertia::render('Finances::PostesRecettes/Show', [
            'poste_recette' => $posteRecette,
            'lignes_recettes' => $lignesRecettes,
            'title' => 'Voir le poste'
        ]);
    }

    public function edit(PosteRecette $posteRecette)
    {
        $lignesRecettes = LigneRecette::where('etat', 'actif')->get()->map(function($item) {
            return [
                'id' => $item->id,
                'libelle' => $item->libelle
            ];
        })->toArray();

        return Inertia::render('Finances::PostesRecettes/Edit', [
            'poste_recette' => $posteRecette,
            'lignes_recettes' => $lignesRecettes,
            'title' => 'Modifier le poste'
        ]);
    }

    public function update(Request $request, PosteRecette $posteRecette)
    {
        $validated = $request->validate([
            'code' => 'required|unique:postes_recettes,code,' . $posteRecette->id,
            'libelle' => 'required',
            'compte_comptable' => 'nullable',
            'ligne_recette_id' => 'nullable|exists:lignes_recettes,id',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['modification_username'] = auth()->user()->name;
        $posteRecette->update($validated);

        return redirect()->route('finances.postes-recettes.index')
            ->with('success', 'Poste de recettes modifié');
    }

    public function destroy(PosteRecette $posteRecette)
    {
        $posteRecette->delete();
        return redirect()->route('finances.postes-recettes.index')
            ->with('success', 'Poste de recettes supprimé');
    }

    public function statut(PosteRecette $posteRecette)
    {
        $posteRecette->etat = $posteRecette->etat === 'actif' ? 'inactif' : 'actif';
        $posteRecette->save();
        return redirect()->route('finances.postes-recettes.index');
    }
}
