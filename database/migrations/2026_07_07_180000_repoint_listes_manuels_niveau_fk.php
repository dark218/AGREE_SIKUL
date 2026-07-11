<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * listes_manuels.niveau_id pointait sur la table `niveaux` (6 lignes héritées),
 * alors que le formulaire alimente le menu déroulant depuis NiveauEtude
 * (`niveaux_etudes`, 21 lignes). Résultat : « niveau id invalide » à
 * l'enregistrement — même incohérence que celle corrigée sur les classes.
 * On repointe la clé étrangère vers `niveaux_etudes`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('listes_manuels') || !Schema::hasColumn('listes_manuels', 'niveau_id')) {
            return;
        }

        // 1) Ancienne contrainte vers `niveaux`.
        Schema::table('listes_manuels', function (Blueprint $table) {
            try {
                $table->dropForeign(['niveau_id']);
            } catch (\Throwable $e) {
                // pas de contrainte nommée par défaut : on ignore
            }
        });

        // 2) Neutralise les valeurs orphelines avant de recréer la FK.
        if (Schema::hasTable('niveaux_etudes')) {
            DB::table('listes_manuels')
                ->whereNotNull('niveau_id')
                ->whereNotIn('niveau_id', function ($q) {
                    $q->select('id')->from('niveaux_etudes');
                })
                ->update(['niveau_id' => null]);

            // 3) Nouvelle contrainte vers `niveaux_etudes`.
            Schema::table('listes_manuels', function (Blueprint $table) {
                $table->foreign('niveau_id')->references('id')->on('niveaux_etudes')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('listes_manuels') || !Schema::hasColumn('listes_manuels', 'niveau_id')) {
            return;
        }

        Schema::table('listes_manuels', function (Blueprint $table) {
            try {
                $table->dropForeign(['niveau_id']);
            } catch (\Throwable $e) {
            }
        });

        if (Schema::hasTable('niveaux')) {
            Schema::table('listes_manuels', function (Blueprint $table) {
                $table->foreign('niveau_id')->references('id')->on('niveaux')->nullOnDelete();
            });
        }
    }
};
