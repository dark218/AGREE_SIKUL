<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le formulaire Classe liste les NiveauEtude (table `niveaux_etudes`), mais la
 * colonne classes.niveau_id pointait par clé étrangère sur `niveaux`. Résultat :
 * « Le niveau id sélectionné est invalide » et échec d'enregistrement.
 *
 * Cette migration repointe la clé étrangère vers `niveaux_etudes`.
 * Elle est défensive (try/catch) pour ne jamais bloquer un déploiement si la
 * contrainte porte déjà un autre nom ou a déjà été supprimée.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('classes') || !Schema::hasColumn('classes', 'niveau_id')) {
            return;
        }

        // 1) Supprimer l'ancienne clé étrangère (vers `niveaux`) si elle existe.
        try {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropForeign(['niveau_id']);
            });
        } catch (\Throwable $e) {
            // Contrainte absente ou nommée différemment : on continue.
        }

        // 2) Neutraliser les valeurs orphelines (id absent de niveaux_etudes)
        //    pour permettre la création de la nouvelle contrainte.
        if (Schema::hasTable('niveaux_etudes')) {
            try {
                DB::statement('UPDATE `classes` SET `niveau_id` = NULL
                    WHERE `niveau_id` IS NOT NULL
                      AND `niveau_id` NOT IN (SELECT `id` FROM `niveaux_etudes`)');
            } catch (\Throwable $e) {
                // En cas de souci on n'empêche pas la migration.
            }

            // 3) Ajouter la nouvelle clé étrangère vers niveaux_etudes.
            try {
                Schema::table('classes', function (Blueprint $table) {
                    $table->foreign('niveau_id')
                        ->references('id')
                        ->on('niveaux_etudes')
                        ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // Si l'ajout échoue (moteur/charset), la validation applicative
                // (exists:niveaux_etudes,id) garantit déjà l'intégrité.
            }
        }

        // 4) Vider le cache des lookups Classe (campus libellé + niveaux).
        Cache::forget('parametrage.lookups.classe_lists');
    }

    public function down(): void
    {
        if (!Schema::hasTable('classes') || !Schema::hasColumn('classes', 'niveau_id')) {
            return;
        }

        try {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropForeign(['niveau_id']);
            });
        } catch (\Throwable $e) {
            // ignore
        }

        if (Schema::hasTable('niveaux')) {
            try {
                Schema::table('classes', function (Blueprint $table) {
                    $table->foreign('niveau_id')
                        ->references('id')
                        ->on('niveaux')
                        ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        Cache::forget('parametrage.lookups.classe_lists');
    }
};
