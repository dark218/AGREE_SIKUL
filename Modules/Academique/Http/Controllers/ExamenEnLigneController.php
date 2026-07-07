<?php

namespace Modules\Academique\Http\Controllers;

use Modules\Academique\Entities\ExamenEnLigne;
use Modules\Academique\Entities\TentativeExamen;
use Modules\Academique\Entities\Apprenant;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Academique\Entities\Enseignant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ExamenEnLigneController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamenEnLigne::query();

        if ($request->filled('titre')) {
            $query->where('titre', 'like', '%' . $request->titre . '%');
        }
        if ($request->filled('matiere_id')) {
            $query->where('matiere_id', $request->matiere_id);
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        $examens = $query->with(['matiere', 'classe', 'enseignant'])
            ->withCount('questions')
            ->paginate(10)
            ->through(function ($item) {
                return [
                    'id' => $item->id,
                    'titre' => $item->titre,
                    'matiere' => $item->matiere?->libelle,
                    'classe' => $item->classe?->nom,
                    'enseignant' => $item->enseignant ? $item->enseignant->prenoms . ' ' . $item->enseignant->nom : '-',
                    'date_debut' => $item->date_debut?->format('Y-m-d H:i'),
                    'date_fin' => $item->date_fin?->format('Y-m-d H:i'),
                    'duree_minutes' => $item->duree_minutes,
                    'nombre_questions' => $item->questions_count,
                    'note_maximum' => $item->note_maximum,
                    'etat' => $item->etat,
                ];
            });

        $matieres = MatiereUnite::where('statut', 'actif')->get(['id', 'libelle']);

        return Inertia::render('Academique::ExamensEnLigne/Index', [
            'examens' => $examens,
            'matieres' => $matieres,
            'filters' => $request->only(['titre', 'matiere_id', 'etat']),
            'statuts' => ExamenEnLigne::STATUTS,
        ]);
    }

    public function create()
    {
        $matieres = MatiereUnite::where('statut', 'actif')->get(['id', 'libelle']);
        $classes = \Modules\Parametrage\Entities\Classe::where('statut', 'actif')
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
        $enseignants = Enseignant::whereNotNull('id')->get(['id', 'prenoms', 'nom']);

        return Inertia::render('Academique::ExamensEnLigne/Create', [
            'matieres' => $matieres,
            'classes' => $classes,
            'enseignants' => $enseignants,
            'statuts' => ExamenEnLigne::STATUTS,
            'title' => 'Créer un examen en ligne'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'classe_id' => 'nullable|exists:classes,id',
            'matiere_id' => 'nullable|exists:matieres_unites,id',
            'enseignant_id' => 'nullable|exists:enseignants,id',
            'planification_examen_id' => 'nullable|exists:planification_examens,id',
            'date_debut' => 'nullable|date_format:Y-m-d\TH:i',
            'date_fin' => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:date_debut',
            'nombre_heures' => 'nullable|numeric|min:0',
            'nombre_questions' => 'nullable|integer|min:0',
            'duree_minutes' => 'nullable|numeric|min:1',
            'note_maximum' => 'nullable|numeric|min:0',
            'note_minimum_passage' => 'nullable|numeric|min:0',
            'melange_questions' => 'boolean',
            'melange_reponses' => 'boolean',
            'nombre_tentatives' => 'nullable|integer|min:1|max:10',
            'afficher_resultat' => 'boolean',
            'afficher_correction' => 'boolean',
            'retour_arriere' => 'boolean',
            'mot_de_passe' => 'nullable|string|max:50',
            'etat' => 'required|in:' . implode(',', ExamenEnLigne::STATUTS),
        ]);

        $validated['creation_username'] = auth()->user()->name;

        $examen = ExamenEnLigne::create($validated);

        return redirect()->route('academique.examens-en-ligne.edit', $examen->id)
            ->with('success', 'Examen créé avec succès. Vous pouvez maintenant ajouter des questions.');
    }

    public function show(ExamenEnLigne $examenEnLigne)
    {
        $examenEnLigne->load(['matiere', 'classe', 'enseignant', 'planificationExamen', 'questions.reponses']);

        $matieres = MatiereUnite::where('statut', 'actif')->get(['id', 'libelle']);
        $classes = \Modules\Parametrage\Entities\Classe::where('statut', 'actif')
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
        $enseignants = Enseignant::whereNotNull('id')->get(['id', 'prenoms', 'nom']);

        return Inertia::render('Academique::ExamensEnLigne/Show', [
            'item' => $examenEnLigne,
            'matieres' => $matieres,
            'classes' => $classes,
            'enseignants' => $enseignants,
            'statuts' => ExamenEnLigne::STATUTS,
            'title' => 'Voir l\'examen en ligne'
        ]);
    }

    public function edit(ExamenEnLigne $examenEnLigne)
    {
        $examenEnLigne->load(['questions.reponses']);

        $matieres = MatiereUnite::where('statut', 'actif')->get(['id', 'libelle']);
        $classes = \Modules\Parametrage\Entities\Classe::where('statut', 'actif')
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
        $enseignants = Enseignant::whereNotNull('id')->get(['id', 'prenoms', 'nom']);

        return Inertia::render('Academique::ExamensEnLigne/Edit', [
            'item' => $examenEnLigne,
            'matieres' => $matieres,
            'classes' => $classes,
            'enseignants' => $enseignants,
            'statuts' => ExamenEnLigne::STATUTS,
            'title' => 'Modifier l\'examen en ligne'
        ]);
    }

    public function update(Request $request, ExamenEnLigne $examenEnLigne)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'classe_id' => 'nullable|exists:classes,id',
            'matiere_id' => 'nullable|exists:matieres_unites,id',
            'enseignant_id' => 'nullable|exists:enseignants,id',
            'planification_examen_id' => 'nullable|exists:planification_examens,id',
            'date_debut' => 'nullable|date_format:Y-m-d\TH:i',
            'date_fin' => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:date_debut',
            'nombre_heures' => 'nullable|numeric|min:0',
            'nombre_questions' => 'nullable|integer|min:0',
            'duree_minutes' => 'nullable|numeric|min:1',
            'note_maximum' => 'nullable|numeric|min:0',
            'note_minimum_passage' => 'nullable|numeric|min:0',
            'melange_questions' => 'boolean',
            'melange_reponses' => 'boolean',
            'nombre_tentatives' => 'nullable|integer|min:1|max:10',
            'afficher_resultat' => 'boolean',
            'afficher_correction' => 'boolean',
            'retour_arriere' => 'boolean',
            'mot_de_passe' => 'nullable|string|max:50',
            'etat' => 'required|in:' . implode(',', ExamenEnLigne::STATUTS),
        ]);

        $validated['modification_username'] = auth()->user()->name;

        $examenEnLigne->update($validated);

        return redirect()->route('academique.examens-en-ligne.index')
            ->with('success', 'Examen en ligne modifié avec succès');
    }

    public function destroy(ExamenEnLigne $examenEnLigne)
    {
        $examenEnLigne->delete();

        return back()->with('success', 'Examen en ligne supprimé avec succès');
    }

    public function statut(ExamenEnLigne $examenEnLigne, Request $request)
    {
        // Cycle de vie : brouillon → publie → en_cours → termine → corrige
        $transitions = [
            'brouillon' => 'publie',
            'publie' => 'en_cours',
            'en_cours' => 'termine',
            'termine' => 'corrige',
        ];

        $newStatut = $request->input('etat', $transitions[$examenEnLigne->etat] ?? $examenEnLigne->etat);

        // Validation : ne peut publier sans questions
        if ($newStatut === 'publie' && $examenEnLigne->questions()->actif()->count() === 0) {
            return back()->with('error', 'Impossible de publier un examen sans questions.');
        }

        $examenEnLigne->etat = $newStatut;
        $examenEnLigne->modification_username = auth()->user()->name;
        $examenEnLigne->save();

        // Quand on passe en "terminé" → attribuer 0 aux absents
        if ($newStatut === 'termine') {
            $nbAbsents = $this->attribuerZeroAuxAbsents($examenEnLigne);
            if ($nbAbsents > 0) {
                return back()->with('success', "Statut mis à jour vers : terminé. $nbAbsents apprenant(s) absent(s) ont reçu la note de 0.");
            }
        }

        return back()->with('success', 'Statut mis à jour vers : ' . $newStatut);
    }

    /**
     * Attribuer la note 0 à tous les apprenants de la classe qui n'ont pas composé
     */
    private function attribuerZeroAuxAbsents(ExamenEnLigne $examen): int
    {
        if (!$examen->classe_id) return 0;

        // Tous les apprenants de la classe
        $apprenants = Apprenant::where('classe_id', $examen->classe_id)
            ->whereNotNull('user_id')
            ->pluck('id');

        // Ceux qui ont déjà composé
        $dejàComposé = TentativeExamen::where('examen_en_ligne_id', $examen->id)
            ->pluck('apprenant_id');

        // Les absents = ceux qui n'ont pas composé
        $absents = $apprenants->diff($dejàComposé);

        $count = 0;
        foreach ($absents as $apprenantId) {
            TentativeExamen::create([
                'examen_en_ligne_id' => $examen->id,
                'apprenant_id' => $apprenantId,
                'numero_tentative' => 1,
                'debut_composition' => $examen->date_fin ?? now(),
                'fin_composition' => $examen->date_fin ?? now(),
                'temps_passe_minutes' => 0,
                'score' => 0,
                'pourcentage' => 0,
                'questions_repondues' => 0,
                'reponses_correctes' => 0,
                'statut' => 'corrigee',
                'nb_incidents_total' => 0,
                'suspicion_triche' => false,
                'creation_username' => 'SYSTEME - Absent',
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Page des résultats d'un examen (côté admin/enseignant)
     */
    public function resultats(ExamenEnLigne $examenEnLigne)
    {
        // Auto-clôturer si la date de fin est passée et l'examen est encore en cours
        if (in_array($examenEnLigne->etat, ['publie', 'en_cours']) && $examenEnLigne->date_fin && $examenEnLigne->date_fin->isPast()) {
            $examenEnLigne->update(['etat' => 'termine']);
            $this->attribuerZeroAuxAbsents($examenEnLigne);
        }

        $examenEnLigne->load(['matiere', 'classe', 'enseignant']);

        $tentatives = TentativeExamen::where('examen_en_ligne_id', $examenEnLigne->id)
            ->with(['apprenant', 'logsSurveillance'])
            ->whereIn('statut', ['corrigee', 'soumise'])
            ->orderBy('score', 'desc')
            ->get()
            ->map(function ($t) use ($examenEnLigne) {
                $noteMin = $examenEnLigne->note_minimum_passage ?? ($examenEnLigne->note_maximum / 2);
                return [
                    'id' => $t->id,
                    'apprenant_nom' => $t->apprenant ? $t->apprenant->prenoms . ' ' . $t->apprenant->nom : '-',
                    'apprenant_matricule' => $t->apprenant?->matricule,
                    'numero_tentative' => $t->numero_tentative,
                    'score' => $t->score,
                    'pourcentage' => $t->pourcentage,
                    'questions_repondues' => $t->questions_repondues,
                    'reponses_correctes' => $t->reponses_correctes,
                    'temps_passe_minutes' => $t->temps_passe_minutes,
                    'debut_composition' => $t->debut_composition?->format('d/m/Y H:i'),
                    'fin_composition' => $t->fin_composition?->format('d/m/Y H:i'),
                    'reussi' => $t->score >= $noteMin,
                    'absent' => $t->creation_username === 'SYSTEME - Absent',
                    'statut' => $t->statut,
                    // Surveillance
                    'nb_changements_onglet' => $t->nb_changements_onglet,
                    'nb_tentatives_copie' => $t->nb_tentatives_copie,
                    'nb_sorties_fullscreen' => $t->nb_sorties_fullscreen,
                    'nb_incidents_total' => $t->nb_incidents_total,
                    'suspicion_triche' => $t->suspicion_triche,
                    'ip_address' => $t->ip_address,
                    'logs_surveillance' => $t->logsSurveillance->map(function ($log) {
                        return [
                            'type' => $log->type_incident,
                            'details' => $log->details,
                            'horodatage' => $log->horodatage?->format('H:i:s'),
                        ];
                    }),
                ];
            });

        // Statistiques
        $nbTentatives = $tentatives->count();
        $nbReussis = $tentatives->where('reussi', true)->count();
        $moyenneClasse = $nbTentatives > 0 ? round($tentatives->avg('score'), 2) : 0;
        $noteMax = $tentatives->max('score') ?? 0;
        $noteMinObtenue = $nbTentatives > 0 ? $tentatives->min('score') : 0;
        $nbSuspicions = $tentatives->where('suspicion_triche', true)->count();

        return Inertia::render('Academique::ExamensEnLigne/Resultats', [
            'examen' => [
                'id' => $examenEnLigne->id,
                'titre' => $examenEnLigne->titre,
                'matiere' => $examenEnLigne->matiere?->libelle,
                'classe' => $examenEnLigne->classe?->nom,
                'enseignant' => $examenEnLigne->enseignant ? $examenEnLigne->enseignant->prenoms . ' ' . $examenEnLigne->enseignant->nom : null,
                'note_maximum' => $examenEnLigne->note_maximum,
                'note_minimum_passage' => $examenEnLigne->note_minimum_passage ?? ($examenEnLigne->note_maximum / 2),
                'nombre_questions' => $examenEnLigne->questions()->actif()->count(),
            ],
            'tentatives' => $tentatives,
            'stats' => [
                'nb_tentatives' => $nbTentatives,
                'nb_reussis' => $nbReussis,
                'nb_echoues' => $nbTentatives - $nbReussis,
                'taux_reussite' => $nbTentatives > 0 ? round(($nbReussis / $nbTentatives) * 100) : 0,
                'moyenne_classe' => $moyenneClasse,
                'note_max' => $noteMax,
                'note_min' => $noteMinObtenue,
                'nb_suspicions' => $nbSuspicions,
            ],
            'title' => 'Résultats - ' . $examenEnLigne->titre,
        ]);
    }
}
