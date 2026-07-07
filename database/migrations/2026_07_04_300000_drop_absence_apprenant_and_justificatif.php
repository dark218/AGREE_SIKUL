<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3.3 — Unification Presence/Absence :
 *   - Drop `absences_apprenants` : Presence est désormais la source unique
 *     (statut IN ['absent', 'malade', 'permis']).
 *   - Drop `justificatifs_absences` : redondant avec justificatif_path des
 *     entités Presence et AbsenceEnseignant.
 *   - Purge features RBAC associées.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (['absences_apprenants', 'justificatifs_absences'] as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                if ($count === 0) {
                    Schema::drop($table);
                } else {
                    logger()->warning(
                        "drop_absence_apprenant_and_justificatif : la table `{$table}` "
                        . "contient encore {$count} lignes ; drop annulé."
                    );
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Purge features + permissions.
        $features = ['absences-apprenants', 'justificatifs-absences'];
        $featureIds = DB::table('feature')->whereIn('menu_url', $features)->pluck('id')->all();
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
