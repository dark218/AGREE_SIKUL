<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\CycleEnseignement;
use Illuminate\Foundation\Validation\ValidatesRequests;

class CycleEnseignementController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-cycleenseignement-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-cycleenseignement-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-cycleenseignement-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-cycleenseignement-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-cycleenseignement-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            \Log::info('=== CycleEnseignementController::index called ===');
            $query = CycleEnseignement::query();

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $cycles = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::CyclesEnseignement/Index', [
                'cycles' => $cycles,
                'filters' => $request->only(['code', 'libelle', 'etat']),
            ]);
        } catch (\Exception $e) {
            \Log::error('CycleEnseignementController::index ERROR: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        return Inertia::render('Parametrage::CyclesEnseignement/Create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:cycles_enseignement,code',
                'libelle' => 'required|string|max:255',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['created_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? 'actif';
            CycleEnseignement::create($validated);

            return redirect()
                ->route('parametrage.cycles_enseignement.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(CycleEnseignement $cycleEnseignement)
    {
        return Inertia::render('Parametrage::CyclesEnseignement/Show', [
            'cycleEnseignement' => $cycleEnseignement,
        ]);
    }

    public function edit(CycleEnseignement $cycleEnseignement)
    {
        return Inertia::render('Parametrage::CyclesEnseignement/Edit', [
            'cycleEnseignement' => $cycleEnseignement,
        ]);
    }

    public function update(Request $request, CycleEnseignement $cycleEnseignement)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:cycles_enseignement,code,' . $cycleEnseignement->id,
                'libelle' => 'required|string|max:255',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['updated_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? $cycleEnseignement->etat;
            $cycleEnseignement->update($validated);

            return redirect()
                ->route('parametrage.cycles_enseignement.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(CycleEnseignement $cycleEnseignement)
    {
        try {
            $cycleEnseignement->deleted_by = auth()->id();
            $cycleEnseignement->save();
            $cycleEnseignement->delete();

            return redirect()->route('parametrage.cycles_enseignement.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.cycles_enseignement.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(CycleEnseignement $cycleEnseignement)
    {
        try {
            $newEtat = $cycleEnseignement->etat === 'actif' ? 'inactif' : 'actif';
            $cycleEnseignement->etat = $newEtat;
            $cycleEnseignement->updated_by = auth()->id();
            $cycleEnseignement->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.cycles_enseignement.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.cycles_enseignement.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
