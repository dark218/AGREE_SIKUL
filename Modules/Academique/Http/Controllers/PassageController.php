<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Academique\Entities\Passage;
use Modules\Parametrage\Entities\{Section, CycleEnseignement, Niveau};
use Inertia\Inertia;

class PassageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:passages-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:passages-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:passages-edit', ['only' => ['edit', 'update', 'statut']]);
        $this->middleware('permission.check:passages-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $sectionId = $request->get('section_id');
        $cycleId = $request->get('cycle_enseignement_id');
        $niveauId = $request->get('niveau_id');
        $etat = $request->get('etat');

        $query = Passage::query();

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        if ($cycleId) {
            $query->where('cycle_enseignement_id', $cycleId);
        }

        if ($niveauId) {
            $query->where('niveau_id', $niveauId);
        }

        if ($etat) {
            $query->where('etat', $etat);
        }

        $passages = $query
            ->with(['section', 'cycleEnseignement', 'niveau', 'niveauSuperieur'])
            ->paginate(10)
            ->appends(request()->query());

        $sections = Section::where('etat', 'actif')->select('id', 'libelle')->get();
        $cycles = CycleEnseignement::where('etat', 'actif')->select('id', 'libelle')->get();
        $niveaux = Niveau::where('statut', 'actif')->select('id', 'libelle')->get();

        return Inertia::render('Academique::Passages/Index', [
            'passages' => $passages,
            'sections' => $sections,
            'cycles' => $cycles,
            'niveaux' => $niveaux,
            'filters' => [
                'section_id' => $sectionId,
                'cycle_enseignement_id' => $cycleId,
                'niveau_id' => $niveauId,
                'etat' => $etat,
            ],
        ]);
    }

    public function create()
    {
        $sections = Section::where('etat', 'actif')->select('id', 'libelle')->get();
        $cycles = CycleEnseignement::where('etat', 'actif')->select('id', 'libelle')->get();
        $niveaux = Niveau::where('statut', 'actif')->select('id', 'libelle')->get();

        return Inertia::render('Academique::Passages/Create', [
            'sections' => $sections,
            'cycles' => $cycles,
            'niveaux' => $niveaux,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'nullable|exists:sections,id',
            'cycle_enseignement_id' => 'nullable|exists:cycles_enseignement,id',
            'niveau_id' => 'required|exists:niveaux,id',
            'niveau_superieur_id' => 'nullable|exists:niveaux,id',
            'etat' => 'required|in:actif,inactif',
        ]);

        $validated['creation_username'] = auth()->user()->name ?? 'system';

        Passage::create($validated);

        return redirect()->route('academique.passages.index')
                       ->with('message', trans('messages.created_successfully'));
    }

    public function show(Passage $passage)
    {
        $passage->load(['section', 'cycleEnseignement', 'niveau', 'niveauSuperieur']);

        $sections = Section::where('etat', 'actif')->select('id', 'libelle')->get();
        $cycles = CycleEnseignement::where('etat', 'actif')->select('id', 'libelle')->get();
        $niveaux = Niveau::where('statut', 'actif')->select('id', 'libelle')->get();

        return Inertia::render('Academique::Passages/Show', [
            'passage' => $passage,
            'sections' => $sections,
            'cycles' => $cycles,
            'niveaux' => $niveaux,
        ]);
    }

    public function edit(Passage $passage)
    {
        $passage->load(['section', 'cycleEnseignement', 'niveau', 'niveauSuperieur']);

        $sections = Section::where('etat', 'actif')->select('id', 'libelle')->get();
        $cycles = CycleEnseignement::where('etat', 'actif')->select('id', 'libelle')->get();
        $niveaux = Niveau::where('statut', 'actif')->select('id', 'libelle')->get();

        return Inertia::render('Academique::Passages/Edit', [
            'passage' => $passage,
            'sections' => $sections,
            'cycles' => $cycles,
            'niveaux' => $niveaux,
        ]);
    }

    public function update(Request $request, Passage $passage)
    {
        $validated = $request->validate([
            'section_id' => 'nullable|exists:sections,id',
            'cycle_enseignement_id' => 'nullable|exists:cycles_enseignement,id',
            'niveau_id' => 'required|exists:niveaux,id',
            'niveau_superieur_id' => 'nullable|exists:niveaux,id',
            'etat' => 'required|in:actif,inactif',
        ]);

        $validated['modification_username'] = auth()->user()->name ?? 'system';

        $passage->update($validated);

        return redirect()->route('academique.passages.index')
                       ->with('message', trans('messages.updated_successfully'));
    }

    public function destroy(Passage $passage)
    {
        $passage->delete();

        return redirect()->route('academique.passages.index')
                       ->with('message', trans('messages.deleted_successfully'));
    }

    public function statut(Passage $passage)
    {
        $newEtat = $passage->etat === 'actif' ? 'inactif' : 'actif';
        $passage->update(['etat' => $newEtat]);

        return back()->with('message', trans('messages.status_updated_successfully'));
    }
}
