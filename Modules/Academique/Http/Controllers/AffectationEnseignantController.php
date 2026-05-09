<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Academique\Entities\AffectationEnseignant;
use Modules\Academique\Entities\Enseignant;
use Modules\Parametrage\Entities\{AnneeScolaire, Classe, Matiere, Ecole, Institution, Campus};
use Inertia\Inertia;

class AffectationEnseignantController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:affectations_enseignants-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:affectations_enseignants-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:affectations_enseignants-edit', ['only' => ['edit', 'update', 'statut']]);
        $this->middleware('permission.check:affectations_enseignants-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $enseignantSearch = $request->get('enseignant');
        $ecoleId = $request->get('ecole_id');
        $anneeId = $request->get('annee_scolaire_id');
        $etat = $request->get('etat');

        $query = AffectationEnseignant::query();

        if ($enseignantSearch) {
            $query->whereHas('enseignant', function ($q) use ($enseignantSearch) {
                $q->where('nom', 'like', '%' . $enseignantSearch . '%')
                  ->orWhere('prenoms', 'like', '%' . $enseignantSearch . '%');
            });
        }

        if ($ecoleId) {
            $query->where('ecole_id', $ecoleId);
        }

        if ($anneeId) {
            $query->where('annee_scolaire_id', $anneeId);
        }

        if ($etat) {
            $query->where('etat', $etat);
        }

        $affectations = $query
            ->with(['enseignant', 'classe', 'ecole', 'anneeScolaire'])
            ->paginate(10)
            ->appends(request()->query());

        $ecoles = Ecole::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray();
        $anneesScolaires = AnneeScolaire::where('etat', 'actif')->select('id', 'libelle')->get()->toArray();

        return Inertia::render('Academique::AffectationsEnseignants/Index', [
            'affectations' => $affectations,
            'ecoles' => $ecoles,
            'anneesScolaires' => $anneesScolaires,
            'filters' => [
                'enseignant' => $enseignantSearch,
                'ecole_id' => $ecoleId,
                'annee_scolaire_id' => $anneeId,
                'etat' => $etat,
            ],
        ]);
    }

    public function create()
    {
        $enseignants = Enseignant::where('statut', 'actif')->select('id', 'nom', 'prenoms')->get()->toArray();
        $anneesScolaires = AnneeScolaire::where('etat', 'actif')->select('id', 'libelle')->get()->toArray();
        $classes = Classe::where('statut', 'actif')->select('id', 'nom')->get()->toArray();
        $ecoles = Ecole::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray();
        $institutions = Institution::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray();
        $campuses = Campus::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray();
        $matieres = Matiere::where('statut', 'actif')->select('id', 'libelle')->get()->toArray();

        return Inertia::render('Academique::AffectationsEnseignants/Create', [
            'enseignants' => $enseignants,
            'anneesScolaires' => $anneesScolaires,
            'classes' => $classes,
            'ecoles' => $ecoles,
            'institutions' => $institutions,
            'campuses' => $campuses,
            'matieres' => $matieres,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            'enseignant_id' => 'required|exists:enseignants,id',
            'classe_id' => 'nullable|exists:classes,id',
            'ecole_id' => 'nullable|exists:ecoles,id',
            'institution_id' => 'nullable|exists:institutions,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'matiere_1_id' => 'nullable|exists:matieres,id',
            'matiere_2_id' => 'nullable|exists:matieres,id',
            'matiere_3_id' => 'nullable|exists:matieres,id',
            'matiere_4_id' => 'nullable|exists:matieres,id',
            'matiere_5_id' => 'nullable|exists:matieres,id',
            'matiere_6_id' => 'nullable|exists:matieres,id',
            'matiere_7_id' => 'nullable|exists:matieres,id',
            'matiere_8_id' => 'nullable|exists:matieres,id',
            'matiere_9_id' => 'nullable|exists:matieres,id',
            'matiere_10_id' => 'nullable|exists:matieres,id',
            'matiere_11_id' => 'nullable|exists:matieres,id',
            'matiere_12_id' => 'nullable|exists:matieres,id',
            'matiere_13_id' => 'nullable|exists:matieres,id',
            'matiere_14_id' => 'nullable|exists:matieres,id',
            'matiere_15_id' => 'nullable|exists:matieres,id',
            'matiere_16_id' => 'nullable|exists:matieres,id',
            'matiere_17_id' => 'nullable|exists:matieres,id',
            'matiere_18_id' => 'nullable|exists:matieres,id',
            'matiere_19_id' => 'nullable|exists:matieres,id',
            'matiere_20_id' => 'nullable|exists:matieres,id',
            'matiere_21_id' => 'nullable|exists:matieres,id',
            'etat' => 'required|in:actif,inactif',
        ]);

        $validated['creation_username'] = auth()->user()->name ?? 'system';

        AffectationEnseignant::create($validated);

        return redirect()->route('academique.affectations_enseignants.index')
                       ->with('message', trans('messages.created_successfully'));
    }

    public function show(AffectationEnseignant $affectationEnseignant)
    {
        $affectationEnseignant->load(['enseignant', 'classe', 'ecole', 'anneeScolaire', 'institution', 'campus']);

        $enseignants = Enseignant::where('statut', 'actif')->select('id', 'nom', 'prenoms')->get()->toArray();
        $anneesScolaires = AnneeScolaire::where('etat', 'actif')->select('id', 'libelle')->get()->toArray();
        $classes = Classe::where('statut', 'actif')->select('id', 'nom')->get()->toArray();
        $ecoles = Ecole::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray();
        $institutions = Institution::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray();
        $campuses = Campus::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray();
        $matieres = Matiere::where('statut', 'actif')->select('id', 'libelle')->get()->toArray();

        return Inertia::render('Academique::AffectationsEnseignants/Show', [
            'affectation' => $affectationEnseignant,
            'enseignants' => $enseignants,
            'anneesScolaires' => $anneesScolaires,
            'classes' => $classes,
            'ecoles' => $ecoles,
            'institutions' => $institutions,
            'campuses' => $campuses,
            'matieres' => $matieres,
        ]);
    }

    public function edit(AffectationEnseignant $affectationEnseignant)
    {
        $affectationEnseignant->load(['enseignant', 'classe', 'ecole', 'anneeScolaire', 'institution', 'campus']);

        $enseignants = Enseignant::where('statut', 'actif')->select('id', 'nom', 'prenoms')->get()->toArray();
        $anneesScolaires = AnneeScolaire::where('etat', 'actif')->select('id', 'libelle')->get()->toArray();
        $classes = Classe::where('statut', 'actif')->select('id', 'nom')->get()->toArray();
        $ecoles = Ecole::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray();
        $institutions = Institution::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray();
        $campuses = Campus::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray();
        $matieres = Matiere::where('statut', 'actif')->select('id', 'libelle')->get()->toArray();

        return Inertia::render('Academique::AffectationsEnseignants/Edit', [
            'affectation' => $affectationEnseignant,
            'enseignants' => $enseignants,
            'anneesScolaires' => $anneesScolaires,
            'classes' => $classes,
            'ecoles' => $ecoles,
            'institutions' => $institutions,
            'campuses' => $campuses,
            'matieres' => $matieres,
        ]);
    }

    public function update(Request $request, AffectationEnseignant $affectationEnseignant)
    {
        $validated = $request->validate([
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            'enseignant_id' => 'required|exists:enseignants,id',
            'classe_id' => 'nullable|exists:classes,id',
            'ecole_id' => 'nullable|exists:ecoles,id',
            'institution_id' => 'nullable|exists:institutions,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'matiere_1_id' => 'nullable|exists:matieres,id',
            'matiere_2_id' => 'nullable|exists:matieres,id',
            'matiere_3_id' => 'nullable|exists:matieres,id',
            'matiere_4_id' => 'nullable|exists:matieres,id',
            'matiere_5_id' => 'nullable|exists:matieres,id',
            'matiere_6_id' => 'nullable|exists:matieres,id',
            'matiere_7_id' => 'nullable|exists:matieres,id',
            'matiere_8_id' => 'nullable|exists:matieres,id',
            'matiere_9_id' => 'nullable|exists:matieres,id',
            'matiere_10_id' => 'nullable|exists:matieres,id',
            'matiere_11_id' => 'nullable|exists:matieres,id',
            'matiere_12_id' => 'nullable|exists:matieres,id',
            'matiere_13_id' => 'nullable|exists:matieres,id',
            'matiere_14_id' => 'nullable|exists:matieres,id',
            'matiere_15_id' => 'nullable|exists:matieres,id',
            'matiere_16_id' => 'nullable|exists:matieres,id',
            'matiere_17_id' => 'nullable|exists:matieres,id',
            'matiere_18_id' => 'nullable|exists:matieres,id',
            'matiere_19_id' => 'nullable|exists:matieres,id',
            'matiere_20_id' => 'nullable|exists:matieres,id',
            'matiere_21_id' => 'nullable|exists:matieres,id',
            'etat' => 'required|in:actif,inactif',
        ]);

        $validated['modification_username'] = auth()->user()->name ?? 'system';

        $affectationEnseignant->update($validated);

        return redirect()->route('academique.affectations_enseignants.index')
                       ->with('message', trans('messages.updated_successfully'));
    }

    public function destroy(AffectationEnseignant $affectationEnseignant)
    {
        $affectationEnseignant->delete();

        return redirect()->route('academique.affectations_enseignants.index')
                       ->with('message', trans('messages.deleted_successfully'));
    }

    public function statut(AffectationEnseignant $affectationEnseignant)
    {
        $newEtat = $affectationEnseignant->etat === 'actif' ? 'inactif' : 'actif';
        $affectationEnseignant->update(['etat' => $newEtat]);

        return back()->with('message', trans('messages.status_updated_successfully'));
    }
}
