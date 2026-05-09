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
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Pays;
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

    public function index(Request $request)
    {
        try {
            $query = GroupeMatiere::with([
                'niveau', 'section', 'cycle',
                'niveau', 'section', 'cycle', 'matiere1', 'matiere2', 'matiere3', 'matiere4', 'matiere5', 'matiere6', 'matiere7', 'matiere8', 'matiere9', 'matiere10', 'anneeScolaire', 'pays', 'matiere4', 'matiere5',
                'matiere6', 'matiere7', 'matiere8', 'matiere9', 'matiere10',
                'anneeScolaire', 'pays'
            ]);

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

            \Log::info('GroupeMatiere Index - Données envoyées:', [
                'count' => $groupeMatieres->count(),
                'total' => $groupeMatieres->total(),
                'data' => $groupeMatieres->toArray(),
            ]);

            return Inertia::render('Parametrage::GroupesMatiere/Index', [
                'title' => 'Groupes de Matières',
                'groupe_matieres' => $groupeMatieres,
                'filters' => $request->only(['code', 'libelle', 'etat']),
            ]);
        } catch (\Exception $e) {
            \Log::error('GroupeMatiere Index Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            // Logging handled by exception handler
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
                        $niveaux = NiveauEtude::all()->map(function($item) {
                return ['id' => $item->id, 'libelle' => $item->libelle];
            });
            $sections = Section::all()->map(function($item) {
                return ['id' => $item->id, 'libelle' => $item->libelle];
            });
            $cycles = CycleEnseignement::all()->map(function($item) {
                return ['id' => $item->id, 'libelle' => $item->libelle];
            });
            $matieres = MatiereUnite::all()->map(function($item) {
                return ['id' => $item->id, 'libelle' => $item->libelle];
            });
            $anneesScolaires = AnneeScolaire::all()->map(function($item) {
                return ['id' => $item->id, 'libelle' => $item->libelle];
            });
            $pays = Pays::all()->map(function($item) {
                return ['id' => $item->id, 'libelle' => $item->libelle];
            });

            return Inertia::render('Parametrage::GroupesMatiere/Create', [
                'niveaux' => $niveaux,
                'sections' => $sections,
                'cycles' => $cycles,
                'matieres' => $matieres,
                'anneesScolaires' => $anneesScolaires,
                'pays' => $pays,
            ]);
        } catch (\Exception $e) {
            \Log::error('GroupeMatiere Create Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('GroupeMatiere Store Request:', $request->all());

                        $validated = $request->validate([
                'code' => 'required|string|max:100|unique:groupes_matieres,code',
                'libelle' => 'required|string|max:255',
                'niveau_id' => 'required|exists:niveaux_etudes,id',
                'section_id' => 'required|exists:sections,id',
                'cycle_id' => 'required|exists:cycles_enseignement,id',
                'matiere1_id' => 'nullable|exists:matieres_unites,id',
                'matiere2_id' => 'nullable|exists:matieres_unites,id',
                'matiere3_id' => 'nullable|exists:matieres_unites,id',
                'matiere4_id' => 'nullable|exists:matieres_unites,id',
                'matiere5_id' => 'nullable|exists:matieres_unites,id',
                'matiere6_id' => 'nullable|exists:matieres_unites,id',
                'matiere7_id' => 'nullable|exists:matieres_unites,id',
                'matiere8_id' => 'nullable|exists:matieres_unites,id',
                'matiere9_id' => 'nullable|exists:matieres_unites,id',
                'matiere10_id' => 'nullable|exists:matieres_unites,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'pays_id' => 'nullable|exists:pays,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            \Log::info('GroupeMatiere Validated Data:', $validated);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();
            $created = GroupeMatiere::create($validated);

            \Log::info('GroupeMatiere Created Successfully:', $created->toArray());

            return redirect()
                ->route('parametrage.groupes_matiere.index')
                ->with('success', 'Créé avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('GroupeMatiere Validation Error: ', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return back()->withErrors($e->errors())->withInput()->with([
                'niveaux' => \Modules\Parametrage\Entities\NiveauEtude::all(),
                'sections' => \Modules\Parametrage\Entities\Section::all(),
                'cycles' => \Modules\Parametrage\Entities\CycleEnseignement::all(),
                'matieres' => \Modules\Parametrage\Entities\MatiereUnite::all(),
            ]);
        } catch (\Exception $e) {
            \Log::error('GroupeMatiere Store Error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors de la création: ' . $e->getMessage())->withInput();
        }
    }

    public function show(GroupeMatiere $groupeMatiere)
    {
        try {
            $niveaux = \Modules\Parametrage\Entities\NiveauEtude::all();
            $sections = \Modules\Parametrage\Entities\Section::all();
            $cycles = \Modules\Parametrage\Entities\CycleEnseignement::all();
            $matieres = \Modules\Parametrage\Entities\MatiereUnite::all();

            $groupeMatiere->load(['niveau', 'section', 'cycle', 'niveau', 'section', 'cycle', 'matiere1', 'matiere2', 'matiere3', 'matiere4', 'matiere5', 'matiere6', 'matiere7', 'matiere8', 'matiere9', 'matiere10', 'anneeScolaire', 'pays']);

            return Inertia::render('Parametrage::GroupesMatiere/Show', [
                'groupeMatiere' => $groupeMatiere,
                'niveaux' => $niveaux,
                'sections' => $sections,
                'cycles' => $cycles,
                'matieres' => $matieres,
            ]);
        } catch (\Exception $e) {
            \Log::error('GroupeMatiere Show Error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement: ' . $e->getMessage());
        }
    }

    public function edit(GroupeMatiere $groupeMatiere)
    {
        try {
            $niveaux = \Modules\Parametrage\Entities\NiveauEtude::all();
            $sections = \Modules\Parametrage\Entities\Section::all();
            $cycles = \Modules\Parametrage\Entities\CycleEnseignement::all();
            $matieres = \Modules\Parametrage\Entities\MatiereUnite::all();

            $item = $groupeMatiere->load(['niveau', 'section', 'cycle', 'niveau', 'section', 'cycle', 'matiere1', 'matiere2', 'matiere3', 'matiere4', 'matiere5', 'matiere6', 'matiere7', 'matiere8', 'matiere9', 'matiere10', 'anneeScolaire', 'pays']);

            return Inertia::render('Parametrage::GroupesMatiere/Edit', [
                'item' => $item,
                'niveaux' => $niveaux,
                'sections' => $sections,
                'cycles' => $cycles,
                'matieres' => $matieres,
            ]);
        } catch (\Exception $e) {
            \Log::error('GroupeMatiere Edit Error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    public function update(Request $request, GroupeMatiere $groupeMatiere)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:groupes_matieres,code,' . $groupeMatiere->id,
                'libelle' => 'required|string|max:255',
                'niveau_id' => 'required|exists:niveaux_etudes,id',
                'section_id' => 'required|exists:sections,id',
                'cycle_id' => 'required|exists:cycles_enseignement,id',
                'matiere1_id' => 'nullable|exists:matieres_unites,id',
                'matiere2_id' => 'nullable|exists:matieres_unites,id',
                'matiere3_id' => 'nullable|exists:matieres_unites,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $groupeMatiere->etat;
            $validated['updated_by'] = auth()->id();
            $groupeMatiere->update($validated);

            return redirect()
                ->route('parametrage.groupes_matiere.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('GroupeMatiere Validation Error on Update: ', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return back()->withErrors($e->errors())->withInput()->with([
                'niveaux' => \Modules\Parametrage\Entities\NiveauEtude::all(),
                'sections' => \Modules\Parametrage\Entities\Section::all(),
                'cycles' => \Modules\Parametrage\Entities\CycleEnseignement::all(),
                'matieres' => \Modules\Parametrage\Entities\MatiereUnite::all(),
            ]);
        } catch (\Exception $e) {
            \Log::error('GroupeMatiere Update Error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors de la modification: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(GroupeMatiere $groupeMatiere)
    {
        try {
            $groupeMatiere->deleted_by = auth()->id();
            $groupeMatiere->save();
            $groupeMatiere->delete();

            return redirect()->route('parametrage.groupes_matiere.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            // Logging handled by exception handler
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
            // Logging handled by exception handler
            return redirect()->route('parametrage.groupes_matiere.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
