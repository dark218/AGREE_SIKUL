<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * EvaluationsFusionService — copie les Devoir/ExamenEnLigne/PlanificationExamen
 * dans `evaluations` avec discriminant `type` (§10.1 du plan).
 *
 * Idempotent : chaque enregistrement copié reçoit une `source_system` marqueur
 * (`fusion:devoir`, `fusion:examen_en_ligne`, `fusion:planification`) et un
 * `external_id` = source_id, ce qui permet de skip les enregistrements déjà
 * copiés.
 *
 * Les tables source RESTENT en place après cette migration — les controllers
 * peuvent continuer à écrire dedans le temps que les Vue/tests soient tous
 * repointés sur Evaluation. La suppression finale des tables devoirs/
 * examens_en_ligne/planification_examens sera une phase séparée.
 */
class EvaluationsFusionService
{
    public array $stats = [];

    public function fusionAll(bool $dryRun = true): array
    {
        $this->stats = ['dry_run' => $dryRun, 'sources' => []];
        $this->fusionDevoirs($dryRun);
        $this->fusionExamensEnLigne($dryRun);
        $this->fusionPlanifications($dryRun);
        return $this->stats;
    }

    public function fusionDevoirs(bool $dryRun = true): int
    {
        if (!Schema::hasTable('devoirs')) return 0;

        $rows = DB::table('devoirs')->whereNull('deleted_at')->get();
        $count = 0;

        foreach ($rows as $d) {
            if ($this->alreadyFused('fusion:devoir', $d->id)) continue;

            $data = [
                'type'         => 'devoir',
                'titre'        => $d->titre ?? 'Devoir sans titre',
                'description'  => $d->description ?? null,
                'classe_id'    => $d->classe_id ?? null,
                'matiere_id'   => $d->matiere_id ?? null,
                'cours_id'     => $d->cours_id ?? null,
                'date'         => $d->date_remise ?? null,
                'date_debut'   => $d->date_debut ?? null,
                'date_fin'     => $d->date_fin ?? null,
                'coefficient'  => $d->coefficient ?? 1,
                'sur'          => 20,
                'fichier_enonce_id' => $d->fichier_enonce_id ?? null,
                'statut'       => $d->statut ?? 'actif',
                'source_system' => 'fusion:devoir',
                'external_id'  => (string) $d->id,
                'created_at'   => $d->created_at ?? now(),
                'updated_at'   => now(),
            ];

            if (!$dryRun) DB::table('evaluations')->insert($data);
            $count++;
        }

        $this->stats['sources']['devoirs'] = $count;
        return $count;
    }

    public function fusionExamensEnLigne(bool $dryRun = true): int
    {
        if (!Schema::hasTable('examens_en_ligne')) return 0;

        $rows = DB::table('examens_en_ligne')->whereNull('deleted_at')->get();
        $count = 0;

        foreach ($rows as $e) {
            if ($this->alreadyFused('fusion:examen_en_ligne', $e->id)) continue;

            $data = [
                'type'                 => 'examen_en_ligne',
                'titre'                => $e->titre ?? 'Examen en ligne',
                'description'          => $e->description ?? null,
                'instructions'         => $e->instructions ?? null,
                'classe_id'            => $e->classe_id ?? null,
                'matiere_id'           => $e->matiere_id ?? null,
                'enseignant_id'        => $e->enseignant_id ?? null,
                'date_debut'           => $e->date_debut ?? null,
                'date_fin'             => $e->date_fin ?? null,
                'duree_minutes'        => $e->duree_minutes ?? null,
                'note_maximum'         => $e->note_maximum ?? null,
                'note_minimum_passage' => $e->note_minimum_passage ?? null,
                'nombre_tentatives'    => $e->nombre_tentatives ?? null,
                'melange_questions'    => $e->melange_questions ?? false,
                'melange_reponses'     => $e->melange_reponses ?? false,
                'retour_arriere'       => $e->retour_arriere ?? true,
                'afficher_resultat'    => $e->afficher_resultat ?? true,
                'afficher_correction'  => $e->afficher_correction ?? false,
                'mot_de_passe'         => $e->mot_de_passe ?? null, // déjà hashé côté source
                'coefficient'          => 1,
                'sur'                  => $e->note_maximum ?? 20,
                'statut'               => in_array($e->etat ?? 'brouillon', ['publie', 'en_cours', 'termine', 'corrige']) ? 'actif' : 'inactif',
                'source_system'        => 'fusion:examen_en_ligne',
                'external_id'          => (string) $e->id,
                'created_at'           => $e->created_at ?? now(),
                'updated_at'           => now(),
            ];

            if (!$dryRun) DB::table('evaluations')->insert($data);
            $count++;
        }

        $this->stats['sources']['examens_en_ligne'] = $count;
        return $count;
    }

    public function fusionPlanifications(bool $dryRun = true): int
    {
        if (!Schema::hasTable('planification_examens')) return 0;

        $rows = DB::table('planification_examens')->whereNull('deleted_at')->get();
        $count = 0;

        foreach ($rows as $p) {
            if ($this->alreadyFused('fusion:planification', $p->id)) continue;

            $data = [
                'type'              => 'planification',
                'titre'             => "Planification examen #{$p->id}",
                'classe_id'         => $p->classe_id ?? null,
                'matiere_id'        => $p->matiere_id ?? null,
                'nature_examen_id'  => $p->nature_examen_id ?? null,
                'type_examen_id'    => $p->type_examen_id ?? null,
                'date'              => $p->date ?? null,
                'heure_debut'       => $p->heure_debut ?? null,
                'heure_fin'         => $p->heure_fin ?? null,
                'duree'             => (int) round(($p->duree ?? 0) * 60), // décimal heures → minutes
                'coefficient'       => 1,
                'sur'               => 20,
                'statut'            => $p->etat ?? 'actif',
                'source_system'     => 'fusion:planification',
                'external_id'       => (string) $p->id,
                'created_at'        => $p->created_at ?? now(),
                'updated_at'        => now(),
            ];

            if (!$dryRun) DB::table('evaluations')->insert($data);
            $count++;
        }

        $this->stats['sources']['planifications'] = $count;
        return $count;
    }

    private function alreadyFused(string $sourceSystem, int $sourceId): bool
    {
        return DB::table('evaluations')
            ->where('source_system', $sourceSystem)
            ->where('external_id', (string) $sourceId)
            ->exists();
    }
}
