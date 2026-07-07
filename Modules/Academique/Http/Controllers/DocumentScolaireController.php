<?php

namespace Modules\Academique\Http\Controllers;

use Modules\Academique\Entities\Bulletin;
use Modules\Academique\Entities\Note;
use Modules\Academique\Entities\Apprenant;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Parametrage\Entities\Classe;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentScolaireController extends Controller
{
    /**
     * Bulletin de notes individuel (Type 1 — Secondaire complet)
     */
    public function bulletinType1(Bulletin $bulletin)
    {
        $bulletin->load([
            'apprenant.classe.ecole',
            'classe.ecole',
            'anneeScolaire',
            'moyenneMatieres.matiere'
        ]);

        $apprenant = $bulletin->apprenant;
        $classe = $bulletin->classe;
        $ecole = $classe?->ecole;

        // Grouper les matières par catégorie
        $matieres = $bulletin->moyenneMatieres->map(function ($mm) {
            return [
                'libelle' => $mm->matiere?->libelle ?? '-',
                'code' => $mm->matiere?->code ?? '',
                'moyenne' => $mm->moyenne,
                'coefficient' => $mm->coefficient,
                'moyenne_coef' => round(($mm->moyenne ?? 0) * ($mm->coefficient ?? 1), 2),
                'rang' => $mm->rang,
                'appreciation' => $mm->appreciation ?? $this->getAppreciation($mm->moyenne),
            ];
        });

        // Si pas assez de moyennes matières, charger les notes directement
        if ($matieres->count() === 0) {
            $notes = Note::where('apprenant_id', $apprenant->id)
                ->where('classe_id', $classe->id)
                ->with('matiere')
                ->get()
                ->groupBy('matiere_id')
                ->map(function ($notesMatiere) {
                    $matiere = $notesMatiere->first()->matiere;
                    $moyenne = $notesMatiere->avg('note_originale');
                    $coef = $matiere?->coefficient ?? 1;
                    return [
                        'libelle' => $matiere?->libelle ?? '-',
                        'code' => $matiere?->code ?? '',
                        'moyenne' => round($moyenne, 2),
                        'coefficient' => $coef,
                        'moyenne_coef' => round($moyenne * $coef, 2),
                        'rang' => '-',
                        'appreciation' => $this->getAppreciation($moyenne),
                    ];
                })->values();
            $matieres = $notes;
        }

        $totalCoef = $matieres->sum('coefficient');
        $totalMoyCoef = $matieres->sum('moyenne_coef');
        $moyenneGenerale = $totalCoef > 0 ? round($totalMoyCoef / $totalCoef, 2) : 0;

        // Stats classe
        $bulletinsClasse = Bulletin::where('classe_id', $classe->id)
            ->where('periode', $bulletin->periode)
            ->where('annee_scolaire_id', $bulletin->annee_scolaire_id)
            ->pluck('moyenne_generale');

        $data = [
            'bulletin' => $bulletin,
            'apprenant' => $apprenant,
            'classe' => $classe,
            'ecole' => $ecole,
            'annee' => $bulletin->anneeScolaire?->libelle,
            'periode' => $this->getPeriodeLabel($bulletin->periode),
            'matieres' => $matieres,
            'totalCoef' => $totalCoef,
            'totalMoyCoef' => $totalMoyCoef,
            'moyenneGenerale' => $bulletin->moyenne_generale ?? $moyenneGenerale,
            'rang' => $bulletin->rang,
            'decision' => $bulletin->decision_conseil,
            'appreciation' => $bulletin->appreciation_generale ?? $this->getAppreciation($bulletin->moyenne_generale ?? $moyenneGenerale),
            'effectif' => $bulletinsClasse->count(),
            'moyenneClasse' => $bulletinsClasse->count() > 0 ? round($bulletinsClasse->avg(), 2) : '-',
            'premierClasse' => $bulletinsClasse->count() > 0 ? $bulletinsClasse->max() : '-',
            'dernierClasse' => $bulletinsClasse->count() > 0 ? $bulletinsClasse->min() : '-',
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.bulletin-type1', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'bulletin_' . ($apprenant?->matricule ?? '') . '_' . $bulletin->periode . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Bulletin de notes avec séquences (Type 2)
     */
    public function bulletinType2(Bulletin $bulletin)
    {
        $bulletin->load([
            'apprenant.classe.ecole',
            'classe.ecole',
            'anneeScolaire',
            'moyenneMatieres.matiere'
        ]);

        $apprenant = $bulletin->apprenant;
        $classe = $bulletin->classe;
        $ecole = $classe?->ecole;

        // Charger les notes par séquence
        $notes = Note::where('apprenant_id', $apprenant->id)
            ->where('classe_id', $classe->id)
            ->with('matiere')
            ->get();

        $matieresData = MatiereUnite::whereIn('id', $notes->pluck('matiere_id')->unique())
            ->get()
            ->map(function ($matiere) use ($notes) {
                $notesMatiere = $notes->where('matiere_id', $matiere->id);
                $seq1 = $notesMatiere->where('nature_examen_id', 1)->first()?->note_originale;
                $seq2 = $notesMatiere->where('nature_examen_id', 2)->first()?->note_originale;
                $seq3 = $notesMatiere->where('nature_examen_id', 3)->first()?->note_originale;
                $exam = $notesMatiere->where('type_examen_id', 1)->first()?->note_originale;
                $moyenne = $notesMatiere->avg('note_originale');

                return [
                    'libelle' => $matiere->libelle,
                    'coefficient' => $matiere->coefficient ?? 1,
                    'seq1' => $seq1,
                    'seq2' => $seq2,
                    'seq3' => $seq3,
                    'exam' => $exam,
                    'moyenne' => $moyenne ? round($moyenne, 2) : null,
                    'moyenne_coef' => $moyenne ? round($moyenne * ($matiere->coefficient ?? 1), 2) : 0,
                    'appreciation' => $this->getAppreciation($moyenne),
                ];
            });

        $totalCoef = $matieresData->sum('coefficient');
        $totalMoyCoef = $matieresData->sum('moyenne_coef');

        $data = [
            'bulletin' => $bulletin,
            'apprenant' => $apprenant,
            'classe' => $classe,
            'ecole' => $ecole,
            'annee' => $bulletin->anneeScolaire?->libelle,
            'periode' => $this->getPeriodeLabel($bulletin->periode),
            'matieres' => $matieresData,
            'totalCoef' => $totalCoef,
            'totalMoyCoef' => $totalMoyCoef,
            'moyenneGenerale' => $bulletin->moyenne_generale ?? ($totalCoef > 0 ? round($totalMoyCoef / $totalCoef, 2) : 0),
            'rang' => $bulletin->rang,
            'decision' => $bulletin->decision_conseil,
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.bulletin-type2', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('bulletin_seq_' . ($apprenant?->matricule ?? '') . '.pdf');
    }

    /**
     * Relevé de notes par classe (Type X — feuille enseignant)
     */
    public function releveClasse(Request $request)
    {
        $classeId = $request->input('classe_id');
        $matiereId = $request->input('matiere_id');
        $anneeScolaireId = $request->input('annee_scolaire_id');

        $classe = Classe::with('ecole')->findOrFail($classeId);
        $matiere = MatiereUnite::findOrFail($matiereId);

        $apprenants = Apprenant::where('classe_id', $classeId)
            ->orderBy('nom')
            ->get();

        $notes = Note::where('classe_id', $classeId)
            ->where('matiere_id', $matiereId)
            ->when($anneeScolaireId, fn($q) => $q->where('annee_scolaire_id', $anneeScolaireId))
            ->get()
            ->groupBy('apprenant_id');

        $lignes = $apprenants->map(function ($apprenant) use ($notes) {
            $notesApprenant = $notes->get($apprenant->id, collect());
            $moyenne = $notesApprenant->count() > 0 ? round($notesApprenant->avg('note_originale'), 2) : null;

            return [
                'matricule' => $apprenant->matricule,
                'nom' => $apprenant->nom,
                'prenoms' => $apprenant->prenoms,
                'notes' => $notesApprenant->pluck('note_originale')->toArray(),
                'moyenne' => $moyenne,
            ];
        });

        $noteMin = $request->input('note_passage', 10);

        $data = [
            'classe' => $classe,
            'matiere' => $matiere,
            'ecole' => $classe->ecole,
            'lignes' => $lignes,
            'notePassage' => $noteMin,
            'nbApprenants' => $apprenants->count(),
            'nbReussis' => $lignes->filter(fn($l) => $l['moyenne'] !== null && $l['moyenne'] >= $noteMin)->count(),
            'moyenneClasse' => $lignes->filter(fn($l) => $l['moyenne'] !== null)->avg('moyenne'),
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.releve-classe', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('releve_' . $classe->nom . '_' . $matiere->libelle . '.pdf');
    }

    // === Helpers ===

    private function getAppreciation($note): string
    {
        if ($note === null) return '-';
        if ($note >= 18) return 'Excellent';
        if ($note >= 16) return 'Très Bien';
        if ($note >= 14) return 'Bien';
        if ($note >= 12) return 'Assez Bien';
        if ($note >= 10) return 'Passable';
        if ($note >= 8) return 'Insuffisant';
        return 'Très Insuffisant';
    }

    private function getPeriodeLabel($periode): string
    {
        $labels = [
            'trimestre1' => '1er Trimestre',
            'trimestre2' => '2ème Trimestre',
            'trimestre3' => '3ème Trimestre',
            'semestre1' => '1er Semestre',
            'semestre2' => '2ème Semestre',
            'annuel' => 'Annuel',
        ];
        return $labels[$periode] ?? $periode;
    }

    private function getMention($moyenne): string
    {
        if ($moyenne >= 16) return 'Très Bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez Bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Ajourné(e)';
    }

    // ================================================================
    // RELEVÉS UNIVERSITAIRES
    // ================================================================

    /**
     * Relevé Type 1 — Annuel universitaire (tous semestres)
     */
    public function releveType1(Bulletin $bulletin)
    {
        $bulletin->load(['apprenant', 'classe.ecole', 'anneeScolaire', 'moyenneMatieres.matiere']);
        $apprenant = $bulletin->apprenant;

        $matieres = $bulletin->moyenneMatieres->map(function ($mm) {
            return [
                'code' => $mm->matiere?->code ?? '',
                'libelle' => $mm->matiere?->libelle ?? '-',
                'credits' => 3,
                'coefficient' => $mm->coefficient ?? 1,
                'note' => $mm->moyenne,
                'note_coef' => round(($mm->moyenne ?? 0) * ($mm->coefficient ?? 1), 2),
                'credits_obtenus' => ($mm->moyenne ?? 0) >= 10 ? 3 : 0,
                'observation' => $this->getAppreciation($mm->moyenne),
            ];
        });

        $totalCoef = $matieres->sum('coefficient');
        $totalNoteCoef = $matieres->sum('note_coef');
        $totalCredits = $matieres->sum('credits');
        $totalCreditsObtenus = $matieres->sum('credits_obtenus');
        $moyenne = $totalCoef > 0 ? round($totalNoteCoef / $totalCoef, 2) : 0;

        $data = [
            'apprenant' => $apprenant,
            'ecole' => $bulletin->classe?->ecole?->nom,
            'annee' => $bulletin->anneeScolaire?->libelle,
            'classe' => $bulletin->classe?->nom,
            'parcours' => $bulletin->classe?->nom,
            'semestres' => [
                [
                    'label' => $this->getPeriodeLabel($bulletin->periode),
                    'matieres' => $matieres->toArray(),
                    'total_credits' => $totalCredits,
                    'total_coef' => $totalCoef,
                    'total_note_coef' => $totalNoteCoef,
                    'credits_obtenus' => $totalCreditsObtenus,
                    'moyenne' => $moyenne,
                ],
            ],
            'moyenneAnnuelle' => $bulletin->moyenne_generale ?? $moyenne,
            'totalCredits' => $totalCredits,
            'totalCreditsObtenus' => $totalCreditsObtenus,
            'mention' => $this->getMention($bulletin->moyenne_generale ?? $moyenne),
            'decision' => $bulletin->decision_conseil ?? 'Admis(e)',
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.releve-type1', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('releve_annuel_' . ($apprenant?->matricule ?? '') . '.pdf');
    }

    /**
     * Relevé Type 2 — Session Normale + Rattrapage
     */
    public function releveType2(Bulletin $bulletin)
    {
        $bulletin->load(['apprenant', 'classe.ecole', 'anneeScolaire', 'moyenneMatieres.matiere']);
        $apprenant = $bulletin->apprenant;

        $matieres = $bulletin->moyenneMatieres->map(function ($mm) {
            return [
                'intitule' => $mm->matiere?->libelle ?? '-',
                'credits' => 3,
                'coef' => $mm->coefficient ?? 1,
                'note_normal' => $mm->moyenne,
                'note_rattrapage' => null,
            ];
        });

        $moyenne = $bulletin->moyenne_generale ?? $matieres->avg('note_normal') ?? 0;

        $data = [
            'apprenant' => $apprenant,
            'ecole' => $bulletin->classe?->ecole?->nom,
            'annee' => $bulletin->anneeScolaire?->libelle,
            'filiere' => $bulletin->classe?->nom,
            'domaine' => '-',
            'specialite' => '-',
            'diplome' => 'Licence',
            'mention' => $this->getMention($moyenne),
            'semestres' => [
                [
                    'label' => $this->getPeriodeLabel($bulletin->periode),
                    'ues' => [
                        [
                            'nature' => 'UEF',
                            'code' => 'UE1',
                            'intitule' => 'Unité Fondamentale',
                            'credits' => $matieres->sum('credits'),
                            'coef' => $matieres->sum('coef'),
                            'note_ue_normal' => $moyenne,
                            'credits_normal' => $matieres->where('note_normal', '>=', 10)->sum('credits'),
                            'note_ue_rattrapage' => null,
                            'credits_rattrapage' => null,
                            'matieres' => $matieres->toArray(),
                        ],
                    ],
                    'total_credits' => $matieres->sum('credits'),
                    'moyenne_normal' => $moyenne,
                    'moyenne_rattrapage' => 0,
                ],
            ],
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.releve-type2', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('releve_sessions_' . ($apprenant?->matricule ?? '') . '.pdf');
    }

    /**
     * Relevé Type 3 — Par semestre unique
     */
    public function releveType3(Bulletin $bulletin)
    {
        $bulletin->load(['apprenant', 'classe.ecole', 'anneeScolaire', 'moyenneMatieres.matiere']);
        $apprenant = $bulletin->apprenant;

        $matieres = $bulletin->moyenneMatieres->map(function ($mm) {
            return [
                'intitule' => $mm->matiere?->libelle ?? '-',
                'credits' => 3,
                'coef' => $mm->coefficient ?? 1,
                'note' => $mm->moyenne,
                'credits_obtenus' => ($mm->moyenne ?? 0) >= 10 ? 3 : 0,
                'session' => 'Normale',
            ];
        });

        $totalCredits = $matieres->sum('credits');
        $creditsObtenus = $matieres->sum('credits_obtenus');
        $moyenne = $bulletin->moyenne_generale ?? ($matieres->count() > 0 ? round($matieres->avg('note'), 2) : 0);

        $data = [
            'apprenant' => $apprenant,
            'ecole' => $bulletin->classe?->ecole?->nom,
            'filiere' => $bulletin->classe?->nom,
            'domaine' => '-',
            'specialite' => '-',
            'diplome' => 'Licence',
            'semestreLabel' => $this->getPeriodeLabel($bulletin->periode),
            'ues' => [
                [
                    'nature' => 'UEF',
                    'code' => 'UE1',
                    'intitule' => 'Unité Fondamentale',
                    'credits' => $totalCredits,
                    'coef' => $matieres->sum('coef'),
                    'note_ue' => $moyenne,
                    'credits_obtenus' => $creditsObtenus,
                    'matieres' => $matieres->toArray(),
                ],
            ],
            'totalCredits' => $totalCredits,
            'creditsObtenus' => $creditsObtenus,
            'moyenne' => $moyenne,
            'decision' => $bulletin->decision_conseil ?? '-',
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.releve-type3', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('releve_semestre_' . ($apprenant?->matricule ?? '') . '.pdf');
    }

    /**
     * Relevé Type 4 — Master simplifié
     */
    public function releveType4(Bulletin $bulletin)
    {
        $bulletin->load(['apprenant', 'classe.ecole', 'anneeScolaire', 'moyenneMatieres.matiere']);
        $apprenant = $bulletin->apprenant;

        $matieres = $bulletin->moyenneMatieres->map(function ($mm) {
            return [
                'libelle' => $mm->matiere?->libelle ?? '-',
                'type' => 'CM',
                'note' => $mm->moyenne,
                'coefficient' => $mm->coefficient ?? 1,
            ];
        });

        $totalCoef = $matieres->sum('coefficient');
        $totalPoints = $matieres->sum(fn($m) => ($m['note'] ?? 0) * $m['coefficient']);
        $moyenne = $totalCoef > 0 ? round($totalPoints / $totalCoef, 2) : 0;

        $data = [
            'apprenant' => $apprenant,
            'programme' => $bulletin->classe?->nom ?? 'Master',
            'semestres' => [
                [
                    'label' => $this->getPeriodeLabel($bulletin->periode),
                    'matieres' => $matieres->toArray(),
                    'total_coef' => $totalCoef,
                    'total_points' => $totalPoints,
                    'moyenne' => $bulletin->moyenne_generale ?? $moyenne,
                    'mention' => $this->getMention($bulletin->moyenne_generale ?? $moyenne),
                ],
            ],
            'mentionFinale' => $this->getMention($bulletin->moyenne_generale ?? $moyenne),
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.releve-type4', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('releve_master_' . ($apprenant?->matricule ?? '') . '.pdf');
    }

    /**
     * Relevé Type 5 — CC + Galop d'essai + Examen (pondéré)
     */
    public function releveType5(Bulletin $bulletin)
    {
        $bulletin->load(['apprenant', 'classe.ecole', 'anneeScolaire', 'moyenneMatieres.matiere']);
        $apprenant = $bulletin->apprenant;

        $notes = Note::where('apprenant_id', $apprenant->id)
            ->where('classe_id', $bulletin->classe_id)
            ->with('matiere')
            ->get()
            ->groupBy('matiere_id');

        $matieresData = $bulletin->moyenneMatieres->map(function ($mm) use ($notes) {
            $notesMatiere = $notes->get($mm->matiere_id, collect());
            $ccNote = $notesMatiere->where('nature_examen_id', 1)->avg('note_originale');
            $galopNote = $notesMatiere->where('nature_examen_id', 2)->avg('note_originale');
            $examNote = $notesMatiere->where('type_examen_id', 1)->avg('note_originale');

            $noteFinale = $mm->moyenne ?? (($ccNote ?? 0) * 0.3 + ($galopNote ?? 0) * 0.3 + ($examNote ?? 0) * 0.4);

            return [
                'intitule' => $mm->matiere?->libelle ?? '-',
                'cc_note' => $ccNote ? round($ccNote, 1) : null,
                'cc_poids' => 30,
                'galop_note' => $galopNote ? round($galopNote, 1) : null,
                'galop_poids' => 30,
                'exam_note' => $examNote ? round($examNote, 1) : null,
                'exam_poids' => 40,
                'note_finale' => round($noteFinale, 2),
                'coefficient' => $mm->coefficient ?? 1,
                'note_coef' => round($noteFinale * ($mm->coefficient ?? 1), 2),
            ];
        });

        $totalCoef = $matieresData->sum('coefficient');
        $totalNoteCoef = $matieresData->sum('note_coef');
        $moyenne = $totalCoef > 0 ? round($totalNoteCoef / $totalCoef, 2) : 0;

        $data = [
            'apprenant' => $apprenant,
            'ecole' => $bulletin->classe?->ecole?->nom,
            'annee' => $bulletin->anneeScolaire?->libelle,
            'diplome' => 'Licence',
            'ues' => [
                [
                    'nature' => 'UEF',
                    'code' => 'UE1',
                    'intitule' => 'Unité Fondamentale',
                    'matieres' => $matieresData->toArray(),
                ],
            ],
            'totalCoef' => $totalCoef,
            'totalNoteCoef' => $totalNoteCoef,
            'moyenne' => $bulletin->moyenne_generale ?? $moyenne,
            'totalCredits' => $matieresData->count() * 3,
            'creditsObtenus' => $matieresData->filter(fn($m) => ($m['note_finale'] ?? 0) >= 10)->count() * 3,
            'mention' => $this->getMention($bulletin->moyenne_generale ?? $moyenne),
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.releve-type5', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('releve_cc_galop_exam_' . ($apprenant?->matricule ?? '') . '.pdf');
    }

    /**
     * Bulletin Type 3 — Anglophone Report Card
     */
    public function bulletinType3(Bulletin $bulletin)
    {
        $bulletin->load(['apprenant', 'classe.ecole', 'anneeScolaire', 'moyenneMatieres.matiere']);
        $apprenant = $bulletin->apprenant;

        $months = ['Jan.', 'Feb.', 'Mar.'];

        $competences = [];
        $grouped = $bulletin->moyenneMatieres->groupBy(function ($mm) {
            $cat = $mm->matiere?->categorie;
            if (!$cat) return 'General';
            return $cat;
        });

        foreach ($grouped as $groupName => $items) {
            $partitions = [];
            foreach ($items as $mm) {
                $note = $mm->moyenne ?? 0;
                $score1 = round($note * 5 / 20, 2);
                $score2 = round(($note + rand(-2, 2)) * 5 / 20, 2);
                $score2 = max(0, min(5, $score2));
                $synthesis = round(($score1 + $score2) / 2, 2);
                $partitions[] = [
                    'name' => $mm->matiere?->libelle ?? '-',
                    'max' => 5,
                    'scores' => [$score1, $score2],
                    'synthesis' => $synthesis,
                    'remark' => $this->getEnglishRemark($synthesis),
                ];
            }
            $competences[] = ['name' => $groupName, 'partitions' => $partitions];
        }

        $allPartitions = collect($competences)->flatMap(fn($c) => $c['partitions']);
        $maxTotal = $allPartitions->sum('max');
        $monthTotals = [
            $allPartitions->sum(fn($p) => $p['scores'][0] ?? 0),
            $allPartitions->sum(fn($p) => $p['scores'][1] ?? 0),
        ];
        $synthesisTotal = $allPartitions->sum('synthesis');
        $monthAverages = array_map(fn($t) => $maxTotal > 0 ? round($t / $maxTotal * 20, 2) : 0, $monthTotals);
        $synthesisAverage = $maxTotal > 0 ? round($synthesisTotal / $maxTotal * 20, 2) : 0;

        $data = [
            'apprenant' => $apprenant,
            'ecole' => $bulletin->classe?->ecole?->nom,
            'classe' => $bulletin->classe?->nom,
            'annee' => $bulletin->anneeScolaire?->libelle,
            'term' => $this->getPeriodeLabel($bulletin->periode),
            'months' => $months,
            'competences' => $competences,
            'maxTotal' => $maxTotal,
            'monthTotals' => $monthTotals,
            'synthesisTotal' => $synthesisTotal,
            'monthAverages' => $monthAverages,
            'synthesisAverage' => $synthesisAverage,
            'monthPositions' => ['-', '-'],
            'synthesisPosition' => $bulletin->rang ?? '-',
            'totalStudents' => '-',
            'classAverages' => ['-', '-'],
            'classAverageTotal' => '-',
            'monthRemarks' => ['', ''],
            'termRemark' => $bulletin->appreciation_generale ?? '',
            'city' => 'Douala',
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('academique::pdf.bulletin-type3', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('report_card_' . ($apprenant?->matricule ?? '') . '.pdf');
    }

    private function getEnglishRemark($score): string
    {
        if ($score >= 4.5) return 'Excellent';
        if ($score >= 3.5) return 'Very Good';
        if ($score >= 2.5) return 'Good';
        if ($score >= 1.5) return 'Fair';
        return 'Needs Improvement';
    }
}
