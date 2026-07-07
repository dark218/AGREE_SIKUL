<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.4 — Suppression de 5 CRUD doublons du module Paramétrage :
 *   - `niveaux` (doublon `niveaux_etudes`)
 *   - `zones` (référentiel dormant, seule FK entrante = résidu SmilPay `kpi_zones`)
 *   - `langues` (référentiel dormant, 0 FK — les enseignants stockent
 *     leurs langues dans le JSON `languages`)
 *   - `categorie_apprenants` (doublon TypeApprenant + StatutApprenant)
 *   - `types_etablissements_spe` (doublon TypeEtablissement, déjà masqué)
 *
 * Les FK entrantes (venant d'autres tables) sont automatiquement droppées
 * avant le drop de la table cible. Nécessaire pour zones qui a un FK depuis
 * `kpi_zones` (résidu SmilPay analytique).
 *
 * Purge aussi les permissions RBAC associées.
 */
return new class extends Migration
{
    private const TABLES_TO_DROP = [
        'niveaux',
        'zones',
        'langues',
        'categorie_apprenants',
        'types_etablissements_spe',
    ];

    private const FEATURES_TO_DROP = [
        'niveaux',
        'zones',
        'langues',
        'categories_apprenant',
        'types_etablissement_spe',
    ];

    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1. Drop des tables. Chaque drop :
        //    a. Vérifie que la table existe et est vide (safety)
        //    b. Drop toutes les FK entrantes venant d'autres tables
        //       (ex: kpi_zones.zone_id → zones.id, résidu SmilPay)
        //    c. Drop la table
        foreach (self::TABLES_TO_DROP as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $count = DB::table($table)->count();
            if ($count > 0) {
                logger()->warning(
                    "Migration drop_5_referentiels_doublons : la table `{$table}` "
                    . "contient {$count} lignes ; drop annulé pour cette table."
                );
                continue;
            }
            $this->dropIncomingForeignKeys($table);
            Schema::drop($table);
        }

        // 2. Purge features RBAC + permissions associées.
        $featureIds = DB::table('feature')
            ->whereIn('menu_url', self::FEATURES_TO_DROP)
            ->pluck('id')->all();
        if (!empty($featureIds)) {
            $permIds = DB::table('permissions')->whereIn('feature_id', $featureIds)->pluck('id')->all();
            if (!empty($permIds)) {
                DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
                DB::table('permissions')->whereIn('id', $permIds)->delete();
            }
            DB::table('feature')->whereIn('id', $featureIds)->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Rollback non trivial.
    }

    /**
     * Drop toutes les foreign key qui pointent VERS la table cible depuis
     * d'autres tables. Nécessaire avant `DROP TABLE` (MySQL 3730 sinon).
     *
     * Note : on drop la FK sur la table SOURCE (celle qui référence).
     */
    private function dropIncomingForeignKeys(string $targetTable): void
    {
        $connection = DB::connection()->getDatabaseName();
        $rows = DB::select(
            "SELECT TABLE_NAME AS source_table, CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND REFERENCED_TABLE_SCHEMA = ?
               AND REFERENCED_TABLE_NAME = ?
               AND CONSTRAINT_NAME != 'PRIMARY'",
            [$connection, $connection, $targetTable]
        );
        foreach ($rows as $r) {
            try {
                DB::statement("ALTER TABLE `{$r->source_table}` DROP FOREIGN KEY `{$r->CONSTRAINT_NAME}`");
            } catch (\Throwable $e) {
                // Ignoré — peut avoir été droppée par une migration antérieure.
            }
        }
    }
};
