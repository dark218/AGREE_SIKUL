<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\Section;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;

class NiveauEtudeController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-niveauetude-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-niveauetude-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-niveauetude-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-niveauetude-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-niveauetude-activate', ['only' => ['activate']]);
    }

    /**
     * Charge les lookups nécessaires au formulaire (Cycle + Section uniquement).
     */
    private function lookups(): array
    {
        return [
            'cycles' => CycleEnseignement::orderBy('libelle')->get(['id', 'libelle']),
            'sections' => Section::orderBy('libelle')->get(['id', 'libelle']),
        ];
    }

    public function index(Request $request)
    {
        try {
            $query = NiveauEtude::query()->with(['cycle', 'section']);

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $niveauEtudes = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::NiveauxÉtude/Index', [
                'niveauEtudes' => $niveauEtudes,
                'filters' => $request->only(['code', 'libelle', 'etat']),
            ]);
        } catch (\Exception $e) {
            Log::error('niveauetudecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Parametrage::NiveauxÉtude/Create', $this->lookups());
        } catch (\Exception $e) {
            Log::error('niveauetudecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:niveaux_etudes,code',
            'libelle' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:50',
            'cycle_id' => 'required|exists:cycles_enseignement,id',
            'section_id' => 'nullable|exists:sections,id',
            'etat' => 'nullable|in:actif,inactif',
        ]);

        try {
            $validated['created_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? 'actif';
            NiveauEtude::create($validated);

            return redirect()
                ->route('parametrage.niveaux_etude.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            Log::error('niveauetudecontroller@error: ' . $e->getmessage());
            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    public function show(NiveauEtude $niveauEtude)
    {
        try {
            return Inertia::render('Parametrage::NiveauxÉtude/Show', array_merge($this->lookups(), [
                'niveauEtude' => $niveauEtude,
            ]));
        } catch (\Exception $e) {
            Log::error('niveauetudecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(NiveauEtude $niveauEtude)
    {
        try {
            return Inertia::render('Parametrage::NiveauxÉtude/Edit', array_merge($this->lookups(), [
                'niveauEtude' => $niveauEtude,
            ]));
        } catch (\Exception $e) {
            Log::error('niveauetudecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, NiveauEtude $niveauEtude)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:niveaux_etudes,code,' . $niveauEtude->id,
                'libelle' => 'required|string|max:255',
                'sigle' => 'nullable|string|max:50',
                'cycle_id' => 'required|exists:cycles_enseignement,id',
                'section_id' => 'nullable|exists:sections,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['updated_by'] = auth()->id();
            $validated['etat'] = $validated['etat'] ?? $niveauEtude->etat;
            $niveauEtude->update($validated);

            return redirect()
                ->route('parametrage.niveaux_etude.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            Log::error('niveauetudecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(NiveauEtude $niveauEtude)
    {
        try {
            $niveauEtude->deleted_by = auth()->id();
            $niveauEtude->save();
            $niveauEtude->delete();

            return redirect()->route('parametrage.niveaux_etude.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            Log::error('niveauetudecontroller@error: ' . $e->getmessage());
            return redirect()->route('parametrage.niveaux_etude.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(NiveauEtude $niveauEtude)
    {
        try {
            $newEtat = $niveauEtude->etat === 'actif' ? 'inactif' : 'actif';
            $niveauEtude->etat = $newEtat;
            $niveauEtude->updated_by = auth()->id();
            $niveauEtude->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.niveaux_etude.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            Log::error('niveauetudecontroller@error: ' . $e->getmessage());
            return redirect()->route('parametrage.niveaux_etude.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
