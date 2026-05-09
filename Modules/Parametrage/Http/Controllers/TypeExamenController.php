<?php

namespace Modules\Parametrage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Parametrage\Entities\TypeExamen;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Section;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;

class TypeExamenController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('permission.check:parametrage-typeexamen-list', ['only' => ['index']]);
        $this->middleware('permission.check:parametrage-typeexamen-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:parametrage-typeexamen-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:parametrage-typeexamen-delete', ['only' => ['destroy']]);
        $this->middleware('permission.check:parametrage-typeexamen-activate', ['only' => ['activate']]);
    }

    public function index(Request $request)
    {
        try {
            $query = TypeExamen::query()
                ->with(['niveau', 'cycle', 'pays'])
                ->orderBy('created_at', 'desc');

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            $typeExamens = $query->paginate(10)->withQueryString();

            return Inertia::render('Parametrage::TypeExamens/Index', [
                'types' => $typeExamens,
                'filters' => $request->only(['code', 'libelle']),
            ]);
        } catch (\Exception $e) {
            Log::error('typeexamencontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function create()
    {
        try {
            $niveaux = NiveauEtude::all()->map(fn($n) => ['id' => $n->id, 'libelle' => $n->libelle])->values();
            $cycles = CycleEnseignement::all()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->values();
            $pays = Pays::all()->map(fn($p) => ['id' => $p->id, 'libelle' => $p->libelle])->values();
            $anneesScolaires = AnneeScolaire::all()->map(fn($a) => ['id' => $a->id, 'libelle' => $a->libelle])->values();
            $sections = Section::all()->map(fn($s) => ['id' => $s->id, 'libelle' => $s->libelle])->values();

            return Inertia::render('Parametrage::TypeExamens/Create', [
                'title' => 'Créer un type d\'examen',
                'niveaux' => $niveaux,
                'cycles' => $cycles,
                'pays' => $pays,
                'anneesScolaires' => $anneesScolaires,
                'sections' => $sections,
            ]);
        } catch (\Exception $e) {
            Log::error('typeexamencontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:type_examens,code',
                'libelle' => 'required|string|max:255',
                'niveau_id' => 'required|exists:niveaux_etudes,id',
                'cycle_id' => 'required|exists:cycles_enseignement,id',
                'pays_id' => 'required|exists:pays,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'section_id' => 'nullable|exists:sections,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? 'actif';
            $validated['created_by'] = auth()->id();
            TypeExamen::create($validated);

            return redirect()
                ->route('parametrage.types_examens.index')
                ->with('success', 'Créé avec succès');
        } catch (\Exception $e) {
            Log::error('typeexamencontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors de la création');
        }
    }

    public function show(TypeExamen $typeExamen)
    {
        try {
            $niveaux = NiveauEtude::all()->map(fn($n) => ['id' => $n->id, 'libelle' => $n->libelle])->values();
            $cycles = CycleEnseignement::all()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->values();
            $pays = Pays::all()->map(fn($p) => ['id' => $p->id, 'libelle' => $p->libelle])->values();
            $anneesScolaires = AnneeScolaire::all()->map(fn($a) => ['id' => $a->id, 'libelle' => $a->libelle])->values();
            $sections = Section::all()->map(fn($s) => ['id' => $s->id, 'libelle' => $s->libelle])->values();

            return Inertia::render('Parametrage::TypeExamens/Show', [
                'title' => 'Détails Type d\'Examen',
                'item' => $typeExamen,
                'niveaux' => $niveaux,
                'cycles' => $cycles,
                'pays' => $pays,
                'anneesScolaires' => $anneesScolaires,
                'sections' => $sections,
            ]);
        } catch (\Exception $e) {
            Log::error('typeexamencontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }

    public function edit(TypeExamen $typeExamen)
    {
        try {
            $niveaux = NiveauEtude::all()->map(fn($n) => ['id' => $n->id, 'libelle' => $n->libelle])->values();
            $cycles = CycleEnseignement::all()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->values();
            $pays = Pays::all()->map(fn($p) => ['id' => $p->id, 'libelle' => $p->libelle])->values();
            $anneesScolaires = AnneeScolaire::all()->map(fn($a) => ['id' => $a->id, 'libelle' => $a->libelle])->values();
            $sections = Section::all()->map(fn($s) => ['id' => $s->id, 'libelle' => $s->libelle])->values();

            return Inertia::render('Parametrage::TypeExamens/Edit', [
                'title' => 'Modifier Type d\'Examen',
                'item' => $typeExamen,
                'niveaux' => $niveaux,
                'cycles' => $cycles,
                'pays' => $pays,
                'anneesScolaires' => $anneesScolaires,
                'sections' => $sections,
            ]);
        } catch (\Exception $e) {
            Log::error('typeexamencontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire');
        }
    }

    public function update(Request $request, TypeExamen $typeExamen)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:type_examens,code,' . $typeExamen->id,
                'libelle' => 'required|string|max:255',
                'niveau_id' => 'required|exists:niveaux_etudes,id',
                'cycle_id' => 'required|exists:cycles_enseignement,id',
                'pays_id' => 'required|exists:pays,id',
                'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
                'section_id' => 'nullable|exists:sections,id',
                'etat' => 'nullable|in:actif,inactif',
            ]);

            $validated['etat'] = $validated['etat'] ?? $typeExamen->etat;
            $validated['updated_by'] = auth()->id();
            $typeExamen->update($validated);

            return redirect()
                ->route('parametrage.types_examens.index')
                ->with('success', 'Modifié avec succès');
        } catch (\Exception $e) {
            Log::error('typeexamencontroller@error: ' . $e->getmessage());
            return back()->with('error', 'Erreur lors de la modification');
        }
    }

    public function destroy(TypeExamen $typeExamen)
    {
        try {
            $typeExamen->deleted_by = auth()->id();
            $typeExamen->save();
            $typeExamen->delete();

            return redirect()->route('parametrage.types_examens.index')->with('success', 'Supprimé avec succès');
        } catch (\Exception $e) {
            Log::error('typeexamencontroller@error: ' . $e->getmessage());
            return redirect()->route('parametrage.types_examens.index')->with('error', 'Erreur lors de la suppression');
        }
    }

    public function activate(TypeExamen $typeExamen)
    {
        try {
            $newEtat = $typeExamen->etat === 'actif' ? 'inactif' : 'actif';
            $typeExamen->etat = $newEtat;
            $typeExamen->updated_by = auth()->id();
            $typeExamen->save();

            $message = $newEtat === 'actif' ? 'Activé' : 'Désactivé';
            return redirect()->route('parametrage.types_examens.index')->with('success', $message . ' avec succès');
        } catch (\Exception $e) {
            Log::error('typeexamencontroller@error: ' . $e->getmessage());
            return redirect()->route('parametrage.types_examens.index')->with('error', 'Erreur lors du changement de statut');
        }
    }
}
