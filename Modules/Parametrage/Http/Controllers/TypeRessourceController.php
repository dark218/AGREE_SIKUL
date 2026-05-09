<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\TypeRessource;
use Modules\Parametrage\Entities\Ecole;
use Illuminate\Foundation\Validation\ValidatesRequests;

class TypeRessourceController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-typeressource-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-typeressource-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-typeressource-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-typeressource-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-typeressource-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = TypeRessource::query()
                ->with(['ecole']);

            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('libelle', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            if ($request->filled('ecole_id')) {
                $query->where('ecole_id', $request->ecole_id);
            }

            $typeRessources = $query->paginate(10)->withQueryString();
            $ecoles = Ecole::select('id', 'nom')->orderBy('nom')->get();

            return Inertia::render('Parametrage::TypesRessource/Index', [
                'title' => 'Types de Ressources',
                'typeRessources' => $typeRessources,
                'filters' => $request->only(['search', 'etat', 'ecole_id']),
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $ecoles = Ecole::select('id', 'nom')->orderBy('nom')->get()->toArray();
            return Inertia::render('Parametrage::TypesRessource/Create', [
                'title' => 'Créer un Type de Ressource',
                'ecoles' => $ecoles,
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
                'code' => 'required|string|max:100|unique:type_ressources,code',
                'libelle' => 'required|string|max:255',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();
            TypeRessource::create($validated);

            return redirect()
                ->route('parametrage.types_ressource.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(TypeRessource $typeRessource)
    {
        try {
            $ecoles = Ecole::select('id', 'nom')->orderBy('nom')->get()->toArray();
            return Inertia::render('Parametrage::TypesRessource/Show', [
                'title' => 'Détails Type de Ressource',
                'item' => $typeRessource,
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(TypeRessource $typeRessource)
    {
        try {
            $ecoles = Ecole::select('id', 'nom')->orderBy('nom')->get()->toArray();
            return Inertia::render('Parametrage::TypesRessource/Edit', [
                'title' => 'Modifier Type de Ressource',
                'item' => $typeRessource,
                'ecoles' => $ecoles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, TypeRessource $typeRessource)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:type_ressources,code,' . $typeRessource->id,
                'libelle' => 'required|string|max:255',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $typeRessource->etat;
            $validated['updated_by'] = auth()->id();
            $typeRessource->update($validated);

            return redirect()
                ->route('parametrage.types_ressource.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(TypeRessource $typeRessource)
    {
        try {
            $typeRessource->deleted_by = auth()->id();
            $typeRessource->save();
            $typeRessource->delete();

            return redirect()->route('parametrage.types_ressource.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.types_ressource.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(TypeRessource $typeRessource)
    {
        try {
            $newEtat = $typeRessource->etat === 'actif' ? 'inactif' : 'actif';
            $typeRessource->etat = $newEtat;
            $typeRessource->updated_by = auth()->id();
            $typeRessource->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.types_ressource.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.types_ressource.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
