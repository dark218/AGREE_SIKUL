<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §UX : le champ "Se répète chaque année" (est_recurrent) est utilisé par le
 * formulaire de création de jour férié (Vue) et référencé dans le fillable
 * de l'entité JourFerie, mais la colonne n'existait pas en DB → crash SQL :
 *
 *   SQLSTATE[42S22]: Unknown column 'est_recurrent' in 'field list'
 *
 * Cette migration ajoute la colonne boolean après `pays_id`. Idempotente.
 * Valeur par défaut = false (compat rétro).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jours_feries')) return;
        if (Schema::hasColumn('jours_feries', 'est_recurrent')) return;

        Schema::table('jours_feries', function (Blueprint $t) {
            $t->boolean('est_recurrent')->default(false)->after('pays_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('jours_feries') && Schema::hasColumn('jours_feries', 'est_recurrent')) {
            Schema::table('jours_feries', function (Blueprint $t) {
                $t->dropColumn('est_recurrent');
            });
        }
    }
};
