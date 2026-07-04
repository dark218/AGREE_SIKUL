<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rend ecole_id et niveau_id optionnels (nullable) sur la table classes.
 * On utilise du SQL brut (MODIFY) afin de conserver les clés étrangères
 * existantes sans avoir besoin de doctrine/dbal.
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasColumn('classes', 'ecole_id')) {
            DB::statement('ALTER TABLE `classes` MODIFY `ecole_id` BIGINT UNSIGNED NULL');
        }
        if (Schema::hasColumn('classes', 'niveau_id')) {
            DB::statement('ALTER TABLE `classes` MODIFY `niveau_id` BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Attention : échoue s'il existe déjà des lignes avec ecole_id / niveau_id NULL.
        if (Schema::hasColumn('classes', 'ecole_id')) {
            DB::statement('ALTER TABLE `classes` MODIFY `ecole_id` BIGINT UNSIGNED NOT NULL');
        }
        if (Schema::hasColumn('classes', 'niveau_id')) {
            DB::statement('ALTER TABLE `classes` MODIFY `niveau_id` BIGINT UNSIGNED NOT NULL');
        }
    }
};
