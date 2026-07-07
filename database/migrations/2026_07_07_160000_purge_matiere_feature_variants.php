<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * §10.5 renforcement — Purge complète de la feature Menu "Matière" du sidebar.
 *
 * Contexte : la migration `2026_07_04_230000_drop_matieres_table_and_rbac`
 * purgeait uniquement `feature.menu_url = 'matieres'`. En prod on trouve
 * potentiellement des variantes (`matiere` singulier, ou entrées créées à
 * la main). Cette migration ratisse plus large et purge :
 *
 *   - feature dont menu_url ∈ {matieres, matiere}
 *   - feature dont libelle = 'Matière' (module Académique id 25 uniquement,
 *     pour ne pas casser un futur "Matière Unité")
 *   - permissions Spatie associées (role_has_permissions + model_has_permissions)
 *
 * Idempotente : peut être rejouée sans effet sur une DB déjà nettoyée.
 * Non destructive côté user : pas de suppression de user/role/apprenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('feature')) {
            return;
        }

        // 1. Sélectionne les features "Matière" (variantes URL + libellé exact
        //    dans le module Académique).
        $featureIds = DB::table('feature')
            ->where(function ($q) {
                $q->whereIn('menu_url', ['matieres', 'matiere'])
                  ->orWhere(function ($qq) {
                      $qq->where('module_id', 25) // Académique
                         ->where('libelle', 'Matière');
                  });
            })
            ->pluck('id')
            ->all();

        if (empty($featureIds)) {
            return;
        }

        // 2. Coupe les permissions Spatie liées (patterns `{menu_url}-{action}`
        //    ET colonne feature_id historique).
        if (Schema::hasTable('permissions')) {
            // Via feature_id (schéma historique custom)
            $permIds = Schema::hasColumn('permissions', 'feature_id')
                ? DB::table('permissions')->whereIn('feature_id', $featureIds)->pluck('id')->all()
                : [];

            // Via naming convention Spatie (menu_url-action)
            foreach (['matieres', 'matiere'] as $slug) {
                $ids = DB::table('permissions')
                    ->where('name', 'like', $slug . '-%')
                    ->pluck('id')->all();
                $permIds = array_merge($permIds, $ids);
            }
            $permIds = array_values(array_unique($permIds));

            if (!empty($permIds)) {
                if (Schema::hasTable('role_has_permissions')) {
                    DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
                }
                if (Schema::hasTable('model_has_permissions')) {
                    DB::table('model_has_permissions')->whereIn('permission_id', $permIds)->delete();
                }
                DB::table('permissions')->whereIn('id', $permIds)->delete();
            }
        }

        // 3. Purge les features.
        DB::table('feature')->whereIn('id', $featureIds)->delete();
    }

    public function down(): void
    {
        // Aucune restauration : la feature Matière ne doit pas réapparaître.
    }
};
