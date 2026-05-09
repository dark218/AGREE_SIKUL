<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\TypeEnseignement;
use Modules\Parametrage\Entities\AnneeScolaire;
use Illuminate\Foundation\Validation\ValidatesRequests;

class TypeEnseignementController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-typeenseignement-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-typeenseignement-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-typeenseignement-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-typeenseignement-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-typeenseignement-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = TypeEnseignement::with(['anneeScolaire']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $typeEnseignements = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::TypesEnseignement/Index', [
                'types' => $typeEnseignements,
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
            $anneesScolaires = AnneeScolaire::all()->map(function($annee) {
                return [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ];
            });

            return Inertia::render('Parametrage::TypesEnseignement/Create', [
                'anneesScolaires' => $anneesScolaires,
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
                'code' => 'required|string|max:100|unique:type_enseignement,code',
                'libelle' => 'required|string|max:255',
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['created_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? 'actif';
            TypeEnseignement::create($validated);

            return redirect()
                ->route('parametrage.types_enseignement.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(TypeEnseignement $typeEnseignement)
    {
        try {
            $anneesScolaires = AnneeScolaire::all()->map(function($annee) {
                return [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ];
            });

            return Inertia::render('Parametrage::TypesEnseignement/Show', [
                'typeEnseignement' => $typeEnseignement,
                'anneesScolaires' => $anneesScolaires,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(TypeEnseignement $typeEnseignement)
    {
        try {
            $anneesScolaires = AnneeScolaire::all()->map(function($annee) {
                return [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ];
            });

            return Inertia::render('Parametrage::TypesEnseignement/Edit', [
                'typeEnseignement' => $typeEnseignement,
                'anneesScolaires' => $anneesScolaires,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, TypeEnseignement $typeEnseignement)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:type_enseignement,code,' . $typeEnseignement->id,
                'libelle' => 'required|string|max:255',
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['updated_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? $typeEnseignement->etat;
            $typeEnseignement->update($validated);

            return redirect()
                ->route('parametrage.types_enseignement.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(TypeEnseignement $typeEnseignement)
    {
        try {
            $typeEnseignement->deleted_by = auth()->id();
            $typeEnseignement->save();
            $typeEnseignement->delete();

            return redirect()->route('parametrage.types_enseignement.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.types_enseignement.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(TypeEnseignement $typeEnseignement)
    {
        try {
            $newEtat = $typeEnseignement->etat === 'actif' ? 'inactif' : 'actif';
            $typeEnseignement->etat = $newEtat;
            $typeEnseignement->updated_by = auth()->id();
            $typeEnseignement->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.types_enseignement.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.types_enseignement.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
