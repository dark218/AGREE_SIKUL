<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Communication\Entities\Traduction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TraductionController extends Controller
{
    public function index(Request $request)
    {
        $query = Traduction::query();

        if ($request->filled('groupe')) {
            $query->where('groupe', $request->groupe);
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        $traductions = $query->paginate(10)
            ->through(function ($item) {
                return [
                    'id' => $item->id,
                    'code_fr' => $item->code_fr,
                    'intitule_fr' => $item->intitule_fr,
                    'code_en' => $item->code_en,
                    'intitule_en' => $item->intitule_en,
                    'groupe' => $item->groupe,
                    'etat' => $item->etat,
                ];
            });

        return Inertia::render('Communication::Traductions/Index', [
            'traductions' => $traductions,
            'filters' => $request->only(['groupe', 'etat'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Communication::Traductions/Create', [
            'title' => 'Créer une traduction'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code_fr' => 'required|unique:traductions,code_fr',
            'intitule_fr' => 'required|string',
            'code_en' => 'required|unique:traductions,code_en',
            'intitule_en' => 'required|string',
            'groupe' => 'nullable|string',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['creation_username'] = auth()->user()->name;

        Traduction::create($validated);

        return redirect()->route('traductions.index')
            ->with('success', 'Traduction créée avec succès');
    }

    public function show(Traduction $traduction)
    {
        return Inertia::render('Communication::Traductions/Show', [
            'item' => $traduction,
            'title' => 'Voir la traduction'
        ]);
    }

    public function edit(Traduction $traduction)
    {
        return Inertia::render('Communication::Traductions/Edit', [
            'item' => $traduction,
            'title' => 'Modifier la traduction'
        ]);
    }

    public function update(Request $request, Traduction $traduction)
    {
        $validated = $request->validate([
            'code_fr' => 'required|unique:traductions,code_fr,' . $traduction->id,
            'intitule_fr' => 'required|string',
            'code_en' => 'required|unique:traductions,code_en,' . $traduction->id,
            'intitule_en' => 'required|string',
            'groupe' => 'nullable|string',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['modification_username'] = auth()->user()->name;

        $traduction->update($validated);

        return redirect()->route('traductions.index')
            ->with('success', 'Traduction modifiée avec succès');
    }

    public function destroy(Traduction $traduction)
    {
        $traduction->delete();

        return back()->with('success', 'Traduction supprimée avec succès');
    }

    public function statut(Traduction $traduction)
    {
        $traduction->etat = $traduction->etat === 'actif' ? 'inactif' : 'actif';
        $traduction->modification_username = auth()->user()->name;
        $traduction->save();

        return back()->with('success', 'Statut mis à jour');
    }
}
