<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Batch : rend `pays_id` nullable sur toutes les tables de référentiels
 * paramétrables (regions, departements, communes, quartiers, zones).
 *
 * Justification : le pays est désormais porté par l'utilisateur (User.pays_id),
 * pas répété manuellement dans chaque formulaire. Les colonnes restent pour
 * préserver les données historiques et d'éventuels liens FK.
 */
return new class extends Migration
{
    private const TABLES = ['regions', 'departements', 'communes', 'quartiers', 'zones'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'pays_id')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `pays_id` BIGINT UNSIGNED NULL");
            }
        }
    }

    public function down(): void
    {
        // Ne pas revert : la table peut contenir des NULL désormais.
    }
};
