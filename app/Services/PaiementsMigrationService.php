<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PaiementsMigrationService — migre les slots hardcodés de Versement,
 * AchatDepense, AutreRevenu et Salaire vers la table `paiements` polymorphe.
 *
 * Chaque méthode `migrateFromXxx()` :
 *   - Lit les enregistrements source
 *   - Parcourt les slots 1..N
 *   - Insère une ligne dans `paiements` avec (payable_type, payable_id)
 *   - Idempotente : skip si déjà migré (détection via reference unique)
 *
 * Usage :
 *   $svc = new PaiementsMigrationService();
 *   $svc->migrateAll(dryRun: true);   // vérif avant
 *   $svc->migrateAll(dryRun: false);  // exécution réelle
 *
 * Rollback : delete de paiements par payable_type + backup source.
 */
class PaiementsMigrationService
{
    public array $stats = [];

    public function migrateAll(bool $dryRun = true): array
    {
        $this->stats = ['dry_run' => $dryRun, 'sources' => []];

        $this->migrateVersements($dryRun);
        $this->migrateAchatsDepenses($dryRun);
        $this->migrateAutresRevenus($dryRun);
        $this->migrateSalaireAvances($dryRun);

        return $this->stats;
    }

    /**
     * Versement : 12 slots (nature_versement_1..12 + montant_versement_1..12).
     */
    public function migrateVersements(bool $dryRun = true): int
    {
        if (!$this->hasTable('versements')) return 0;

        $rows = DB::table('versements')->whereNull('deleted_at')->get();
        $count = 0;

        foreach ($rows as $v) {
            for ($i = 1; $i <= 12; $i++) {
                $nature  = $v->{"nature_versement_$i"}  ?? null;
                $montant = $v->{"montant_versement_$i"} ?? null;
                if (!$nature && !$montant) continue;

                $ref = "V:{$v->id}:{$i}";
                if ($this->alreadyMigrated($ref)) continue;

                $data = [
                    'payable_type'  => 'Modules\\Finances\\Entities\\Versement',
                    'payable_id'    => $v->id,
                    'apprenant_id'  => $v->apprenant_id ?? null,
                    'montant_cents' => (int) round(((float) ($montant ?? 0)) * 100),
                    'mode_paiement' => $this->normalizeMode($nature),
                    'reference'     => $ref,
                    'date_paiement' => $v->created_at ? substr($v->created_at, 0, 10) : now()->toDateString(),
                    'recu_par'      => null,
                    'source_system' => 'agree_sikul_migration_versement',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                if (!$dryRun) DB::table('paiements')->insert($data);
                $count++;
            }
        }
        $this->stats['sources']['versements'] = $count;
        return $count;
    }

    /**
     * AchatDepense : 6 slots (date_paiement_1..6 + montant_paiement_1..6).
     */
    public function migrateAchatsDepenses(bool $dryRun = true): int
    {
        if (!$this->hasTable('achats_depenses')) return 0;

        $rows = DB::table('achats_depenses')->whereNull('deleted_at')->get();
        $count = 0;

        foreach ($rows as $a) {
            for ($i = 1; $i <= 6; $i++) {
                $date    = $a->{"date_paiement_$i"}    ?? null;
                $montant = $a->{"montant_paiement_$i"} ?? null;
                if (!$date && !$montant) continue;

                $ref = "A:{$a->id}:{$i}";
                if ($this->alreadyMigrated($ref)) continue;

                $data = [
                    'payable_type'  => 'Modules\\Finances\\Entities\\AchatDepense',
                    'payable_id'    => $a->id,
                    'apprenant_id'  => null,
                    'montant_cents' => (int) round(((float) ($montant ?? 0)) * 100),
                    'mode_paiement' => $this->normalizeMode($a->mode_paiement ?? 'espece'),
                    'reference'     => $ref,
                    'date_paiement' => $date ? substr($date, 0, 10) : now()->toDateString(),
                    'recu_par'      => null,
                    'source_system' => 'agree_sikul_migration_achat',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                if (!$dryRun) DB::table('paiements')->insert($data);
                $count++;
            }
        }
        $this->stats['sources']['achats_depenses'] = $count;
        return $count;
    }

    /**
     * AutreRevenu : slots {uniforme, tenue_mercredi, tenue_sport, autre1..6}.
     * Cas "revenu" ≠ paiement de frais — on utilise `payable_type` comme
     * discriminant et on stocke le montant en positif (crédit).
     */
    public function migrateAutresRevenus(bool $dryRun = true): int
    {
        if (!$this->hasTable('autres_revenus')) return 0;

        $rows = DB::table('autres_revenus')->whereNull('deleted_at')->get();
        $count = 0;
        $nommes = ['uniforme', 'tenue_mercredi', 'tenue_sport'];
        $autres = ['autre1', 'autre2', 'autre3', 'autre4', 'autre5', 'autre6'];

        foreach ($rows as $r) {
            foreach (array_merge($nommes, $autres) as $slot) {
                $montant = $r->$slot ?? null;
                if (!$montant) continue;

                $ref = "R:{$r->id}:{$slot}";
                if ($this->alreadyMigrated($ref)) continue;

                $data = [
                    'payable_type'  => 'Modules\\Finances\\Entities\\AutreRevenu',
                    'payable_id'    => $r->id,
                    'apprenant_id'  => null,
                    'montant_cents' => (int) round(((float) $montant) * 100),
                    'mode_paiement' => 'espece', // par défaut, pas de nature en source
                    'reference'     => $ref,
                    'date_paiement' => $r->created_at ? substr($r->created_at, 0, 10) : now()->toDateString(),
                    'recu_par'      => null,
                    'source_system' => 'agree_sikul_migration_revenu',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                if (!$dryRun) DB::table('paiements')->insert($data);
                $count++;
            }
        }
        $this->stats['sources']['autres_revenus'] = $count;
        return $count;
    }

    /**
     * Salaire : 4 avances (avance1..4 + date_avance1..4) + paiement_integral.
     */
    public function migrateSalaireAvances(bool $dryRun = true): int
    {
        if (!$this->hasTable('salaires')) return 0;

        $rows = DB::table('salaires')->whereNull('deleted_at')->get();
        $count = 0;

        foreach ($rows as $s) {
            // Paiement intégral
            if (($s->paiement_integral ?? 0) > 0) {
                $ref = "S:{$s->id}:integral";
                if (!$this->alreadyMigrated($ref)) {
                    $data = $this->makeSalaireRow($s, $ref, $s->paiement_integral, $s->date_paiement_integral, 'integral');
                    if (!$dryRun) DB::table('paiements')->insert($data);
                    $count++;
                }
            }
            // 4 avances
            for ($i = 1; $i <= 4; $i++) {
                $montant = $s->{"avance$i"} ?? null;
                $date    = $s->{"date_avance$i"} ?? null;
                if (!$montant) continue;

                $ref = "S:{$s->id}:avance{$i}";
                if ($this->alreadyMigrated($ref)) continue;

                $data = $this->makeSalaireRow($s, $ref, $montant, $date, "avance{$i}");
                if (!$dryRun) DB::table('paiements')->insert($data);
                $count++;
            }
        }
        $this->stats['sources']['salaires'] = $count;
        return $count;
    }

    // ==================================================================
    // Helpers privés
    // ==================================================================

    private function makeSalaireRow($s, string $ref, $montant, $date, string $kind): array
    {
        return [
            'payable_type'  => 'Modules\\Finances\\Entities\\Salaire',
            'payable_id'    => $s->id,
            'apprenant_id'  => null,
            'montant_cents' => (int) round(((float) $montant) * 100),
            'mode_paiement' => 'virement', // par défaut
            'reference'     => $ref,
            'date_paiement' => $date ? substr($date, 0, 10) : now()->toDateString(),
            'recu_par'      => null,
            'source_system' => 'agree_sikul_migration_salaire',
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }

    private function alreadyMigrated(string $reference): bool
    {
        return DB::table('paiements')->where('reference', $reference)->exists();
    }

    private function hasTable(string $name): bool
    {
        try {
            return \Schema::hasTable($name);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Normalise une chaîne nature/mode en valeur enum DB
     * (espece, cheque, virement, mobile_money, carte).
     */
    private function normalizeMode(?string $raw): string
    {
        $clean = strtolower(trim((string) $raw));
        $clean = str_replace([' ', '-'], '_', $clean);
        return match (true) {
            str_contains($clean, 'cheque')       => 'cheque',
            str_contains($clean, 'virement')     => 'virement',
            str_contains($clean, 'mobile')       => 'mobile_money',
            str_contains($clean, 'carte')        => 'carte',
            str_contains($clean, 'especes'), str_contains($clean, 'espece') => 'espece',
            default                              => 'espece',
        };
    }
}
