<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop la table `menus` — doublon de `menu_cantines`.
 *
 * Contexte §10.6 : deux entités Menu (table `menus`, ancienne) et MenuCantine
 * (table `menu_cantines`, plus riche avec week_number/year/entree/plat).
 * MenuCantine est la source canonique.
 *
 * ServiceCantine::menus() est repointé sur MenuCantine (cf. entity).
 * MenuController + routes menus.* + dossier Vue Menus/ retirés.
 *
 * Table vide en prod (0 rows) — drop safe.
 * Idempotente : dropIfExists.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        if (Schema::hasTable('menus')) {
            // Drop FK entrantes d'abord (ex: passages_cantine.menu_id → menus.id
            // dans certaines migrations Modules/Services).
            $this->dropIncomingForeignKeys('menus');
            Schema::dropIfExists('menus');
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Aucune restauration — MenuCantine couvre le besoin fonctionnel.
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
};
