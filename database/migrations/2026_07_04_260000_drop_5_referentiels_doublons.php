<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.4 — Suppression de 5 CRUD doublons du module Paramétrage :
 *   - `niveaux` (doublon `niveaux_etudes`)
 *   - `zones` (référentiel dormant, 0 FK)
 *   - `langues` (référentiel dormant, 0 FK — les enseignants stockent
 *     leurs langues dans le JSON `languages`)
 *   - `categorie_apprenants` (doublon TypeApprenant + StatutApprenant)
 *   - `types_etablissements_spe` (doublon TypeEtablissement, déjà masqué)
 *
 * Toutes les tables sont vides ou n'ont AUCUNE FK entrante — on drop
 * en toute sécurité.
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
        // 1. Drop des tables (safety : chacune ne doit pas avoir de lignes).
        //    On log un warning si des lignes existent au lieu de bloquer.
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
    }

    public function down(): void
    {
        // Rollback non trivial.
    }
};
