<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\TypeEtablissement;
use Modules\Parametrage\Entities\AnneeScolaire;
use Illuminate\Foundation\Validation\ValidatesRequests;

class TypeEtablissementController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-typeetablissement-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-typeetablissement-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-typeetablissement-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-typeetablissement-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-typeetablissement-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = TypeEtablissement::with(['anneeScolaire']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $typeEtablissements = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::TypeEtablissements/Index', [
                'types' => $typeEtablissements,
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

            return Inertia::render('Parametrage::TypeEtablissements/Create', [
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
                'code' => 'required|string|max:100|unique:type_etablissement,code',
                'libelle' => 'required|string|max:255',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['created_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? 'actif';
            TypeEtablissement::create($validated);

            return redirect()
                ->route('parametrage.types_etablissements.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(TypeEtablissement $typeEtablissement)
    {
        try {
            $anneesScolaires = AnneeScolaire::all()->map(function($annee) {
                return [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ];
            });

            return Inertia::render('Parametrage::TypeEtablissements/Show', [
                'typeEtablissement' => $typeEtablissement,
                'anneesScolaires' => $anneesScolaires,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(TypeEtablissement $typeEtablissement)
    {
        try {
            $anneesScolaires = AnneeScolaire::all()->map(function($annee) {
                return [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ];
            });

            return Inertia::render('Parametrage::TypeEtablissements/Edit', [
                'typeEtablissement' => $typeEtablissement,
                'anneesScolaires' => $anneesScolaires,
            ]);
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, TypeEtablissement $typeEtablissement)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:type_etablissement,code,' . $typeEtablissement->id,
                'libelle' => 'required|string|max:255',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['updated_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? $typeEtablissement->etat;
            $typeEtablissement->update($validated);

            return redirect()
                ->route('parametrage.types_etablissements.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(TypeEtablissement $typeEtablissement)
    {
        try {
            $typeEtablissement->deleted_by = auth()->id();
            $typeEtablissement->save();
            $typeEtablissement->delete();

            return redirect()->route('parametrage.types_etablissements.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.types_etablissements.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(TypeEtablissement $typeEtablissement)
    {
        try {
            $newEtat = $typeEtablissement->etat === 'actif' ? 'inactif' : 'actif';
            $typeEtablissement->etat = $newEtat;
            $typeEtablissement->updated_by = auth()->id();
            $typeEtablissement->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.types_etablissements.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
            return redirect()->route('parametrage.types_etablissements.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
