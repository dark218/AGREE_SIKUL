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
        try {
            $lignesRecettes = LigneRecette::where('etat', 'actif')->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'libelle' => $item->libelle
                ];
            })->toArray();
        } catch (\Throwable $e) {
            // On ne bloque pas l'affichage du formulaire si la liste des lignes
            // de recettes ne peut pas être chargée : on log et on continue à vide.
            \Log::error('PosteRecetteController@create - chargement lignes_recettes', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            $lignesRecettes = [];
        }

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

        try {
            // creation_username est aussi renseigné automatiquement par BaseModel
            // (full_login), mais on garde une valeur explicite en repli.
            $validated['creation_username'] = optional(auth()->user())->full_login ?? 'system';
            PosteRecette::create($validated);

            return redirect()->route('finances.postes-recettes.index')
                ->with('success', 'Poste de recettes créé');
        } catch (\Throwable $e) {
            \Log::error('PosteRecetteController@store - EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()
                ->with('error', 'Erreur lors de la création du poste de recettes : ' . $e->getMessage())
                ->withInput();
        }
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
