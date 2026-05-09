<?php

namespace Modules\Finances\Http\Controllers;

use Modules\Finances\Entities\PosteDepense;
use Modules\Finances\Entities\LigneDepense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PosteDepenseController extends Controller
{
    public function index(Request $request)
    {
        $query = PosteDepense::query();

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        $postes = $query->paginate(10);

        return Inertia::render('Finances::PostesDepenses/Index', [
            'postes_depenses' => $postes,
            'filters' => $request->only(['code', 'etat'])
        ]);
    }

    public function create()
    {
        $lignesDepenses = \Modules\Finances\Entities\LigneDepense::where('etat', 'actif')->get()->map(function($item) {
            return [
                'id' => $item->id,
                'libelle' => $item->libelle
            ];
        })->toArray();

        return Inertia::render('Finances::PostesDepenses/Create', [
            'title' => 'Créer un poste de dépenses',
            'lignes_depenses' => $lignesDepenses
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:postes_depenses',
            'libelle' => 'required',
            'compte_comptable' => 'nullable',
            'ligne_depense_id' => 'nullable|exists:lignes_depenses,id',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['creation_username'] = auth()->user()->name;
        PosteDepense::create($validated);

        return redirect()->route('finances.postes-depenses.index')
            ->with('success', 'Poste de dépenses créé');
    }

    public function show(PosteDepense $posteDepense)
    {
        $lignesDepenses = LigneDepense::where('etat', 'actif')->get()->map(function($item) {
            return [
                'id' => $item->id,
                'libelle' => $item->libelle
            ];
        })->toArray();

        return Inertia::render('Finances::PostesDepenses/Show', [
            'poste_depense' => $posteDepense,
            'lignes_depenses' => $lignesDepenses,
            'title' => 'Voir le poste'
        ]);
    }

    public function edit(PosteDepense $posteDepense)
    {
        $lignesDepenses = LigneDepense::where('etat', 'actif')->get()->map(function($item) {
            return [
                'id' => $item->id,
                'libelle' => $item->libelle
            ];
        })->toArray();

        return Inertia::render('Finances::PostesDepenses/Edit', [
            'poste_depense' => $posteDepense,
            'lignes_depenses' => $lignesDepenses,
            'title' => 'Modifier le poste'
        ]);
    }

    public function update(Request $request, PosteDepense $posteDepense)
    {
        $validated = $request->validate([
            'code' => 'required|unique:postes_depenses,code,' . $posteDepense->id,
            'libelle' => 'required',
            'compte_comptable' => 'nullable',
            'ligne_depense_id' => 'nullable|exists:lignes_depenses,id',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['modification_username'] = auth()->user()->name;
        $posteDepense->update($validated);

        return redirect()->route('finances.postes-depenses.index')
            ->with('success', 'Poste de dépenses modifié');
    }

    public function destroy(PosteDepense $posteDepense)
    {
        $posteDepense->delete();
        return redirect()->route('finances.postes-depenses.index')
            ->with('success', 'Poste de dépenses supprimé');
    }

    public function statut(PosteDepense $posteDepense)
    {
        $posteDepense->etat = $posteDepense->etat === 'actif' ? 'inactif' : 'actif';
        $posteDepense->save();
        return redirect()->route('finances.postes-depenses.index');
    }
}
