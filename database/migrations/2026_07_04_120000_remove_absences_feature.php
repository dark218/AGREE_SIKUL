<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supprime la fonctionnalité "Absences" (menu_url = 'absences') qui faisait
 * doublon avec "Absence Apprenant" / "Absence Enseignant" et apparaissait dans
 * le sous-menu "Autres". On retire la feature ET ses permissions liées
 * (via permissions.feature_id) proprement, sans toucher aux features
 * absences-apprenants / absences-enseignants.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('feature')) {
            return;
        }

        // Id(s) de la feature "absences" (menu_url exact).
        $featureIds = DB::table('feature')->where('menu_url', 'absences')->pluck('id')->all();
        if (empty($featureIds)) {
            return;
        }

        // Permissions liées à cette feature (via feature_id si la colonne existe).
        $permissionIds = [];
        if (Schema::hasTable('permissions') && Schema::hasColumn('permissions', 'feature_id')) {
            $permissionIds = DB::table('permissions')->whereIn('feature_id', $featureIds)->pluck('id')->all();
        }

        // Nettoyage des pivots Spatie pour éviter les orphelins.
        if (!empty($permissionIds)) {
            foreach (['role_has_permissions', 'model_has_permissions'] as $pivot) {
                if (Schema::hasTable($pivot)) {
                    try {
                        DB::table($pivot)->whereIn('permission_id', $permissionIds)->delete();
                    } catch (\Throwable $e) { /* ignore */ }
                }
            }
            try {
                DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            } catch (\Throwable $e) { /* ignore */ }
        }

        // Pivot feature<->permission éventuel.
        if (Schema::hasTable('feature_permission')) {
            try {
                DB::table('feature_permission')->whereIn('feature_id', $featureIds)->delete();
            } catch (\Throwable $e) { /* ignore */ }
        }

        // Suppression de la feature elle-même (hard delete même si SoftDeletes).
        try {
            DB::table('feature')->whereIn('id', $featureIds)->delete();
        } catch (\Throwable $e) { /* ignore */ }
    }

    public function down(): void
    {
        // Pas de rollback : on ne recrée pas une fonctionnalité doublon volontairement supprimée.
    }
};
