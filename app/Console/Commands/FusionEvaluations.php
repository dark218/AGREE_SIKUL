<?php

namespace App\Console\Commands;

use App\Services\EvaluationsFusionService;
use Illuminate\Console\Command;

/**
 * Fusion évaluations — copie Devoir/ExamenEnLigne/PlanificationExamen
 * dans `evaluations` avec type discriminant.
 *
 * Usage :
 *   php artisan evaluations:fusion --dry-run
 *   php artisan evaluations:fusion
 *   php artisan evaluations:fusion --source=devoirs
 */
class FusionEvaluations extends Command
{
    protected $signature = 'evaluations:fusion
                            {--dry-run : Simule la fusion sans écrire}
                            {--source= : Cible spécifique (devoirs|examens_en_ligne|planifications|all)}';

    protected $description = 'Fusionne Devoir/ExamenEnLigne/PlanificationExamen dans `evaluations` (§10.1)';

    public function handle(EvaluationsFusionService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $source = $this->option('source') ?: 'all';

        $this->info(sprintf(
            'Fusion évaluations — mode: %s | source: %s',
            $dryRun ? 'DRY RUN' : 'RÉEL',
            $source
        ));

        if (!$dryRun && !$this->confirm('Continuer avec la fusion RÉELLE ?', true)) {
            $this->warn('Annulé.');
            return self::SUCCESS;
        }

        $stats = match ($source) {
            'devoirs'          => ['sources' => ['devoirs'          => $service->fusionDevoirs($dryRun)]],
            'examens_en_ligne' => ['sources' => ['examens_en_ligne' => $service->fusionExamensEnLigne($dryRun)]],
            'planifications'   => ['sources' => ['planifications'   => $service->fusionPlanifications($dryRun)]],
            default            => $service->fusionAll($dryRun),
        };

        $this->line('');
        $this->info('Statistiques :');
        foreach ($stats['sources'] ?? [] as $key => $count) {
            $this->line(sprintf('  %-20s : %d ligne(s) %s',
                $key, $count, $dryRun ? '(simulées)' : 'insérées'
            ));
        }

        return self::SUCCESS;
    }
}
