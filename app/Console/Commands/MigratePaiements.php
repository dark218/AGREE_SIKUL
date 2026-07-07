<?php

namespace App\Console\Commands;

use App\Services\PaiementsMigrationService;
use Illuminate\Console\Command;

/**
 * Migration paiements — commande CLI pour exécuter PaiementsMigrationService.
 *
 * Usage :
 *   php artisan paiements:migrate --dry-run   # simule, affiche stats
 *   php artisan paiements:migrate             # exécute réellement
 *   php artisan paiements:migrate --source=versements   # cible une source
 */
class MigratePaiements extends Command
{
    protected $signature = 'paiements:migrate
                            {--dry-run : Simule la migration sans écrire}
                            {--source= : Cible spécifique (versements|achats_depenses|autres_revenus|salaires|all)}';

    protected $description = 'Migre les slots de paiements hardcodés vers paiements polymorphe (§10.4)';

    public function handle(PaiementsMigrationService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $source = $this->option('source') ?: 'all';

        $this->info(sprintf(
            'Migration paiements — mode: %s | source: %s',
            $dryRun ? 'DRY RUN' : 'RÉEL',
            $source
        ));

        if (!$dryRun && !$this->confirm('Continuer avec la migration RÉELLE ?', true)) {
            $this->warn('Annulé.');
            return self::SUCCESS;
        }

        $stats = match ($source) {
            'versements'      => ['sources' => ['versements'      => $service->migrateVersements($dryRun)]],
            'achats_depenses' => ['sources' => ['achats_depenses' => $service->migrateAchatsDepenses($dryRun)]],
            'autres_revenus'  => ['sources' => ['autres_revenus'  => $service->migrateAutresRevenus($dryRun)]],
            'salaires'        => ['sources' => ['salaires'        => $service->migrateSalaireAvances($dryRun)]],
            default           => $service->migrateAll($dryRun),
        };

        $this->line('');
        $this->info('Statistiques :');
        foreach ($stats['sources'] ?? [] as $key => $count) {
            $this->line(sprintf('  %-25s : %d ligne(s) %s',
                $key, $count, $dryRun ? '(simulées)' : 'insérées'
            ));
        }

        $this->line('');
        if ($dryRun) {
            $this->comment('Relancez sans --dry-run pour exécuter réellement.');
        } else {
            $this->info('✓ Migration terminée.');
        }

        return self::SUCCESS;
    }
}
