<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\TitreCivilite;
use Illuminate\Foundation\Validation\ValidatesRequests;

class TitreCiviliteController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-titrecivilite-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-titrecivilite-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-titrecivilite-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-titrecivilite-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-titrecivilite-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = TitreCivilite::query();

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $titreCivilites = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::TitreCivilites/Index', [
                'titreCivilites' => $titreCivilites,
                'filters' => $request->only(['code', 'libelle']),
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Parametrage::TitreCivilites/Create');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:titres_civilites,code',
                'libelle' => 'required|string|max:255',
                'sigle' => 'required|string|max:50',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();
            TitreCivilite::create($validated);

            return redirect()
                ->route('parametrage.titres_civilites.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(TitreCivilite $titreCivilite)
    {
        try {
            return Inertia::render('Parametrage::TitreCivilites/Show', [
                'titreCivilite' => $titreCivilite,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(TitreCivilite $titreCivilite)
    {
        try {
            return Inertia::render('Parametrage::TitreCivilites/Edit', [
                'titreCivilite' => $titreCivilite,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, TitreCivilite $titreCivilite)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:titres_civilites,code,' . $titreCivilite->id,
                'libelle' => 'required|string|max:255',
                'sigle' => 'required|string|max:50',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $titreCivilite->etat;
            $validated['updated_by'] = auth()->id();
            $titreCivilite->update($validated);

            return redirect()
                ->route('parametrage.titres_civilites.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(TitreCivilite $titreCivilite)
    {
        try {
            $titreCivilite->deleted_by = auth()->id();
            $titreCivilite->save();
            $titreCivilite->delete();

            return redirect()->route('parametrage.titres_civilites.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.titres_civilites.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(TitreCivilite $titreCivilite)
    {
        try {
            $newEtat = $titreCivilite->etat === 'actif' ? 'inactif' : 'actif';
            $titreCivilite->etat = $newEtat;
            $titreCivilite->updated_by = auth()->id();
            $titreCivilite->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.titres_civilites.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.titres_civilites.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
