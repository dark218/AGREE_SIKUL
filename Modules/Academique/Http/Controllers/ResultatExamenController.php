<?php

namespace Modules\Academique\Http\Controllers;

use Modules\Academique\Entities\ExamenEnLigne;
use Modules\Academique\Entities\TentativeExamen;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Parametrage\Entities\Classe;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class ResultatExamenController extends Controller
{
    /**
     * Vue globale de tous les résultats — AUTOMATIQUE depuis les examens en ligne
     */
    public function index(Request $request)
    {
        $query = TentativeExamen::query()
            ->whereIn('statut', ['corrigee', 'soumise'])
            ->with(['examenEnLigne.matiere', 'examenEnLigne.classe', 'examenEnLigne.enseignant', 'apprenant']);

        // Filtres
        if ($request->filled('examen_id')) {
            $query->where('examen_en_ligne_id', $request->examen_id);
        }
        if ($request->filled('matiere_id')) {
            $query->whereHas('examenEnLigne', function ($q) use ($request) {
                $q->where('matiere_id', $request->matiere_id);
            });
        }
        if ($request->filled('classe_id')) {
            $query->whereHas('examenEnLigne', function ($q) use ($request) {
                $q->where('classe_id', $request->classe_id);
            });
        }
        if ($request->filled('resultat')) {
            if ($request->resultat === 'admis') {
                $query->whereRaw('score >= (SELECT note_minimum_passage FROM examens_en_ligne WHERE examens_en_ligne.id = tentatives_examen.examen_en_ligne_id)');
            } elseif ($request->resultat === 'refuse') {
                $query->whereRaw('score < (SELECT note_minimum_passage FROM examens_en_ligne WHERE examens_en_ligne.id = tentatives_examen.examen_en_ligne_id)');
            }
        }

        $resultats = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($t) {
                $examen = $t->examenEnLigne;
                $noteMin = $examen?->note_minimum_passage ?? (($examen?->note_maximum ?? 20) / 2);
                return [
                    'id' => $t->id,
                    'examen_titre' => $examen?->titre,
                    'matiere' => $examen?->matiere?->libelle,
                    'classe' => $examen?->classe?->nom,
                    'enseignant' => $examen?->enseignant ? $examen->enseignant->prenoms . ' ' . $examen->enseignant->nom : '-',
                    'apprenant_nom' => $t->apprenant ? $t->apprenant->prenoms . ' ' . $t->apprenant->nom : '-',
                    'apprenant_matricule' => $t->apprenant?->matricule,
                    'score' => $t->score,
                    'note_maximum' => $examen?->note_maximum,
                    'pourcentage' => $t->pourcentage,
                    'reponses_correctes' => $t->reponses_correctes,
                    'questions_repondues' => $t->questions_repondues,
                    'temps_passe_minutes' => $t->temps_passe_minutes,
                    'date_composition' => $t->debut_composition?->format('d/m/Y H:i'),
                    'reussi' => $t->score >= $noteMin,
                    'absent' => $t->creation_username === 'SYSTEME - Absent',
                    'suspicion_triche' => $t->suspicion_triche,
                    'nb_incidents' => $t->nb_incidents_total,
                ];
            });

        // Données pour les filtres
        $examens = ExamenEnLigne::with('matiere')
            ->whereIn('etat', ['publie', 'en_cours', 'termine', 'corrige'])
            ->get()
            ->map(fn($e) => ['id' => $e->id, 'titre' => $e->titre . ' (' . ($e->matiere?->libelle ?? '') . ')']);

        $matieres = MatiereUnite::where('statut', 'actif')->get(['id', 'libelle']);
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

        // Stats globales
        $totalTentatives = TentativeExamen::whereIn('statut', ['corrigee', 'soumise'])->count();
        $totalReussis = TentativeExamen::whereIn('statut', ['corrigee', 'soumise'])
            ->whereRaw('score >= (SELECT COALESCE(note_minimum_passage, note_maximum/2) FROM examens_en_ligne WHERE examens_en_ligne.id = tentatives_examen.examen_en_ligne_id)')
            ->count();
        $moyenneGlobale = TentativeExamen::whereIn('statut', ['corrigee', 'soumise'])->avg('score');

        return Inertia::render('Academique::ResultatsExamens/Index', [
            'resultats' => $resultats,
            'examens' => $examens,
            'matieres' => $matieres,
            'classes' => $classes,
            'filters' => $request->only(['examen_id', 'matiere_id', 'classe_id', 'resultat']),
            'stats' => [
                'total' => $totalTentatives,
                'reussis' => $totalReussis,
                'echoues' => $totalTentatives - $totalReussis,
                'taux_reussite' => $totalTentatives > 0 ? round(($totalReussis / $totalTentatives) * 100) : 0,
                'moyenne_globale' => round($moyenneGlobale ?? 0, 2),
            ],
            'title' => 'Résultats des Examens'
        ]);
    }

    /**
     * Détails d'un résultat individuel
     */
    public function show(TentativeExamen $resultatExamen)
    {
        $resultatExamen->load(['examenEnLigne.matiere', 'examenEnLigne.classe', 'examenEnLigne.enseignant', 'apprenant', 'reponses.question.reponses', 'logsSurveillance']);

        $examen = $resultatExamen->examenEnLigne;
        $noteMin = $examen?->note_minimum_passage ?? (($examen?->note_maximum ?? 20) / 2);

        // Construire la correction détaillée
        $correction = $examen->questions()->actif()->with('reponses')->orderBy('ordre')->get()->map(function ($q) use ($resultatExamen) {
            $reponseApprenant = $resultatExamen->reponses->firstWhere('question_examen_id', $q->id);
            return [
                'enonce' => $q->enonce,
                'type' => $q->type,
                'points' => $q->points,
                'explication' => $q->explication,
                'reponses' => $q->reponses->map(fn($r) => [
                    'id' => $r->id,
                    'texte' => $r->texte,
                    'est_correcte' => $r->est_correcte,
                ]),
                'reponse_choisie_id' => $reponseApprenant?->reponse_choisie_id,
                'reponse_libre' => $reponseApprenant?->reponse_libre,
                'est_correcte' => $reponseApprenant?->est_correcte,
                'points_obtenus' => $reponseApprenant?->points_obtenus ?? 0,
            ];
        });

        return Inertia::render('Academique::ResultatsExamens/Show', [
            'tentative' => [
                'id' => $resultatExamen->id,
                'score' => $resultatExamen->score,
                'pourcentage' => $resultatExamen->pourcentage,
                'questions_repondues' => $resultatExamen->questions_repondues,
                'reponses_correctes' => $resultatExamen->reponses_correctes,
                'temps_passe_minutes' => $resultatExamen->temps_passe_minutes,
                'debut_composition' => $resultatExamen->debut_composition?->format('d/m/Y H:i'),
                'fin_composition' => $resultatExamen->fin_composition?->format('d/m/Y H:i'),
                'nb_incidents_total' => $resultatExamen->nb_incidents_total,
                'suspicion_triche' => $resultatExamen->suspicion_triche,
            ],
            'examen' => [
                'titre' => $examen->titre,
                'matiere' => $examen->matiere?->libelle,
                'classe' => $examen->classe?->nom,
                'note_maximum' => $examen->note_maximum,
                'note_minimum_passage' => $noteMin,
                'nombre_questions' => $examen->questions()->actif()->count(),
            ],
            'apprenant' => [
                'nom' => $resultatExamen->apprenant?->prenoms . ' ' . $resultatExamen->apprenant?->nom,
                'matricule' => $resultatExamen->apprenant?->matricule,
            ],
            'reussi' => $resultatExamen->score >= $noteMin,
            'correction' => $correction,
            'logs' => $resultatExamen->logsSurveillance->map(fn($l) => [
                'type' => $l->type_incident,
                'details' => $l->details,
                'horodatage' => $l->horodatage?->format('d/m/Y H:i:s'),
            ]),
            'title' => 'Détail résultat - ' . ($resultatExamen->apprenant?->prenoms ?? '') . ' ' . ($resultatExamen->apprenant?->nom ?? ''),
        ]);
    }

    /**
     * PDF individuel — Fiche de résultat d'un apprenant
     */
    public function pdfIndividuel(TentativeExamen $resultatExamen)
    {
        $resultatExamen->load(['examenEnLigne.matiere', 'examenEnLigne.classe', 'examenEnLigne.enseignant', 'apprenant', 'reponses.question.reponses']);

        $examen = $resultatExamen->examenEnLigne;
        $noteMin = $examen?->note_minimum_passage ?? (($examen?->note_maximum ?? 20) / 2);
        $reussi = $resultatExamen->score >= $noteMin;

        $data = [
            'tentative' => $resultatExamen,
            'examen' => $examen,
            'apprenant' => $resultatExamen->apprenant,
            'reussi' => $reussi,
            'noteMin' => $noteMin,
            'date' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('academique::pdf.resultat-individuel', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'resultat_' . ($resultatExamen->apprenant?->matricule ?? $resultatExamen->id) . '_' . str_replace(' ', '_', $examen->titre) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * PDF groupé — Tous les résultats d'un examen (par classe)
     */
    public function pdfClasse(ExamenEnLigne $examenEnLigne)
    {
        $examenEnLigne->load(['matiere', 'classe', 'enseignant']);

        $tentatives = TentativeExamen::where('examen_en_ligne_id', $examenEnLigne->id)
            ->with(['apprenant'])
            ->whereIn('statut', ['corrigee', 'soumise'])
            ->orderBy('score', 'desc')
            ->get();

        $noteMin = $examenEnLigne->note_minimum_passage ?? ($examenEnLigne->note_maximum / 2);
        $nbReussis = $tentatives->filter(fn($t) => $t->score >= $noteMin)->count();
        $moyenne = $tentatives->count() > 0 ? round($tentatives->avg('score'), 2) : 0;

        $data = [
            'examen' => $examenEnLigne,
            'tentatives' => $tentatives,
            'noteMin' => $noteMin,
            'nbReussis' => $nbReussis,
            'nbEchoues' => $tentatives->count() - $nbReussis,
            'moyenne' => $moyenne,
            'tauxReussite' => $tentatives->count() > 0 ? round(($nbReussis / $tentatives->count()) * 100) : 0,
            'noteMax' => $tentatives->max('score') ?? 0,
            'noteMinObtenue' => $tentatives->count() > 0 ? $tentatives->min('score') : 0,
            'date' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('academique::pdf.resultats-classe', $data);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'resultats_classe_' . str_replace(' ', '_', $examenEnLigne->titre) . '_' . ($examenEnLigne->classe?->nom ?? 'all') . '.pdf';

        return $pdf->download($filename);
    }
}
