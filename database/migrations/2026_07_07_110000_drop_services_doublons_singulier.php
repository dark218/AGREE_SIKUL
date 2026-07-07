<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop les 4 tables doublons SINGULIER du module Services (Phase 4.6c fix).
 *
 * Contexte : Modules/Services/Database/Migrations crée des tables au singulier
 * (`consultations_infirmerie`, `passages_cantine`, `inscriptions_cantine`,
 * `inscriptions_transport`) avec un schéma DIFFÉRENT des tables canoniques
 * PLURIEL créées dans database/migrations/2026_02_09_15**_create_*_table.php.
 *
 * Résultat : chaque entité pointe sur l'une des deux, avec un fillable qui
 * ne matche que le schéma pluriel. Toutes les entités ont été repointées sur
 * les tables pluriel dans les fixes précédents.
 *
 * Ces 4 tables singulier sont vides en prod (0 rows vérifiés) — drop safe.
 *
 * Idempotente : `dropIfExists` no-op si absente.
 */
return new class extends Migration
{
    private const DOUBLONS_A_DROP = [
        'consultations_infirmerie',
        'passages_cantine',
        'inscriptions_cantine',
        'inscriptions_transport',
    ];

    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::DOUBLONS_A_DROP as $table) {
            if (Schema::hasTable($table)) {
                // Drop FK entrantes d'abord — évite MySQL 3730 si des tables
                // partenaires (menu_id, passages, etc.) référencent ces doublons.
                $this->dropIncomingForeignKeys($table);
                Schema::dropIfExists($table);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function dropIncomingForeignKeys(string $targetTable): void
    {
        $connection = DB::connection()->getDatabaseName();
        $rows = DB::select(
            "SELECT TABLE_NAME AS source_table, CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND REFERENCED_TABLE_SCHEMA = ?
               AND REFERENCED_TABLE_NAME = ?
               AND CONSTRAINT_NAME != 'PRIMARY'",
            [$connection, $connection, $targetTable]
        );
        foreach ($rows as $r) {
            try {
                DB::statement("ALTER TABLE `{$r->source_table}` DROP FOREIGN KEY `{$r->CONSTRAINT_NAME}`");
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        // Aucune restauration : les tables singulier ne doivent jamais réapparaître.
        // Les entités Services ont été repointées définitivement vers les PLURIEL.
    }
};
