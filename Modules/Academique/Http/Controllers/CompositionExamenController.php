<?php

namespace Modules\Academique\Http\Controllers;

use Modules\Academique\Entities\ExamenEnLigne;
use Modules\Academique\Entities\QuestionExamen;
use Modules\Academique\Entities\TentativeExamen;
use Modules\Academique\Entities\ReponseApprenant;
use Modules\Academique\Entities\Apprenant;
use Modules\Academique\Entities\LogSurveillanceExamen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;

class CompositionExamenController extends Controller
{
    /**
     * Trouver l'apprenant lié à l'utilisateur connecté
     */
    private function getApprenant()
    {
        return Apprenant::where('user_id', auth()->id())->first();
    }

    /**
     * Liste des examens disponibles pour l'apprenant
     */
    public function mesExamens()
    {
        $apprenant = $this->getApprenant();

        if (!$apprenant) {
            return Inertia::render('Academique::Composition/MesExamens', [
                'examens' => [],
                'apprenant' => null,
                'title' => 'Mes Examens',
                'error' => 'Votre compte n\'est pas lié à un profil apprenant.'
            ]);
        }

        $examens = ExamenEnLigne::where(function ($q) use ($apprenant) {
                $q->where('classe_id', $apprenant->classe_id)
                  ->orWhereNull('classe_id');
            })
            ->whereIn('etat', ['publie', 'en_cours'])
            ->with(['matiere', 'classe', 'enseignant'])
            ->withCount('questions')
            ->get()
            ->map(function ($examen) use ($apprenant) {
                $tentatives = $examen->tentatives()
                    ->where('apprenant_id', $apprenant->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $derniereTentative = $tentatives->first();
                $nbTentatives = $tentatives->count();
                $meilleureNote = $tentatives->where('statut', 'corrigee')->max('score');

                return [
                    'id' => $examen->id,
                    'titre' => $examen->titre,
                    'description' => $examen->description,
                    'instructions' => $examen->instructions,
                    'matiere' => $examen->matiere?->libelle,
                    'classe' => $examen->classe?->nom,
                    'enseignant' => $examen->enseignant ? $examen->enseignant->prenoms . ' ' . $examen->enseignant->nom : null,
                    'date_debut' => $examen->date_debut?->format('d/m/Y H:i'),
                    'date_fin' => $examen->date_fin?->format('d/m/Y H:i'),
                    'duree_minutes' => $examen->duree_minutes,
                    'nombre_questions' => $examen->questions_count,
                    'note_maximum' => $examen->note_maximum,
                    'nombre_tentatives_max' => $examen->nombre_tentatives,
                    'nb_tentatives_utilisees' => $nbTentatives,
                    'meilleure_note' => $meilleureNote,
                    'etat' => $examen->etat,
                    'a_mot_de_passe' => !empty($examen->mot_de_passe),
                    'peut_composer' => $examen->peutTenterExamen($apprenant->id),
                    'en_cours' => $derniereTentative && $derniereTentative->statut === 'en_cours',
                    'tentative_en_cours_id' => $derniereTentative && $derniereTentative->statut === 'en_cours' ? $derniereTentative->id : null,
                ];
            });

        return Inertia::render('Academique::Composition/MesExamens', [
            'examens' => $examens,
            'apprenant' => [
                'id' => $apprenant->id,
                'nom' => $apprenant->nom,
                'prenoms' => $apprenant->prenoms,
                'matricule' => $apprenant->matricule,
                'classe' => $apprenant->classe?->nom,
            ],
            'title' => 'Mes Examens'
        ]);
    }

    /**
     * Vérifier le mot de passe et démarrer l'examen
     */
    public function demarrer(Request $request, ExamenEnLigne $examenEnLigne)
    {
        $apprenant = $this->getApprenant();
        if (!$apprenant) {
            return back()->with('error', 'Profil apprenant non trouvé.');
        }

        // Vérifier mot de passe si requis
        if (!empty($examenEnLigne->mot_de_passe)) {
            $request->validate(['mot_de_passe' => 'required|string']);
            if ($request->mot_de_passe !== $examenEnLigne->mot_de_passe) {
                return back()->with('error', 'Mot de passe incorrect.');
            }
        }

        // Vérifier que l'apprenant peut encore tenter
        if (!$examenEnLigne->peutTenterExamen($apprenant->id)) {
            return back()->with('error', 'Vous avez épuisé vos tentatives pour cet examen.');
        }

        // Vérifier si une tentative en cours existe déjà
        $tentativeEnCours = TentativeExamen::where('examen_en_ligne_id', $examenEnLigne->id)
            ->where('apprenant_id', $apprenant->id)
            ->where('statut', 'en_cours')
            ->first();

        if ($tentativeEnCours) {
            return redirect()->route('academique.composition.composer', $tentativeEnCours->id);
        }

        // Créer une nouvelle tentative
        $nbTentatives = $examenEnLigne->getNombreTentatives($apprenant->id);
        $tentative = TentativeExamen::create([
            'examen_en_ligne_id' => $examenEnLigne->id,
            'apprenant_id' => $apprenant->id,
            'numero_tentative' => $nbTentatives + 1,
            'debut_composition' => now(),
            'statut' => 'en_cours',
            'ip_address' => $request->ip(),
            'creation_username' => auth()->user()->name,
        ]);

        return redirect()->route('academique.composition.composer', $tentative->id);
    }

    /**
     * Page de composition — l'apprenant répond aux questions
     */
    public function composer(TentativeExamen $tentative)
    {
        $apprenant = $this->getApprenant();
        if (!$apprenant || $tentative->apprenant_id !== $apprenant->id) {
            return redirect()->route('academique.composition.mes-examens')
                ->with('error', 'Accès non autorisé.');
        }

        if ($tentative->statut !== 'en_cours') {
            return redirect()->route('academique.composition.resultat', $tentative->id);
        }

        $examen = $tentative->examenEnLigne;
        $examen->load(['matiere', 'enseignant']);

        // Charger les questions (avec mélange si activé)
        $questions = $examen->questions()
            ->actif()
            ->with('reponses')
            ->orderBy('ordre')
            ->get();

        if ($examen->melange_questions) {
            $questions = $questions->shuffle();
        }

        $questionsFormatted = $questions->map(function ($q) use ($examen) {
            $reponses = $q->reponses;
            if ($examen->melange_reponses) {
                $reponses = $reponses->shuffle();
            }

            return [
                'id' => $q->id,
                'ordre' => $q->ordre,
                'type' => $q->type,
                'enonce' => $q->enonce,
                'points' => $q->points,
                'obligatoire' => $q->obligatoire,
                'reponses' => $reponses->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'texte' => $r->texte,
                        // Ne PAS envoyer est_correcte !
                    ];
                })->values(),
            ];
        })->values();

        // Charger les réponses déjà données (si retour en arrière autorisé)
        $reponsesExistantes = ReponseApprenant::where('tentative_examen_id', $tentative->id)
            ->get()
            ->keyBy('question_examen_id')
            ->map(function ($r) {
                return [
                    'reponse_choisie_id' => $r->reponse_choisie_id,
                    'reponse_libre' => $r->reponse_libre,
                ];
            });

        // Calculer le temps restant
        $tempsEcoule = now()->diffInSeconds($tentative->debut_composition);
        $tempsTotal = (int) ($examen->duree_minutes * 60);
        $tempsRestant = max(0, $tempsTotal - $tempsEcoule);

        return Inertia::render('Academique::Composition/Composer', [
            'tentative' => [
                'id' => $tentative->id,
                'numero_tentative' => $tentative->numero_tentative,
                'debut_composition' => $tentative->debut_composition->toIso8601String(),
            ],
            'examen' => [
                'id' => $examen->id,
                'titre' => $examen->titre,
                'instructions' => $examen->instructions,
                'matiere' => $examen->matiere?->libelle,
                'enseignant' => $examen->enseignant ? $examen->enseignant->prenoms . ' ' . $examen->enseignant->nom : null,
                'duree_minutes' => $examen->duree_minutes,
                'note_maximum' => $examen->note_maximum,
                'retour_arriere' => $examen->retour_arriere,
                'nombre_questions' => $questionsFormatted->count(),
            ],
            'questions' => $questionsFormatted,
            'reponses_existantes' => $reponsesExistantes,
            'temps_restant_secondes' => $tempsRestant,
            'title' => $examen->titre,
        ]);
    }

    /**
     * Sauvegarder la réponse à une question (appel AJAX)
     */
    public function sauvegarderReponse(Request $request, TentativeExamen $tentative)
    {
        $apprenant = $this->getApprenant();
        if (!$apprenant || $tentative->apprenant_id !== $apprenant->id) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        if ($tentative->statut !== 'en_cours') {
            return response()->json(['error' => 'Cette tentative est déjà terminée.'], 400);
        }

        // Vérifier que le temps n'est pas écoulé
        $examen = $tentative->examenEnLigne;
        $tempsEcoule = now()->diffInSeconds($tentative->debut_composition);
        $tempsTotal = (int) ($examen->duree_minutes * 60);
        if ($tempsEcoule > $tempsTotal + 30) { // 30s de marge
            $this->autoSoumettre($tentative);
            return response()->json(['error' => 'Temps écoulé. Examen soumis automatiquement.', 'time_up' => true], 400);
        }

        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:questions_examen,id',
            'reponse_choisie_id' => 'nullable|exists:reponses_question,id',
            'reponse_libre' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        ReponseApprenant::updateOrCreate(
            [
                'tentative_examen_id' => $tentative->id,
                'question_examen_id' => $request->question_id,
            ],
            [
                'reponse_choisie_id' => $request->reponse_choisie_id,
                'reponse_libre' => $request->reponse_libre,
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Logger un incident de surveillance (appel AJAX)
     */
    public function logIncident(Request $request, TentativeExamen $tentative)
    {
        $apprenant = $this->getApprenant();
        if (!$apprenant || $tentative->apprenant_id !== $apprenant->id) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type_incident' => 'required|string|in:tab_switch,copy_attempt,paste_attempt,right_click,fullscreen_exit,devtools_open,window_blur,window_resize,screen_capture',
            'details' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Logger l'incident
        LogSurveillanceExamen::create([
            'tentative_examen_id' => $tentative->id,
            'apprenant_id' => $apprenant->id,
            'type_incident' => $request->type_incident,
            'details' => $request->details,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'horodatage' => now(),
        ]);

        // Mettre à jour les compteurs
        $counters = [];
        switch ($request->type_incident) {
            case 'tab_switch':
            case 'window_blur':
                $tentative->increment('nb_changements_onglet');
                break;
            case 'copy_attempt':
            case 'paste_attempt':
                $tentative->increment('nb_tentatives_copie');
                break;
            case 'fullscreen_exit':
                $tentative->increment('nb_sorties_fullscreen');
                break;
        }

        $tentative->increment('nb_incidents_total');

        // Marquer suspicion si trop d'incidents
        if ($tentative->nb_incidents_total >= 5) {
            $tentative->update(['suspicion_triche' => true]);
        }

        return response()->json(['success' => true, 'total_incidents' => $tentative->nb_incidents_total]);
    }

    /**
     * Soumettre l'examen (fin de composition)
     */
    public function soumettre(Request $request, TentativeExamen $tentative)
    {
        $apprenant = $this->getApprenant();
        if (!$apprenant || $tentative->apprenant_id !== $apprenant->id) {
            return back()->with('error', 'Accès non autorisé.');
        }

        if ($tentative->statut !== 'en_cours') {
            return redirect()->route('academique.composition.resultat', $tentative->id);
        }

        $this->corrigerTentative($tentative);

        return redirect()->route('academique.composition.resultat', $tentative->id);
    }

    /**
     * Soumission automatique (temps écoulé)
     */
    private function autoSoumettre(TentativeExamen $tentative)
    {
        if ($tentative->statut === 'en_cours') {
            $this->corrigerTentative($tentative);
        }
    }

    /**
     * Corriger automatiquement la tentative
     */
    private function corrigerTentative(TentativeExamen $tentative)
    {
        $examen = $tentative->examenEnLigne;
        $questions = $examen->questions()->actif()->with('reponses')->get();
        $reponses = $tentative->reponses()->get()->keyBy('question_examen_id');

        $totalPoints = 0;
        $questionsRepondues = 0;
        $reponsesCorrectes = 0;

        foreach ($questions as $question) {
            $reponseApprenant = $reponses->get($question->id);

            if (!$reponseApprenant) continue;

            $questionsRepondues++;

            if (in_array($question->type, ['qcm', 'vrai_faux'])) {
                // Correction automatique
                $bonneReponse = $question->reponses->firstWhere('est_correcte', true);
                $estCorrecte = $bonneReponse && $reponseApprenant->reponse_choisie_id === $bonneReponse->id;

                $pointsObtenus = $estCorrecte ? $question->points : 0;
                $reponsesCorrectes += $estCorrecte ? 1 : 0;
                $totalPoints += $pointsObtenus;

                $reponseApprenant->update([
                    'est_correcte' => $estCorrecte,
                    'points_obtenus' => $pointsObtenus,
                ]);
            }
            // Les réponses libres restent à corriger manuellement
        }

        $tempsEcoule = now()->diffInMinutes($tentative->debut_composition, true);
        $noteMax = $examen->note_maximum ?? $questions->sum('points');
        $totalPointsMax = $questions->sum('points');
        $score = $totalPointsMax > 0 ? ($totalPoints / $totalPointsMax) * $noteMax : 0;
        $pourcentage = $totalPointsMax > 0 ? ($totalPoints / $totalPointsMax) * 100 : 0;

        $tentative->update([
            'fin_composition' => now(),
            'temps_passe_minutes' => round($tempsEcoule, 2),
            'score' => round($score, 2),
            'pourcentage' => round($pourcentage, 2),
            'questions_repondues' => $questionsRepondues,
            'reponses_correctes' => $reponsesCorrectes,
            'statut' => 'corrigee',
        ]);
    }

    /**
     * Page de résultat après soumission
     */
    public function resultat(TentativeExamen $tentative)
    {
        $apprenant = $this->getApprenant();
        if (!$apprenant || $tentative->apprenant_id !== $apprenant->id) {
            return redirect()->route('academique.composition.mes-examens')
                ->with('error', 'Accès non autorisé.');
        }

        $examen = $tentative->examenEnLigne;
        $examen->load(['matiere', 'enseignant']);

        $noteMinPassage = $examen->note_minimum_passage ?? ($examen->note_maximum / 2);
        $reussi = $tentative->score >= $noteMinPassage;

        $resultData = [
            'tentative' => [
                'id' => $tentative->id,
                'numero_tentative' => $tentative->numero_tentative,
                'score' => $tentative->score,
                'pourcentage' => $tentative->pourcentage,
                'questions_repondues' => $tentative->questions_repondues,
                'reponses_correctes' => $tentative->reponses_correctes,
                'temps_passe_minutes' => $tentative->temps_passe_minutes,
                'debut_composition' => $tentative->debut_composition?->format('d/m/Y H:i'),
                'fin_composition' => $tentative->fin_composition?->format('d/m/Y H:i'),
                'statut' => $tentative->statut,
            ],
            'examen' => [
                'id' => $examen->id,
                'titre' => $examen->titre,
                'matiere' => $examen->matiere?->libelle,
                'note_maximum' => $examen->note_maximum,
                'note_minimum_passage' => $noteMinPassage,
                'nombre_questions' => $examen->questions()->actif()->count(),
                'afficher_resultat' => $examen->afficher_resultat,
                'afficher_correction' => $examen->afficher_correction,
            ],
            'reussi' => $reussi,
            'title' => 'Résultat - ' . $examen->titre,
        ];

        // Si l'enseignant autorise l'affichage de la correction
        if ($examen->afficher_correction) {
            $questions = $examen->questions()->actif()->with('reponses')->orderBy('ordre')->get();
            $reponsesApprenant = $tentative->reponses()->get()->keyBy('question_examen_id');

            $resultData['correction'] = $questions->map(function ($q) use ($reponsesApprenant) {
                $reponseApprenant = $reponsesApprenant->get($q->id);
                return [
                    'enonce' => $q->enonce,
                    'type' => $q->type,
                    'points' => $q->points,
                    'explication' => $q->explication,
                    'reponses' => $q->reponses->map(function ($r) {
                        return [
                            'id' => $r->id,
                            'texte' => $r->texte,
                            'est_correcte' => $r->est_correcte,
                        ];
                    }),
                    'reponse_choisie_id' => $reponseApprenant?->reponse_choisie_id,
                    'reponse_libre' => $reponseApprenant?->reponse_libre,
                    'est_correcte' => $reponseApprenant?->est_correcte,
                    'points_obtenus' => $reponseApprenant?->points_obtenus ?? 0,
                ];
            });
        }

        return Inertia::render('Academique::Composition/Resultat', $resultData);
    }
}
