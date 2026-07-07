<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\AffectationEnseignant;
use Modules\Academique\Entities\Enseignant;
use Modules\Parametrage\Entities\{AnneeScolaire, Classe, MatiereUnite, Ecole, Institution, Campus};

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

        if ($ecoleId)  $query->where('ecole_id', $ecoleId);
        if ($anneeId)  $query->where('annee_scolaire_id', $anneeId);
        if ($etat)     $query->where('etat', $etat);

        $affectations = $query
            ->with(['enseignant', 'classe', 'ecole', 'anneeScolaire', 'matieres'])
            ->paginate(10)
            ->appends(request()->query());

        return Inertia::render('Academique::AffectationsEnseignants/Index', [
            'affectations'    => $affectations,
            'ecoles'          => Ecole::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray(),
            'anneesScolaires' => AnneeScolaire::where('etat', 'actif')->select('id', 'libelle')->get()->toArray(),
            'filters' => [
                'enseignant'        => $enseignantSearch,
                'ecole_id'          => $ecoleId,
                'annee_scolaire_id' => $anneeId,
                'etat'              => $etat,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Academique::AffectationsEnseignants/Create', $this->lookups());
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->baseRules());
        $matiereIds = $this->pluckMatiereIds($request);

        $validated['creation_username'] = auth()->user()->nom ?? 'system';
        $affectation = AffectationEnseignant::create($validated);
        $affectation->matieres()->sync($matiereIds);

        return redirect()->route('academique.affectations_enseignants.index')
                       ->with('message', trans('messages.created_successfully'));
    }

    public function show(AffectationEnseignant $affectationEnseignant)
    {
        $affectationEnseignant->load(['enseignant', 'classe', 'ecole', 'anneeScolaire', 'institution', 'campus', 'matieres']);

        return Inertia::render('Academique::AffectationsEnseignants/Show', array_merge(
            $this->lookups(),
            ['affectation' => $affectationEnseignant]
        ));
    }

    public function edit(AffectationEnseignant $affectationEnseignant)
    {
        $affectationEnseignant->load(['enseignant', 'classe', 'ecole', 'anneeScolaire', 'institution', 'campus', 'matieres']);

        return Inertia::render('Academique::AffectationsEnseignants/Edit', array_merge(
            $this->lookups(),
            ['affectation' => $affectationEnseignant]
        ));
    }

    public function update(Request $request, AffectationEnseignant $affectationEnseignant)
    {
        $validated = $request->validate($this->baseRules());
        $matiereIds = $this->pluckMatiereIds($request);

        $validated['modification_username'] = auth()->user()->nom ?? 'system';
        $affectationEnseignant->update($validated);
        $affectationEnseignant->matieres()->sync($matiereIds);

        return redirect()->route('academique.affectations_enseignants.index')
                       ->with('message', trans('messages.updated_successfully'));
    }

    public function destroy(AffectationEnseignant $affectationEnseignant)
    {
        // La pivot affectation_matieres a cascadeOnDelete → nettoyage auto.
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

    /**
     * Règles de validation communes store/update.
     */
    private function baseRules(): array
    {
        return [
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            'enseignant_id'     => 'required|exists:enseignants,id',
            'classe_id'         => 'nullable|exists:classes,id',
            'ecole_id'          => 'nullable|exists:ecoles,id',
            'institution_id'    => 'nullable|exists:institutions,id',
            'campus_id'         => 'nullable|exists:campuses,id',
            'matieres'          => 'nullable|array',
            'matieres.*'        => 'integer|exists:matieres_unites,id',
            'etat'              => 'required|in:actif,inactif',
        ];
    }

    /**
     * Récupère et déduplique la liste des ids de matières à sync.
     */
    private function pluckMatiereIds(Request $request): array
    {
        $ids = $request->input('matieres', []);
        if (!is_array($ids)) return [];
        return array_values(array_unique(array_filter($ids, fn($v) => is_numeric($v))));
    }

    /**
     * Lookups partagés Create/Edit/Show.
     */
    private function lookups(): array
    {
        return [
            'enseignants'     => Enseignant::where('statut', 'actif')->select('id', 'nom', 'prenoms')->get()->toArray(),
            'anneesScolaires' => AnneeScolaire::where('etat', 'actif')->select('id', 'libelle')->get()->toArray(),
            'classes'         => $this->classeLookups(),
            'ecoles'          => Ecole::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray(),
            'institutions'    => Institution::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray(),
            'campuses'        => Campus::where('statut', 'actif')->select('id', 'nom as libelle')->get()->toArray(),
            'matieres'        => MatiereUnite::where('etat', 'actif')->select('id', 'libelle')->orderBy('libelle')->get()->toArray(),
        ];
    }

    private function classeLookups(): array
    {
        return Classe::where('statut', 'actif')
            ->with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
            ->select('id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
            ->get()
            ->map(fn($c) => [
                'id'                     => $c->id,
                'nom'                    => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                'libelle'                => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                'ecole_id'               => $c->ecole_id,     'ecole_nom' => $c->ecole?->nom,
                'campus_id'              => $c->campus_id,    'campus_nom' => $c->campus?->nom,
                'niveau_id'              => $c->niveau_id,    'niveau_libelle' => $c->niveau?->libelle,
                'section_id'             => $c->section_id,   'section_libelle' => $c->section?->libelle,
                'cycle_id'               => $c->cycle_id,     'cycle_libelle' => $c->cycle?->libelle,
                'annee_scolaire_id'      => $c->annee_scolaire_id,
                'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
            ])->toArray();
    }
}
