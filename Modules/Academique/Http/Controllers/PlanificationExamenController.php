<?php

namespace Modules\Academique\Http\Controllers;

use Modules\Academique\Entities\PlanificationExamen;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Academique\Entities\Enseignant;
use Modules\Parametrage\Entities\NatureExamen;
use Modules\Parametrage\Entities\TypeExamen;
use Modules\Parametrage\Entities\Classe;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanificationExamenController extends Controller
{
    public function index(Request $request)
    {
        $query = PlanificationExamen::query();

        if ($request->filled('nature_examen_id')) {
            $query->where('nature_examen_id', $request->nature_examen_id);
        }
        if ($request->filled('type_examen_id')) {
            $query->where('type_examen_id', $request->type_examen_id);
        }
        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }
        if ($request->filled('matiere_id')) {
            $query->where('matiere_id', $request->matiere_id);
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        $planifications = $query->with(['natureExamen', 'typeExamen', 'classe', 'matiere'])
            ->paginate(10)
            ->through(function ($item) {
                return [
                    'id' => $item->id,
                    'nature' => $item->natureExamen?->libelle,
                    'type' => $item->typeExamen?->libelle,
                    'classe' => $item->classe?->nom,
                    'matiere' => $item->matiere?->libelle,
                    'jour' => $item->jour,
                    'date' => $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : null,
                    'heure_debut' => $item->heure_debut ? \Carbon\Carbon::parse($item->heure_debut)->format('H:i') : null,
                    'heure_fin' => $item->heure_fin ? \Carbon\Carbon::parse($item->heure_fin)->format('H:i') : null,
                    'duree' => $item->duree,
                    'etat' => $item->etat,
                ];
            });

        $natures = NatureExamen::where('etat', 'actif')->get(['id', 'libelle']);
        $types = TypeExamen::where('etat', 'actif')->get(['id', 'libelle']);
        $classes = Classe::where('statut', 'actif')
            ->with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
            ->select('id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                'ecole_id' => $c->ecole_id, 'ecole_nom' => $c->ecole?->nom,
                'campus_id' => $c->campus_id, 'campus_nom' => $c->campus?->nom,
                'niveau_id' => $c->niveau_id, 'niveau_libelle' => $c->niveau?->libelle,
                'section_id' => $c->section_id, 'section_libelle' => $c->section?->libelle,
                'cycle_id' => $c->cycle_id, 'cycle_libelle' => $c->cycle?->libelle,
                'annee_scolaire_id' => $c->annee_scolaire_id, 'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
            ]);
        $matieres = MatiereUnite::where('statut', 'actif')->get(['id', 'libelle']);

        return Inertia::render('Academique::PlanificationExamens/Index', [
            'planifications' => $planifications,
            'natures' => $natures,
            'types' => $types,
            'classes' => $classes,
            'matieres' => $matieres,
            'filters' => $request->only(['nature_examen_id', 'type_examen_id', 'classe_id', 'matiere_id', 'etat'])
        ]);
    }

    public function create()
    {
        $natures = NatureExamen::where('etat', 'actif')->get(['id', 'libelle']);
        $types = TypeExamen::where('etat', 'actif')->get(['id', 'libelle']);
        $classes = Classe::where('statut', 'actif')
            ->with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
            ->select('id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                'ecole_id' => $c->ecole_id, 'ecole_nom' => $c->ecole?->nom,
                'campus_id' => $c->campus_id, 'campus_nom' => $c->campus?->nom,
                'niveau_id' => $c->niveau_id, 'niveau_libelle' => $c->niveau?->libelle,
                'section_id' => $c->section_id, 'section_libelle' => $c->section?->libelle,
                'cycle_id' => $c->cycle_id, 'cycle_libelle' => $c->cycle?->libelle,
                'annee_scolaire_id' => $c->annee_scolaire_id, 'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
            ]);
        $matieres = MatiereUnite::where('statut', 'actif')->get(['id', 'libelle']);
        $enseignants = Enseignant::whereNotNull('id')->get(['id', 'prenoms', 'nom']);

        return Inertia::render('Academique::PlanificationExamens/Create', [
            'natures' => $natures,
            'types' => $types,
            'classes' => $classes,
            'matieres' => $matieres,
            'enseignants' => $enseignants,
            'title' => 'Créer une planification'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nature_examen_id' => 'nullable|exists:natures_examens,id',
            'type_examen_id' => 'nullable|exists:type_examens,id',
            'classe_id' => 'nullable|exists:classes,id',
            'matiere_id' => 'nullable|exists:matieres_unites,id',
            'enseignant_id' => 'nullable|exists:enseignants,id',
            'jour' => 'nullable|string',
            'date' => 'nullable|date',
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i',
            'duree' => 'nullable|numeric',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['creation_username'] = auth()->user()->name;

        PlanificationExamen::create($validated);

        return redirect()->route('academique.planification-examens.index')
            ->with('success', 'Planification créée avec succès');
    }

    public function show(PlanificationExamen $planificationExamen)
    {
        $planificationExamen->load(['natureExamen', 'typeExamen', 'classe', 'matiere']);

        // Format dates and times
        $item = $planificationExamen->toArray();
        $item['date'] = $planificationExamen->date ? \Carbon\Carbon::parse($planificationExamen->date)->format('Y-m-d') : null;
        $item['heure_debut'] = $planificationExamen->heure_debut ? \Carbon\Carbon::parse($planificationExamen->heure_debut)->format('H:i') : null;
        $item['heure_fin'] = $planificationExamen->heure_fin ? \Carbon\Carbon::parse($planificationExamen->heure_fin)->format('H:i') : null;

        $natures = NatureExamen::where('etat', 'actif')->get(['id', 'libelle']);
        $types = TypeExamen::where('etat', 'actif')->get(['id', 'libelle']);
        $classes = Classe::where('statut', 'actif')
            ->with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
            ->select('id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                'ecole_id' => $c->ecole_id, 'ecole_nom' => $c->ecole?->nom,
                'campus_id' => $c->campus_id, 'campus_nom' => $c->campus?->nom,
                'niveau_id' => $c->niveau_id, 'niveau_libelle' => $c->niveau?->libelle,
                'section_id' => $c->section_id, 'section_libelle' => $c->section?->libelle,
                'cycle_id' => $c->cycle_id, 'cycle_libelle' => $c->cycle?->libelle,
                'annee_scolaire_id' => $c->annee_scolaire_id, 'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
            ]);
        $matieres = MatiereUnite::where('statut', 'actif')->get(['id', 'libelle']);
        $enseignants = Enseignant::whereNotNull('id')->get(['id', 'prenoms', 'nom']);

        return Inertia::render('Academique::PlanificationExamens/Show', [
            'item' => array_merge($item, [
                'natureExamen' => $planificationExamen->natureExamen,
                'typeExamen' => $planificationExamen->typeExamen,
                'classe' => $planificationExamen->classe,
                'matiere' => $planificationExamen->matiere,
            ]),
            'natures' => $natures,
            'types' => $types,
            'classes' => $classes,
            'matieres' => $matieres,
            'enseignants' => $enseignants,
            'title' => 'Voir la planification'
        ]);
    }

    public function edit(PlanificationExamen $planificationExamen)
    {
        $natures = NatureExamen::where('etat', 'actif')->get(['id', 'libelle']);
        $types = TypeExamen::where('etat', 'actif')->get(['id', 'libelle']);
        $classes = Classe::where('statut', 'actif')
            ->with(['ecole:id,nom', 'campus:id,nom', 'niveau:id,libelle', 'section:id,libelle', 'cycle:id,libelle', 'anneeScolaire:id,libelle'])
            ->select('id', 'nom', 'libelle', 'libelle_affichage', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'annee_scolaire_id')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                'libelle' => $c->libelle_affichage ?: ($c->libelle ?: $c->nom),
                'ecole_id' => $c->ecole_id, 'ecole_nom' => $c->ecole?->nom,
                'campus_id' => $c->campus_id, 'campus_nom' => $c->campus?->nom,
                'niveau_id' => $c->niveau_id, 'niveau_libelle' => $c->niveau?->libelle,
                'section_id' => $c->section_id, 'section_libelle' => $c->section?->libelle,
                'cycle_id' => $c->cycle_id, 'cycle_libelle' => $c->cycle?->libelle,
                'annee_scolaire_id' => $c->annee_scolaire_id, 'annee_scolaire_libelle' => $c->anneeScolaire?->libelle,
            ]);
        $matieres = MatiereUnite::where('statut', 'actif')->get(['id', 'libelle']);

        // Format dates and times for form input
        $item = $planificationExamen->toArray();
        $item['date'] = $planificationExamen->date ? \Carbon\Carbon::parse($planificationExamen->date)->format('Y-m-d') : null;
        $item['heure_debut'] = $planificationExamen->heure_debut ? \Carbon\Carbon::parse($planificationExamen->heure_debut)->format('H:i') : null;
        $item['heure_fin'] = $planificationExamen->heure_fin ? \Carbon\Carbon::parse($planificationExamen->heure_fin)->format('H:i') : null;

        $enseignants = Enseignant::whereNotNull('id')->get(['id', 'prenoms', 'nom']);

        return Inertia::render('Academique::PlanificationExamens/Edit', [
            'item' => $item,
            'natures' => $natures,
            'types' => $types,
            'classes' => $classes,
            'matieres' => $matieres,
            'enseignants' => $enseignants,
            'title' => 'Modifier la planification'
        ]);
    }

    public function update(Request $request, PlanificationExamen $planificationExamen)
    {
        $validated = $request->validate([
            'nature_examen_id' => 'nullable|exists:natures_examens,id',
            'type_examen_id' => 'nullable|exists:type_examens,id',
            'classe_id' => 'nullable|exists:classes,id',
            'matiere_id' => 'nullable|exists:matieres_unites,id',
            'enseignant_id' => 'nullable|exists:enseignants,id',
            'jour' => 'nullable|string',
            'date' => 'nullable|date',
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i',
            'duree' => 'nullable|numeric',
            'etat' => 'required|in:actif,inactif'
        ]);

        $validated['modification_username'] = auth()->user()->name;

        $planificationExamen->update($validated);

        return redirect()->route('academique.planification-examens.index')
            ->with('success', 'Planification modifiée avec succès');
    }

    public function destroy(PlanificationExamen $planificationExamen)
    {
        $planificationExamen->delete();

        return back()->with('success', 'Planification supprimée avec succès');
    }

    public function statut(PlanificationExamen $planificationExamen)
    {
        $planificationExamen->etat = $planificationExamen->etat === 'actif' ? 'inactif' : 'actif';
        $planificationExamen->modification_username = auth()->user()->name;
        $planificationExamen->save();

        return back()->with('success', 'Statut mis à jour');
    }
}
