<?php

namespace Modules\Finances\Http\Controllers;

use Modules\Finances\Entities\LigneDepense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LigneDepenseController extends Controller
{
    public function index(Request $request)
    {
        $query = LigneDepense::query();

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        $lignes = $query->paginate(10);

        return Inertia::render('Finances::LignesDepenses/Index', [
            'lignes_depenses' => $lignes,
            'filters' => $request->only(['code', 'etat'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Finances::LignesDepenses/Create', [
            'title' => 'Créer une ligne de dépenses'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:lignes_depenses',
            'libelle' => 'required',
            'compte_comptable' => 'nullable',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['creation_username'] = auth()->user()->name;
        LigneDepense::create($validated);

        return redirect()->route('finances.lignes-depenses.index')
            ->with('success', 'Ligne de dépenses créée');
    }

    public function show(LigneDepense $ligneDepense)
    {
        return Inertia::render('Finances::LignesDepenses/Show', [
            'ligne_depense' => $ligneDepense,
            'title' => 'Voir la ligne'
        ]);
    }

    public function edit(LigneDepense $ligneDepense)
    {
        return Inertia::render('Finances::LignesDepenses/Edit', [
            'ligne_depense' => $ligneDepense,
            'title' => 'Modifier la ligne'
        ]);
    }

    public function update(Request $request, LigneDepense $ligneDepense)
    {
        $validated = $request->validate([
            'code' => 'required|unique:lignes_depenses,code,' . $ligneDepense->id,
            'libelle' => 'required',
            'compte_comptable' => 'nullable',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['modification_username'] = auth()->user()->name;
        $ligneDepense->update($validated);

        return redirect()->route('finances.lignes-depenses.index')
            ->with('success', 'Ligne de dépenses modifiée');
    }

    public function destroy(LigneDepense $ligneDepense)
    {
        $ligneDepense->delete();
        return redirect()->route('finances.lignes-depenses.index')
            ->with('success', 'Ligne de dépenses supprimée');
    }

    public function statut(LigneDepense $ligneDepense)
    {
        $ligneDepense->etat = $ligneDepense->etat === 'actif' ? 'inactif' : 'actif';
        $ligneDepense->save();
        return redirect()->route('finances.lignes-depenses.index');
    }
}
