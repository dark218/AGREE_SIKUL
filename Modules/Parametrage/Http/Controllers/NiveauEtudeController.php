<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Ecole;
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

    public function index(Request $request)
    {
        try {
            $query = NiveauEtude::query()->with(['cycle', 'pays']);

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
            $pays = Pays::all()->map(function($country) {
                return [
                    'id' => $country->id,
                    'libelle' => $country->libelle,
                ];
            });

            $cycles = CycleEnseignement::all()->map(function($cycle) {
                return [
                    'id' => $cycle->id,
                    'libelle' => $cycle->libelle,
                ];
            });

            $anneesScolaires = AnneeScolaire::all()->map(function($annee) {
                return [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ];
            });

            $ecoles = Ecole::orderBy('nom')->get(['id', 'nom as libelle']);
            $sections = Section::orderBy('libelle')->get(['id', 'libelle']);

            return Inertia::render('Parametrage::NiveauxÉtude/Create', [
                'pays' => $pays,
                'cycles' => $cycles,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'sections' => $sections,
            ]);
        } catch (\Exception $e) {
            Log::error('niveauetudecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:niveaux_etudes,code',
            'sigle' => 'nullable|string|max:50',
            'libelle' => 'required|string|max:255',
            'ecole_id' => 'nullable|exists:ecoles,id',
            'section_id' => 'nullable|exists:sections,id',
            'cycle_id' => 'required|exists:cycles_enseignement,id',
            'pays_id' => 'required|exists:pays,id',
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
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
            $pays = Pays::all()->map(function($country) {
                return [
                    'id' => $country->id,
                    'libelle' => $country->libelle,
                ];
            });

            $cycles = CycleEnseignement::all()->map(function($cycle) {
                return [
                    'id' => $cycle->id,
                    'libelle' => $cycle->libelle,
                ];
            });

            $anneesScolaires = AnneeScolaire::all()->map(function($annee) {
                return [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ];
            });

            $ecoles = Ecole::orderBy('nom')->get(['id', 'nom as libelle']);
            $sections = Section::orderBy('libelle')->get(['id', 'libelle']);

            return Inertia::render('Parametrage::NiveauxÉtude/Show', [
                'niveauEtude' => $niveauEtude,
                'pays' => $pays,
                'cycles' => $cycles,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'sections' => $sections,
            ]);
        } catch (\Exception $e) {
            Log::error('niveauetudecontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(NiveauEtude $niveauEtude)
    {
        try {
            $pays = Pays::all()->map(function($country) {
                return [
                    'id' => $country->id,
                    'libelle' => $country->libelle,
                ];
            });

            $cycles = CycleEnseignement::all()->map(function($cycle) {
                return [
                    'id' => $cycle->id,
                    'libelle' => $cycle->libelle,
                ];
            });

            $anneesScolaires = AnneeScolaire::all()->map(function($annee) {
                return [
                    'id' => $annee->id,
                    'libelle' => $annee->libelle,
                ];
            });

            $ecoles = Ecole::orderBy('nom')->get(['id', 'nom as libelle']);
            $sections = Section::orderBy('libelle')->get(['id', 'libelle']);

            return Inertia::render('Parametrage::NiveauxÉtude/Edit', [
                'niveauEtude' => $niveauEtude,
                'pays' => $pays,
                'cycles' => $cycles,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'sections' => $sections,
            ]);
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
                'sigle' => 'nullable|string|max:50',
                'libelle' => 'required|string|max:255',
                'ecole_id' => 'nullable|exists:ecoles,id',
                'section_id' => 'nullable|exists:sections,id',
                'cycle_id' => 'required|exists:cycles_enseignement,id',
                'pays_id' => 'required|exists:pays,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
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
