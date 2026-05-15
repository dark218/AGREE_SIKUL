<?php

namespace Modules\Parametrage\Console;

use Illuminate\Console\Command;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\Commune;
use Modules\Parametrage\Entities\Departement;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Institution;
use Modules\Parametrage\Entities\Quartier;
use Modules\Parametrage\Entities\Region;

/**
 * php artisan parametrage:match-locations
 *
 * Migration best-effort des anciens champs string (quartier, commune, departement, region)
 * vers les nouvelles FK (quartier_id, commune_id, ...).
 *
 * Pour chaque enregistrement Institution/Campus/Ecole :
 *   - si la FK est null mais la string existe et match EXACT le libellé d'un record → set la FK
 *   - sinon, on laisse tel quel
 *
 * Idempotent : peut être lancé plusieurs fois sans risque.
 */
class MatchLocationsCommand extends Command
{
    protected $signature = 'parametrage:match-locations
                            {--dry-run : Affiche les changements sans les appliquer}
                            {--model=all : Cible un modèle spécifique (institution|campus|ecole|all)}';

    protected $description = 'Tente de matcher les anciennes valeurs string de localisation vers les nouvelles FK';

    /** @var array{quartier: array, commune: array, departement: array, region: array} */
    private array $caches = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $target = $this->option('model');

        $this->info("🔍 Best-effort matching des localisations" . ($dryRun ? ' [DRY-RUN]' : ''));
        $this->line('');

        $this->buildCaches();

        $totals = ['updated' => 0, 'skipped' => 0];

        if (in_array($target, ['all', 'institution'])) {
            $this->processModel(Institution::class, 'Institution', $dryRun, $totals);
        }
        if (in_array($target, ['all', 'campus'])) {
            $this->processModel(Campus::class, 'Campus', $dryRun, $totals);
        }
        if (in_array($target, ['all', 'ecole'])) {
            $this->processModel(Ecole::class, 'Ecole', $dryRun, $totals);
        }

        $this->line('');
        $this->info("✅ Terminé. Mis à jour : {$totals['updated']} | Ignorés : {$totals['skipped']}");
        if ($dryRun) {
            $this->warn("⚠️ Mode dry-run : aucun changement n'a été persisté. Relancer sans --dry-run pour appliquer.");
        }

        return self::SUCCESS;
    }

    private function buildCaches(): void
    {
        $this->caches['quartier']   = $this->buildLookup(Quartier::class);
        $this->caches['commune']    = $this->buildLookup(Commune::class);
        $this->caches['departement']= $this->buildLookup(Departement::class);
        $this->caches['region']     = $this->buildLookup(Region::class);

        $this->line('  Cache : '
            . count($this->caches['quartier']) . ' quartiers, '
            . count($this->caches['commune']) . ' communes, '
            . count($this->caches['departement']) . ' départements, '
            . count($this->caches['region']) . ' régions');
    }

    /**
     * @return array<string, int> Map [libelle_lowercased => id]
     */
    private function buildLookup(string $modelClass): array
    {
        return $modelClass::pluck('id', 'libelle')
            ->mapWithKeys(fn ($id, $libelle) => [mb_strtolower(trim($libelle)) => $id])
            ->all();
    }

    private function processModel(string $modelClass, string $label, bool $dryRun, array &$totals): void
    {
        $this->line('');
        $this->info("→ Traitement : {$label}");

        $modelClass::query()->chunk(200, function ($records) use ($dryRun, &$totals) {
            foreach ($records as $rec) {
                $changes = $this->resolveChanges($rec);
                if (empty($changes)) {
                    $totals['skipped']++;
                    continue;
                }

                $this->line("   #{$rec->id} {$rec->nom} → " . implode(', ', array_map(
                    fn ($k, $v) => "$k=$v",
                    array_keys($changes),
                    array_values($changes)
                )));

                if (!$dryRun) {
                    foreach ($changes as $col => $val) {
                        $rec->{$col} = $val;
                    }
                    $rec->saveQuietly();
                }
                $totals['updated']++;
            }
        });
    }

    /**
     * Calcule les colonnes FK à mettre à jour pour un enregistrement donné.
     */
    private function resolveChanges($rec): array
    {
        $changes = [];
        $pairs = [
            ['string' => 'quartier',    'fk' => 'quartier_id',    'cache' => 'quartier'],
            ['string' => 'commune',     'fk' => 'commune_id',     'cache' => 'commune'],
            ['string' => 'departement', 'fk' => 'departement_id', 'cache' => 'departement'],
            ['string' => 'region',      'fk' => 'region_id',      'cache' => 'region'],
        ];

        foreach ($pairs as $p) {
            if (!\Schema::hasColumn($rec->getTable(), $p['string'])
                || !\Schema::hasColumn($rec->getTable(), $p['fk'])) {
                continue;
            }
            if ($rec->{$p['fk']}) continue; // déjà rempli
            $strVal = $rec->{$p['string']} ?? null;
            if (!$strVal) continue;

            $key = mb_strtolower(trim($strVal));
            if (isset($this->caches[$p['cache']][$key])) {
                $changes[$p['fk']] = $this->caches[$p['cache']][$key];
            }
        }

        return $changes;
    }
}
