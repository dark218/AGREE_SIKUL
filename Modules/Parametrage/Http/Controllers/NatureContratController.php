<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\NatureContrat;
use Illuminate\Foundation\Validation\ValidatesRequests;

class NatureContratController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-naturecontrat-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-naturecontrat-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-naturecontrat-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-naturecontrat-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-naturecontrat-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = NatureContrat::query();

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $natureContrats = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::NaturesContrat/Index', [
                'title' => 'Natures de Contrat',
                'natures' => $natureContrats,
                'filters' => $request->only(['code', 'libelle', 'etat']),
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Parametrage::NaturesContrat/Create', [
                'title' => 'Créer une Nature de Contrat',
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:natures_contrats,code',
                'libelle' => 'required|string|max:255',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();
            NatureContrat::create($validated);

            return redirect()
                ->route('parametrage.natures_contrat.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(NatureContrat $natureContrat)
    {
        try {
            return Inertia::render('Parametrage::NaturesContrat/Show', [
                'title' => 'Détails Nature de Contrat',
                'item' => $natureContrat,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(NatureContrat $natureContrat)
    {
        try {
            return Inertia::render('Parametrage::NaturesContrat/Edit', [
                'title' => 'Modifier Nature de Contrat',
                'item' => $natureContrat,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, NatureContrat $natureContrat)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:natures_contrats,code,' . $natureContrat->id,
                'libelle' => 'required|string|max:255',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $natureContrat->etat;
            $validated['updated_by'] = auth()->id();
            $natureContrat->update($validated);

            return redirect()
                ->route('parametrage.natures_contrat.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(NatureContrat $natureContrat)
    {
        try {
            $natureContrat->deleted_by = auth()->id();
            $natureContrat->save();
            $natureContrat->delete();

            return redirect()->route('parametrage.natures_contrat.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.natures_contrat.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(NatureContrat $natureContrat)
    {
        try {
            $newEtat = $natureContrat->etat === 'actif' ? 'inactif' : 'actif';
            $natureContrat->etat = $newEtat;
            $natureContrat->updated_by = auth()->id();
            $natureContrat->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.natures_contrat.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.natures_contrat.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
