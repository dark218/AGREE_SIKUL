<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.3 — Suppression fonctionnalité "Type de contrat" au profit
 * de "Nature de contrat" (garde le référentiel scoping ecole_id).
 *
 * 1. Copie types_contrats → natures_contrats (dédoublonnage par code)
 * 2. Drop la table `types_contrats`
 * 3. Purge la feature RBAC `types_contrats` et ses permissions
 *
 * Note : `enseignants.type_contrat` est un VARCHAR (pas une FK) — aucun
 * FK repointage nécessaire au niveau schéma.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('types_contrats') && Schema::hasTable('natures_contrats')) {
            $existing = DB::table('natures_contrats')->pluck('code')->all();
            $rows = DB::table('types_contrats')->whereNull('deleted_at')->get();
            foreach ($rows as $row) {
                if (in_array($row->code, $existing, true)) {
                    continue;
                }
                DB::table('natures_contrats')->insert([
                    'code'       => $row->code,
                    'libelle'    => $row->libelle,
                    'etat'       => $row->etat ?? 'actif',
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        if (Schema::hasTable('types_contrats')) {
            Schema::drop('types_contrats');
        }

        // Purge feature + permissions RBAC `types_contrats`.
        $featureIds = DB::table('feature')->where('menu_url', 'types_contrats')->pluck('id')->all();
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
