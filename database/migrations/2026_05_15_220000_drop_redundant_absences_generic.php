<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidation des tables Absences (3 → 2 typées).
 *
 * On gardait 3 tables :
 *  - absences (générique, créée 2026_03_01) — moins riche
 *  - absences_apprenants (typée, créée 2026_02_09_150500, enrichie multiple fois)
 *  - absences_enseignants (typée, créée 2026_02_10_120200, enrichie)
 *
 * On garde les 2 typées (apprenants + enseignants) et on drop `absences` générique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('absences')) {
            Schema::drop('absences');
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Pas de rollback : consolidation définitive
    }
};
