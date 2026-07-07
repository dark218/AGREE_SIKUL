<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FUSION #12 (Phase 3.3 du plan) — Paiements polymorphes.
 *
 * Contexte §10.4 : 4 modélisations concurrentes de paiements échelonnés :
 *   - Versement.versement_1..12 + montant_versement_1..12 (24 colonnes)
 *   - FacturationApprenant (via Versement)
 *   - AchatDepense.date_paiement_1..6 + montant_paiement_1..6 (12 colonnes)
 *   - Salaire.avance_1..4 + date_avance_1..4 (8 colonnes)
 *
 * La table `paiements` (créée en Phase 4.6b) devient la source unique, avec
 * `payable_type` + `payable_id` en morphs (à la Laravel MorphTo).
 *
 * Cette migration :
 *   1. Ajoute `payable_type` + `payable_id` à `paiements`
 *   2. Rend `frais_id` NULLABLE (auparavant NOT NULL — trop restrictif car
 *      les paiements ne sont pas tous liés à un Frais Académique)
 *   3. Ajoute un index composé (payable_type, payable_id) pour les lookups
 *
 * Idempotente. Les données existantes (Versement/AchatDepense/AutreRevenu/Salaire
 * dans leurs slots hardcodés) restent en place — la migration des données est
 * gérée par un service dédié (PaiementsMigrationService) et un artisan command.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('paiements')) return;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('paiements', function (Blueprint $t) {
            if (!Schema::hasColumn('paiements', 'payable_type')) {
                $t->string('payable_type', 125)->nullable()->after('frais_id');
            }
            if (!Schema::hasColumn('paiements', 'payable_id')) {
                $t->unsignedBigInteger('payable_id')->nullable()->after('payable_type');
            }
        });

        // Rendre frais_id NULLABLE — les paiements polymorphes ne pointent pas
        // tous vers un Frais Académique (peuvent être Versement, AchatDepense…).
        $this->makeColumnNullable('paiements', 'frais_id');

        // Index composé pour les lookups (payable_type, payable_id).
        if (!$this->indexExists('paiements', 'paiements_payable_index')) {
            Schema::table('paiements', function (Blueprint $t) {
                $t->index(['payable_type', 'payable_id'], 'paiements_payable_index');
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        if (!Schema::hasTable('paiements')) return;

        Schema::table('paiements', function (Blueprint $t) {
            if ($this->indexExists('paiements', 'paiements_payable_index')) {
                $t->dropIndex('paiements_payable_index');
            }
            if (Schema::hasColumn('paiements', 'payable_id')) {
                $t->dropColumn('payable_id');
            }
            if (Schema::hasColumn('paiements', 'payable_type')) {
                $t->dropColumn('payable_type');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = DB::connection()->getDatabaseName();
        $rows = DB::select(
            "SELECT COUNT(*) AS c
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$connection, $table, $indexName]
        );
        return (int) ($rows[0]->c ?? 0) > 0;
    }

    private function makeColumnNullable(string $tableName, string $columnName): void
    {
        $connection = DB::connection()->getDatabaseName();
        $col = DB::select(
            "SELECT COLUMN_TYPE, IS_NULLABLE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$connection, $tableName, $columnName]
        );
        if (empty($col) || strtoupper($col[0]->IS_NULLABLE) === 'YES') return;

        $type = $col[0]->COLUMN_TYPE;
        try {
            DB::statement("ALTER TABLE `{$tableName}` MODIFY `{$columnName}` {$type} NULL");
        } catch (\Throwable $e) {}
    }
};
