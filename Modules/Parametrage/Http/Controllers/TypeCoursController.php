<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Modules\Parametrage\Entities\TypeCours;
use Modules\Parametrage\Entities\CycleEnseignement;
use Illuminate\Foundation\Validation\ValidatesRequests;

class TypeCoursController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-typecours-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-typecours-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-typecours-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-typecours-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-typecours-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = TypeCours::query()->with(['cycle']);

            if ($request->filled('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('libelle', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $typeCours = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::TypeCours/Index', [
                'typeCours' => $typeCours,
                'filters' => $request->only(['search', 'etat']),
            ]);
        } catch (\Exception $e) {
            Log::error('typecourscontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $cycles = CycleEnseignement::all()->map(function($cycle) {
                return [
                    'id' => $cycle->id,
                    'libelle' => $cycle->libelle,
                ];
            });

            return Inertia::render('Parametrage::TypeCours/Create', [
                                'cycles' => $cycles,
            ]);
        } catch (\Exception $e) {
            Log::error('typecourscontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:type_cours,code',
                'libelle' => 'required|string|max:255',
                'cycle_id' => 'required|exists:cycles_enseignement,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();
            TypeCours::create($validated);

            return redirect()
                ->route('parametrage.types_cours.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            Log::error('typecourscontroller@error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    public function show(TypeCours $typeCours)
    {
        try {
            $cycles = CycleEnseignement::all()->map(function($cycle) {
                return [
                    'id' => $cycle->id,
                    'libelle' => $cycle->libelle,
                ];
            });

            return Inertia::render('Parametrage::TypeCours/Show', [
                'typeCours' => $typeCours,
                                'cycles' => $cycles,
            ]);
        } catch (\Exception $e) {
            Log::error('typecourscontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(TypeCours $typeCours)
    {
        try {
            $cycles = CycleEnseignement::all()->map(function($cycle) {
                return [
                    'id' => $cycle->id,
                    'libelle' => $cycle->libelle,
                ];
            });

            return Inertia::render('Parametrage::TypeCours/Edit', [
                'typeCours' => $typeCours,
                                'cycles' => $cycles,
            ]);
        } catch (\Exception $e) {
            Log::error('typecourscontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, TypeCours $typeCours)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:type_cours,code,' . $typeCours->id,
                'libelle' => 'required|string|max:255',
                'cycle_id' => 'required|exists:cycles_enseignement,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $typeCours->etat;
            $validated['updated_by'] = auth()->id();
            $typeCours->update($validated);

            return redirect()
                ->route('parametrage.types_cours.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            Log::error('typecourscontroller@error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(TypeCours $typeCours)
    {
        try {
            $typeCours->deleted_by = auth()->id();
            $typeCours->save();
            $typeCours->delete();

            return redirect()->route('parametrage.types_cours.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            Log::error('typecourscontroller@error: ' . $e->getMessage());
            return redirect()->route('parametrage.types_cours.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(TypeCours $typeCours)
    {
        try {
            $newEtat = $typeCours->etat === 'actif' ? 'inactif' : 'actif';
            $typeCours->etat = $newEtat;
            $typeCours->updated_by = auth()->id();
            $typeCours->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.types_cours.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            Log::error('typecourscontroller@error: ' . $e->getMessage());
            return redirect()->route('parametrage.types_cours.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
