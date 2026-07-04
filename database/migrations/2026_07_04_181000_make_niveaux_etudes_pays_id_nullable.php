<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rend `pays_id` nullable sur `niveaux_etudes`.
 * Le formulaire Niveau d'Étude a été simplifié : le pays est porté par
 * l'utilisateur, plus par le référentiel. Colonne conservée pour les données legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('niveaux_etudes') || !Schema::hasColumn('niveaux_etudes', 'pays_id')) {
            return;
        }
        DB::statement('ALTER TABLE `niveaux_etudes` MODIFY `pays_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('niveaux_etudes') || !Schema::hasColumn('niveaux_etudes', 'pays_id')) {
            return;
        }
        DB::statement('ALTER TABLE `niveaux_etudes` MODIFY `pays_id` BIGINT UNSIGNED NOT NULL');
    }
};
