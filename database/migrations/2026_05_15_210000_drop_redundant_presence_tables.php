<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidation des tables Présences.
 *
 * On gardait 3 tables similaires :
 *  - presences (active, créée 2026_02_21_150000, refactor 2026_03_01)
 *  - presence_seances (créée 2026_02_28, doublon)
 *  - presences_seances (créée 2026_02_09_150400, doublon)
 *
 * On garde uniquement `presences` (la plus utilisée + refactor le plus récent).
 * Les 2 autres sont droppées ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('presence_seances')) {
            Schema::drop('presence_seances');
        }
        if (Schema::hasTable('presences_seances')) {
            Schema::drop('presences_seances');
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Pas de rollback : consolidation définitive
    }
};
