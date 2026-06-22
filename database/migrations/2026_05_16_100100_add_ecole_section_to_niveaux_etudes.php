<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('niveaux_etudes')) {
            return;
        }

        // 1) Ajout colonnes nullable (pas de FK encore)
        Schema::table('niveaux_etudes', function (Blueprint $table) {
            if (!Schema::hasColumn('niveaux_etudes', 'ecole_id')) {
                $table->unsignedBigInteger('ecole_id')->nullable()->after('libelle');
            }
            if (!Schema::hasColumn('niveaux_etudes', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->after('ecole_id');
            }
        });

        // 2) Nettoie les données orphelines existantes qui empêcheraient l'ajout de FK
        //    Une migration historique a peut-être laissé des pays_id qui n'existent plus.
        try {
            DB::statement('UPDATE niveaux_etudes ne LEFT JOIN pays p ON p.id = ne.pays_id SET ne.pays_id = NULL WHERE p.id IS NULL AND ne.pays_id IS NOT NULL');
        } catch (\Throwable $e) {
            // On ignore si le nettoyage échoue, la suite gérera quand même
        }

        // 3) Ajout des FK avec désactivation temporaire des checks
        //    (évite que le rebuild de la table échoue à cause d'anciens orphelins)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            Schema::table('niveaux_etudes', function (Blueprint $table) {
                if (Schema::hasTable('ecoles')) {
                    try { $table->foreign('ecole_id')->references('id')->on('ecoles')->nullOnDelete(); } catch (\Throwable $e) {}
                }
                if (Schema::hasTable('sections')) {
                    try { $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete(); } catch (\Throwable $e) {}
                }
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('niveaux_etudes')) {
            return;
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            Schema::table('niveaux_etudes', function (Blueprint $table) {
                try { $table->dropForeign(['ecole_id']); } catch (\Throwable $e) {}
                try { $table->dropForeign(['section_id']); } catch (\Throwable $e) {}
                foreach (['ecole_id', 'section_id'] as $col) {
                    if (Schema::hasColumn('niveaux_etudes', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
