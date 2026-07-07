<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\GroupeMatiere;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Institution;
use Illuminate\Foundation\Validation\ValidatesRequests;

class GroupeMatiereController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-groupematiere-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-groupematiere-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-groupematiere-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-groupematiere-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-groupematiere-activate', ['only' => ['activate']]);
    }

    /**
     * Lookups partagés Create / Edit / Show.
     * Le niveau inclut section_id + cycle_id pour permettre l'auto-fill côté front.
     */
    private function lookups(): array
    {
        return [
            'niveaux' => NiveauEtude::orderBy('libelle')
                ->get(['id', 'libelle', 'section_id', 'cycle_id'])
                ->toArray(),
            'sections' => Section::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
            'cycles' => CycleEnseignement::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
            'matieres' => MatiereUnite::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
            'ecoles' => Ecole::orderBy('nom')
                ->get(['id', 'nom as libelle', 'institution_id', 'campus_id'])
                ->toArray(),
            'institutions' => Institution::orderBy('nom')->get(['id', 'nom as libelle'])->toArray(),
        ];
    }

    public function index(Request $request)
    {
        try {
            $query = GroupeMatiere::with(['niveau', 'section', 'cycle', 'matieres']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }
            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }
            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $groupeMatieres = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::GroupesMatiere/Index', [
                'title' => 'Groupes de Matières',
                'groupe_matieres' => $groupeMatieres,
                'filters' => $request->only(['code', 'libelle', 'etat']),
            ]);
        } catch (\Exception $e) {
            \Log::error('GroupeMatiere Index Error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        return Inertia::render('Parametrage::GroupesMatiere/Create', $this->lookups());
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->baseRules());
            $matiereIds = $this->pluckMatiereIds($request);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();
            $groupe = GroupeMatiere::create($validated);
            $groupe->matieres()->sync($matiereIds);

            return redirect()
                ->route('parametrage.groupes_matiere.index')
                ->with('success', 'Créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('GroupeMatiere Store Error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la création: ' . $e->getMessage())->withInput();
        }
    }

    public function show(GroupeMatiere $groupeMatiere)
    {
        $groupeMatiere->load(['niveau', 'section', 'cycle', 'matieres']);
        return Inertia::render('Parametrage::GroupesMatiere/Show', array_merge(
            $this->lookups(),
            ['groupeMatiere' => $groupeMatiere]
        ));
    }

    public function edit(GroupeMatiere $groupeMatiere)
    {
        $item = $groupeMatiere->load(['niveau', 'section', 'cycle', 'matieres']);
        return Inertia::render('Parametrage::GroupesMatiere/Edit', array_merge(
            $this->lookups(),
            ['item' => $item]
        ));
    }

    public function update(Request $request, GroupeMatiere $groupeMatiere)
    {
        try {
            $validated = $request->validate($this->baseRules($groupeMatiere->id));
            $matiereIds = $this->pluckMatiereIds($request);

            $validated['etat'] = $validated['etat'] ?? $groupeMatiere->etat;
            $validated['updated_by'] = auth()->id();
            $groupeMatiere->update($validated);
            $groupeMatiere->matieres()->sync($matiereIds);

            return redirect()
                ->route('parametrage.groupes_matiere.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('GroupeMatiere Update Error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la modification: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(GroupeMatiere $groupeMatiere)
    {
        try {
            $groupeMatiere->deleted_by = auth()->id();
            $groupeMatiere->save();
            // Cascade on delete de la pivot → nettoyage auto des groupe_matiere_items.
            $groupeMatiere->delete();
            return redirect()->route('parametrage.groupes_matiere.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            return redirect()->route('parametrage.groupes_matiere.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(GroupeMatiere $groupeMatiere)
    {
        try {
            $newEtat = $groupeMatiere->etat === 'actif' ? 'inactif' : 'actif';
            $groupeMatiere->etat = $newEtat;
            $groupeMatiere->updated_by = auth()->id();
            $groupeMatiere->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.groupes_matiere.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            return redirect()->route('parametrage.groupes_matiere.index')->with('error', 'Erreur lors du changement de statut');
        }
    }

    private function baseRules(?int $ignoreId = null): array
    {
        $codeRule = 'required|string|max:100|unique:groupes_matieres,code' . ($ignoreId ? ',' . $ignoreId : '');
        return [
            'code'              => $codeRule,
            'libelle'           => 'required|string|max:255',
            'ecole_id'          => 'nullable|exists:ecoles,id',
            'institution_id'    => 'nullable|exists:institutions,id',
            'niveau_id'         => 'required|exists:niveaux_etudes,id',
            'section_id'        => 'nullable|exists:sections,id',
            'cycle_id'          => 'nullable|exists:cycles_enseignement,id',
            'matieres'          => 'nullable|array',
            'matieres.*'        => 'integer|exists:matieres_unites,id',
            'etat'              => 'nullable|in:actif,inactif',
        ];
    }

    private function pluckMatiereIds(Request $request): array
    {
        $ids = $request->input('matieres', []);
        if (!is_array($ids)) return [];
        return array_values(array_unique(array_filter($ids, fn($v) => is_numeric($v))));
    }
}
