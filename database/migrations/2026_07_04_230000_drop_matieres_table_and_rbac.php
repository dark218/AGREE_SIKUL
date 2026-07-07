<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.1e finale — Drop de la table `matieres` (Académique) et
 * nettoyage des permissions RBAC associées.
 *
 * Prérequis : migration précédente (`2026_07_04_220000_enrich_matieres_unites_and_repoint_fks.php`)
 * a copié les données et repointé les FK. Le code applicatif a été
 * repointé vers `MatiereUnite` (Phase 1.1b+c).
 *
 * On drop uniquement si la table est vide (précaution).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop la table matieres si elle est vide (safety).
        if (Schema::hasTable('matieres')) {
            $count = DB::table('matieres')->count();
            if ($count === 0) {
                Schema::drop('matieres');
            } else {
                // On ne bloque pas la migration, on log l'anomalie pour ne pas
                // perdre de données silencieusement.
                logger()->warning(
                    "Migration drop_matieres_table_and_rbac : la table `matieres` "
                    . "contient encore {$count} lignes. Drop annulé, migrer les données "
                    . "vers matieres_unites d'abord."
                );
            }
        }

        // 2. Purger les permissions et la feature `matieres` (Académique).
        $featureIds = DB::table('feature')->where('menu_url', 'matieres')->pluck('id')->all();
        if (!empty($featureIds)) {
            $permIds = DB::table('permissions')->whereIn('feature_id', $featureIds)->pluck('id')->all();
            if (!empty($permIds)) {
                DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
                DB::table('permissions')->whereIn('id', $permIds)->delete();
            }
            DB::table('feature')->whereIn('id', $featureIds)->delete();
        }
    }

    public function down(): void
    {
        // Rollback non trivial (recréation table + repopulation + FK) :
        // à faire manuellement si vraiment nécessaire.
    }
};
