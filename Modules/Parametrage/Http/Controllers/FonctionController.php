<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Fonction;
use Modules\Parametrage\Entities\UniteOrganisationnelle;
use Illuminate\Foundation\Validation\ValidatesRequests;

class FonctionController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:fonction-list', ['only' => ['index']]);
        $this->middleware('permission.check:fonction-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:fonction-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:fonction-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:fonction-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Fonction::query()->with('uniteOrganisationnelle');

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('unite_organisationnelle_id')) {
                $query->where('unite_organisationnelle_id', $request->unite_organisationnelle_id);
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $fonctions = $query->paginate(10)->withQueryString();

            $unites_organisationnelles = UniteOrganisationnelle::all()->map(function($unite) {
                return ['id' => $unite->id, 'libelle' => $unite->libelle];
            });

            return Inertia::render('Parametrage::Fonctions/Index', [
                'fonctions' => $fonctions,
                'filters' => $request->only(['code', 'libelle', 'unite_organisationnelle_id', 'etat']),
                'unites_organisationnelles' => $unites_organisationnelles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $unites_organisationnelles = UniteOrganisationnelle::all();
            return Inertia::render('Parametrage::Fonctions/Create', [
                'unites_organisationnelles' => $unites_organisationnelles,
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
                'code' => 'required|string|max:100|unique:fonctions,code',
                'libelle' => 'required|string|max:255',
                'unite_organisationnelle_id' => 'required|exists:unites_organisationnelles,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();
            Fonction::create($validated);

            return redirect()
                ->route('parametrage.fonctions.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(Fonction $fonction)
    {
        try {
            $unites_organisationnelles = UniteOrganisationnelle::all();
            return Inertia::render('Parametrage::Fonctions/Show', [
                'fonction' => $fonction->load(['uniteOrganisationnelle']),
                'unites_organisationnelles' => $unites_organisationnelles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(Fonction $fonction)
    {
        try {
            $unites_organisationnelles = UniteOrganisationnelle::all();
            return Inertia::render('Parametrage::Fonctions/Edit', [
                'item' => $fonction->load(['uniteOrganisationnelle']),
                'unites_organisationnelles' => $unites_organisationnelles,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, Fonction $fonction)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:fonctions,code,' . $fonction->id,
                'libelle' => 'required|string|max:255',
                'unite_organisationnelle_id' => 'required|exists:unites_organisationnelles,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $fonction->etat;
            $validated['updated_by'] = auth()->id();
            $fonction->update($validated);

            return redirect()
                ->route('parametrage.fonctions.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(Fonction $fonction)
    {
        try {
            $fonction->deleted_by = auth()->id();
            $fonction->save();
            $fonction->delete();

            return redirect()->route('parametrage.fonctions.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.fonctions.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(Fonction $fonction)
    {
        try {
            $newEtat = $fonction->etat === 'actif' ? 'inactif' : 'actif';
            $fonction->etat = $newEtat;
            $fonction->updated_by = auth()->id();
            $fonction->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.fonctions.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.fonctions.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
