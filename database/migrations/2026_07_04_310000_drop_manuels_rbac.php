<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3.4 — Purge la feature RBAC `manuels` (doublon de `listes-manuels`).
 * Aucune table à drop : ManuelController et ListeManuelsController utilisent
 * tous deux la même table `listes_manuels` (bug préexistant).
 */
return new class extends Migration
{
    public function up(): void
    {
        $featureIds = DB::table('feature')->where('menu_url', 'manuels')->pluck('id')->all();
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
