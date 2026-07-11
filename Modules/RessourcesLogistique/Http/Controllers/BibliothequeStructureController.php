<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Campus;
use Modules\RessourcesLogistique\Entities\BibliothequeStructure;

/**
 * CRUD des bibliothèques (lieux) — sous-fonctionnalité "Liste".
 */
class BibliothequeStructureController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:bibliotheque-structures-list',   ['only' => ['index', 'show']]);
        $this->middleware('permission.check:bibliotheque-structures-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:bibliotheque-structures-edit',   ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:bibliotheque-structures-delete', ['only' => ['destroy', 'statut']]);
    }

    private function rules(): array
    {
        return [
            'code'                 => 'nullable|string|max:100',
            'libelle'              => 'required|string|max:255',
            'localisation'         => 'nullable|string|max:255',
            'campus_id'            => 'nullable|exists:campuses,id',
            'responsable'          => 'nullable|string|max:255',
            'statut_disponibilite' => 'nullable|in:disponible,indisponible,maintenance',
            'etat'                 => 'nullable|in:actif,inactif',
        ];
    }

    private function options(): array
    {
        return [
            'campuses' => Campus::whereNull('deleted_at')->orderBy('nom')
                ->get(['id', 'nom'])
                ->map(fn ($c) => ['id' => $c->id, 'libelle' => $c->nom]),
        ];
    }

    public function index(Request $request)
    {
        try {
            $query = BibliothequeStructure::query()->with('campus');

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('code', 'like', "%$s%")
                      ->orWhere('libelle', 'like', "%$s%")
                      ->orWhere('localisation', 'like', "%$s%");
                });
            }
            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $structures = $query->paginate(10)->withQueryString();

            return Inertia::render('RessourcesLogistique::BibliothequeStructures/Index', [
                'structures' => $structures,
                'filters'    => $request->only(['search', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error('BibliothequeStructure', 'index', $th->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        return Inertia::render('RessourcesLogistique::BibliothequeStructures/Create', $this->options());
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->rules());
            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['statut_disponibilite'] = $validated['statut_disponibilite'] ?? 'disponible';

            BibliothequeStructure::create($validated);

            return redirect()->route('bibliotheque-structures.index')
                ->with('success', 'Bibliothèque créée avec succès');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error('BibliothequeStructure', 'store', $th->getMessage());
            return back()->with('error', 'Erreur lors de la création : ' . $th->getMessage())->withInput();
        }
    }

    public function show(BibliothequeStructure $bibliotheque_structure)
    {
        return Inertia::render('RessourcesLogistique::BibliothequeStructures/Show', array_merge(
            $this->options(),
            ['structure' => $bibliotheque_structure->load('campus')]
        ));
    }

    public function edit(BibliothequeStructure $bibliotheque_structure)
    {
        return Inertia::render('RessourcesLogistique::BibliothequeStructures/Edit', array_merge(
            $this->options(),
            ['structure' => $bibliotheque_structure->load('campus')]
        ));
    }

    public function update(Request $request, BibliothequeStructure $bibliotheque_structure)
    {
        try {
            $validated = $request->validate($this->rules());
            $bibliotheque_structure->update($validated);

            return redirect()->route('bibliotheque-structures.index')
                ->with('success', 'Bibliothèque modifiée avec succès');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error('BibliothequeStructure', 'update', $th->getMessage());
            return back()->with('error', 'Erreur lors de la modification : ' . $th->getMessage())->withInput();
        }
    }

    public function destroy(BibliothequeStructure $bibliotheque_structure)
    {
        try {
            $bibliotheque_structure->delete();
            return redirect()->route('bibliotheque-structures.index')->with('success', 'Bibliothèque supprimée');
        } catch (\Throwable $th) {
            log_error('BibliothequeStructure', 'destroy', $th->getMessage());
            return back()->with('error', 'Erreur lors de la suppression');
        }
    }

    public function statut(BibliothequeStructure $bibliotheque_structure)
    {
        try {
            $bibliotheque_structure->etat = $bibliotheque_structure->etat === 'actif' ? 'inactif' : 'actif';
            $bibliotheque_structure->save();
            return redirect()->route('bibliotheque-structures.index')->with('success', 'Statut modifié');
        } catch (\Throwable $th) {
            log_error('BibliothequeStructure', 'statut', $th->getMessage());
            return back()->with('error', 'Erreur lors du changement de statut');
        }
    }
}
