<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2 fixes :
 *  - natures_examens.poids : NOT NULL avec default 1 → nullable (le form simplifié
 *    n'envoie pas toujours poids, et Eloquent envoie explicitement null → MySQL
 *    refuse même avec default car le INSERT contient `poids = NULL`)
 *  - jours_feries.jour et jours_feries.mois : NOT NULL sans default → nullable
 *    (on calcule jour/mois automatiquement depuis la date dans le controller)
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (Schema::hasTable('natures_examens') && Schema::hasColumn('natures_examens', 'poids')) {
            Schema::table('natures_examens', function (Blueprint $table) {
                $table->decimal('poids', 5, 2)->nullable()->change();
            });
        }

        if (Schema::hasTable('jours_feries')) {
            if (Schema::hasColumn('jours_feries', 'jour')) {
                Schema::table('jours_feries', function (Blueprint $table) {
                    $table->tinyInteger('jour')->nullable()->change();
                });
            }
            if (Schema::hasColumn('jours_feries', 'mois')) {
                Schema::table('jours_feries', function (Blueprint $table) {
                    $table->tinyInteger('mois')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // Pas de rollback
    }
};
