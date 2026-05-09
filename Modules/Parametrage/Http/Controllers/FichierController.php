<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Fichier;
use Illuminate\Foundation\Validation\ValidatesRequests;

class FichierController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-fichier-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:parametrage-fichier-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-fichier-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-fichier-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-fichier-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Fichier::query();

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $fichiers = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::Fichiers/Index', [
                'fichiers' => $fichiers,
                'filters' => $request->only(['code', 'libelle', 'etat']),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return Inertia::render('Parametrage::Fichiers/Create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:fichiers,code',
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'chemin_fichier' => 'nullable|string',
                'type_fichier' => 'nullable|string',
                'taille_fichier' => 'nullable|integer',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();

            Fichier::create($validated);

            return redirect()->route('parametrage.fichiers.index')->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function show(Fichier $fichier)
    {
        return Inertia::render('Parametrage::Fichiers/Show', ['fichier' => $fichier]);
    }

    public function edit(Fichier $fichier)
    {
        return Inertia::render('Parametrage::Fichiers/Edit', ['fichier' => $fichier]);
    }

    public function update(Request $request, Fichier $fichier)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:fichiers,code,' . $fichier->id,
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'chemin_fichier' => 'nullable|string',
                'type_fichier' => 'nullable|string',
                'taille_fichier' => 'nullable|integer',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['updated_by'] = auth()->id();
            $fichier->update($validated);

            return redirect()->route('parametrage.fichiers.index')->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function destroy(Fichier $fichier)
    {
        try {
            $fichier->deleted_by = auth()->id();
            $fichier->save();
            $fichier->delete();

            return redirect()->route('parametrage.fichiers.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            return redirect()->route('parametrage.fichiers.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function activate(Fichier $fichier)
    {
        try {
            $newEtat = $fichier->etat === 'actif' ? 'inactif' : 'actif';
            $fichier->etat = $newEtat;
            $fichier->updated_by = auth()->id();
            $fichier->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.fichiers.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            return redirect()->route('parametrage.fichiers.index')->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
