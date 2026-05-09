<?php

namespace Modules\Academique\Services;

use Modules\Academique\Entities\Note;
use Modules\Academique\Entities\Bulletin;
use Modules\Academique\Entities\MoyenneMatiere;
use Modules\Academique\Entities\Matiere;
use Illuminate\Support\Collection;

/**
 * Service de calcul académique robuste
 * Gère les calculs de moyennes conformément aux standards académiques
 */
class AcademicCalculationService
{
    /**
     * Calcule la moyenne par matière pour un apprenant sur une période
     *
     * Formule: moyenne_matiere = SUM(notes_validees) / COUNT(notes_validees)
     * Limitation: Seulement les notes avec statut "validee"
     *
     * @param int $apprenantId
     * @param int $matiereId
     * @param int $periodeId
     * @return float|null Moyenne calculée ou null si pas de notes
     */
    public function calculateMoyenneMatiere(int $apprenantId, int $matiereId, int $periodeId): ?float
    {
        try {
            $notes = Note::where('apprenant_id', $apprenantId)
                ->where('matiere_id', $matiereId)
                ->where('periode_id', $periodeId)
                ->where('statut', 'validee')
                ->get();

            if ($notes->isEmpty()) {
                return null;
            }

            // Moyenne simple: somme des notes / nombre de notes
            $sum = $notes->sum('note');
            $count = $notes->count();

            if ($count === 0) {
                return null;
            }

            return round($sum / $count, 2);
        } catch (\Exception $e) {
            \Log::error('AcademicCalculationService::calculateMoyenneMatiere', [
                'apprenant_id' => $apprenantId,
                'matiere_id' => $matiereId,
                'periode_id' => $periodeId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Calcule la moyenne générale pondérée par les coefficients des matières
     *
     * Formule: moyenne_generale = SUM(moyenne_matiere × coefficient_matiere) / SUM(coefficients)
     *
     * @param int $bulletinId
     * @return float|null Moyenne générale ou null si pas de moyennes
     */
    public function calculateMoyenneGenerale(int $bulletinId): ?float
    {
        try {
            $bulletin = Bulletin::findOrFail($bulletinId);

            // Charger les moyennes matieres avec leurs coefficients
            $moyennesMatieres = $bulletin->moyenneMatieres()
                ->with('matiere')
                ->get();

            if ($moyennesMatieres->isEmpty()) {
                return null;
            }

            // Somme pondérée: SUM(moyenne × coefficient)
            $totalPondere = $moyennesMatieres->sum(function ($mm) {
                return ($mm->moyenne ?? 0) * ($mm->coefficient ?? 0);
            });

            // Somme des coefficients
            $totalCoefficient = $moyennesMatieres->sum('coefficient');

            // Protection contre la division par zéro
            if ($totalCoefficient === 0 || $totalCoefficient === 0.0) {
                return null;
            }

            return round($totalPondere / $totalCoefficient, 2);
        } catch (\Exception $e) {
            \Log::error('AcademicCalculationService::calculateMoyenneGenerale', [
                'bulletin_id' => $bulletinId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Calcule le rang de l'apprenant dans sa classe
     *
     * Critique: Tous les bulletins de la classe pour la même période
     * Trie par moyenne_generale DESC
     *
     * @param int $bulletinId
     * @return int|null Rang de l'apprenant (1er, 2e, etc.) ou null
     */
    public function calculateRang(int $bulletinId): ?int
    {
        try {
            $bulletin = Bulletin::findOrFail($bulletinId);

            // Bulletins de la même classe, même période, même année scolaire
            $bulletins = Bulletin::where('classe_id', $bulletin->classe_id)
                ->where('periode', $bulletin->periode)
                ->where('annee_scolaire_id', $bulletin->annee_scolaire_id)
                ->where('statut', 'actif')
                ->whereNotNull('moyenne_generale')
                ->orderByDesc('moyenne_generale')
                ->get();

            if ($bulletins->isEmpty()) {
                return null;
            }

            // Trouver la position du bulletin courant
            $rang = $bulletins->search(function ($b) use ($bulletinId) {
                return $b->id === $bulletinId;
            });

            // search() retourne false si non trouvé, sinon retourne l'index (0-based)
            if ($rang === false) {
                return null;
            }

            return $rang + 1; // Convertir index 0-based en rang 1-based
        } catch (\Exception $e) {
            \Log::error('AcademicCalculationService::calculateRang', [
                'bulletin_id' => $bulletinId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Génère les données complètes pour créer un bulletin
     *
     * Automatise:
     * 1. Récupération de toutes les notes de l'apprenant (période + classe)
     * 2. Calcul des moyennes par matière
     * 3. Création des MoyenneMatieres
     * 4. Calcul de la moyenne générale
     * 5. Calcul du rang
     *
     * @param int $apprenantId
     * @param int $classeId
     * @param int $periodeId
     * @param int $anneeScolaireId
     * @return array Données formatées pour création/mise à jour bulletin
     */
    public function generateBulletinData(
        int $apprenantId,
        int $classeId,
        int $periodeId,
        int $anneeScolaireId
    ): array {
        try {
            // 1. Récupérer toutes les notes validées de l'apprenant pour cette période et classe
            $notesRaw = Note::where('apprenant_id', $apprenantId)
                ->where('classe_id', $classeId)
                ->where('periode_id', $periodeId)
                ->where('statut', 'validee')
                ->with('matiere')
                ->get();

            if ($notesRaw->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Aucune note validée trouvée pour cet apprenant',
                    'moyennes_matieres' => [],
                    'moyenne_generale' => null,
                ];
            }

            // 2. Grouper les notes par matière et calculer les moyennes
            $matieres = $notesRaw->groupBy('matiere_id');
            $moyennesMatieres = [];
            $totalPondere = 0;
            $totalCoefficient = 0;

            foreach ($matieres as $matiereId => $notesByMatiere) {
                // Calculer la moyenne pour cette matière
                $moyenneMatiere = round(
                    $notesByMatiere->sum('note') / $notesByMatiere->count(),
                    2
                );

                // Récupérer le coefficient de la matière
                $matiere = Matiere::findOrFail($matiereId);
                $coefficient = $matiere->coefficient ?? 1;

                // Stocker pour création MoyenneMatiere
                $moyennesMatieres[] = [
                    'matiere_id' => $matiereId,
                    'moyenne' => $moyenneMatiere,
                    'coefficient' => $coefficient,
                    'appreciation' => $this->determineAppreciation($moyenneMatiere),
                ];

                // Accumuler pour moyenne générale
                $totalPondere += $moyenneMatiere * $coefficient;
                $totalCoefficient += $coefficient;
            }

            // 3. Calculer la moyenne générale
            $moyenneGenerale = null;
            if ($totalCoefficient > 0) {
                $moyenneGenerale = round($totalPondere / $totalCoefficient, 2);
            }

            return [
                'success' => true,
                'message' => 'Données calculées avec succès',
                'moyennes_matieres' => $moyennesMatieres,
                'moyenne_generale' => $moyenneGenerale,
                'stats' => [
                    'nombre_notes' => $notesRaw->count(),
                    'nombre_matieres' => $matieres->count(),
                    'total_coefficient' => $totalCoefficient,
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('AcademicCalculationService::generateBulletinData', [
                'apprenant_id' => $apprenantId,
                'classe_id' => $classeId,
                'periode_id' => $periodeId,
                'annee_scolaire_id' => $anneeScolaireId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du calcul: ' . $e->getMessage(),
                'moyennes_matieres' => [],
                'moyenne_generale' => null,
            ];
        }
    }

    /**
     * Détermine l'appréciation basée sur la moyenne
     *
     * Grille:
     * - [18-20]: Excellent
     * - [16-17.99]: Bien
     * - [14-15.99]: Assez
     * - [12-13.99]: Moyen
     * - [0-11.99]: Faible
     *
     * @param float $moyenne
     * @return string Appréciation qualitative
     */
    public function determineAppreciation(float $moyenne): string
    {
        if ($moyenne >= 18) {
            return 'excellent';
        } elseif ($moyenne >= 16) {
            return 'bien';
        } elseif ($moyenne >= 14) {
            return 'assez';
        } elseif ($moyenne >= 12) {
            return 'moyen';
        } else {
            return 'faible';
        }
    }

    /**
     * Récupère toutes les notes d'un apprenant pour une période
     * Utile pour affichage/audit
     *
     * @param int $apprenantId
     * @param int $periodeId
     * @return Collection
     */
    public function getApprenantNotesByPeriode(int $apprenantId, int $periodeId): Collection
    {
        return Note::where('apprenant_id', $apprenantId)
            ->where('periode_id', $periodeId)
            ->where('statut', 'validee')
            ->with(['matiere', 'evaluation'])
            ->orderBy('matiere_id')
            ->get();
    }

    /**
     * Récupère les statistiques d'une classe pour une période
     * Utile pour rapports/analyses
     *
     * @param int $classeId
     * @param int $periodeId
     * @param int $anneeScolaireId
     * @return array Statistiques
     */
    public function getClasseStatistics(
        int $classeId,
        int $periodeId,
        int $anneeScolaireId
    ): array {
        $bulletins = Bulletin::where('classe_id', $classeId)
            ->where('periode', $periodeId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'actif')
            ->get();

        $moyennes = $bulletins->pluck('moyenne_generale')->filter();

        return [
            'nombre_apprenants' => $bulletins->count(),
            'nombre_bulletins_complets' => $moyennes->count(),
            'moyenne_classe' => $moyennes->isEmpty() ? null : round($moyennes->avg(), 2),
            'max' => $moyennes->isEmpty() ? null : $moyennes->max(),
            'min' => $moyennes->isEmpty() ? null : $moyennes->min(),
            'median' => $this->calculateMedian($moyennes->toArray()),
        ];
    }

    /**
     * Calcule la médiane d'un tableau
     *
     * @param array $values
     * @return float|null
     */
    private function calculateMedian(array $values): ?float
    {
        if (empty($values)) {
            return null;
        }

        sort($values);
        $count = count($values);
        $middle = intval($count / 2);

        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return round(($values[$middle - 1] + $values[$middle]) / 2, 2);
    }
}
