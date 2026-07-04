<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute cycle_id et duree à periodes_colaires.
 * Les 2 colonnes sont NULLABLE → aucun impact sur les données existantes.
 *  - cycle_id : FK vers cycles_enseignement (optionnel)
 *  - duree    : nombre de jours auto-calculé depuis date_debut/date_fin
 *               (calculé dans le controller au moment du save)
 */
return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('periodes_colaires')) {
            return;
        }

        Schema::table('periodes_colaires', function (Blueprint $table) {
            if (!Schema::hasColumn('periodes_colaires', 'cycle_id')) {
                $table->unsignedBigInteger('cycle_id')->nullable()->after('annee_scolaire_id');
            }
            if (!Schema::hasColumn('periodes_colaires', 'duree')) {
                $table->integer('duree')->nullable()->comment('Durée en jours (auto-calculée)')->after('date_fin');
            }
        });

        // Ajoute la FK cycle_id si elle n'existe pas (try/catch pour idempotence)
        if (Schema::hasTable('cycles_enseignement') && Schema::hasColumn('periodes_colaires', 'cycle_id')) {
            try {
                Schema::table('periodes_colaires', function (Blueprint $table) {
                    $table->foreign('cycle_id')->references('id')->on('cycles_enseignement')->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // FK déjà créée
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('periodes_colaires')) {
            return;
        }

        Schema::table('periodes_colaires', function (Blueprint $table) {
            if (Schema::hasColumn('periodes_colaires', 'cycle_id')) {
                try { $table->dropForeign(['cycle_id']); } catch (\Throwable $e) {}
                $table->dropColumn('cycle_id');
            }
            if (Schema::hasColumn('periodes_colaires', 'duree')) {
                $table->dropColumn('duree');
            }
        });
    }
};
