<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.2 — Suppression fonctionnalité Civilités au profit de
 * TitreCivilité (garde le référentiel avec `sigle` en plus).
 *
 * 1. Copie civilites → titres_civilites (dédoublonnage par code)
 * 2. Drop la table `civilites`
 * 3. Purge la feature RBAC `civilites` et ses permissions
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Copie civilites → titres_civilites (skip si code déjà présent).
        //    Note : `titres_civilites.code` a une contrainte UNIQUE case-insensitive
        //    (collation utf8mb4_unicode_ci). On normalise en uppercase pour la
        //    comparaison ET on utilise `insertOrIgnore` en second filet de
        //    sécurité — la migration doit rester idempotente même si la table
        //    source contient des doublons (soft-delete, seed antérieur).
        if (Schema::hasTable('civilites') && Schema::hasTable('titres_civilites')) {
            $existing = array_map(
                fn ($c) => strtoupper((string) $c),
                DB::table('titres_civilites')->pluck('code')->all()
            );
            $seenInBatch = [];
            $rows = DB::table('civilites')->whereNull('deleted_at')->get();
            foreach ($rows as $row) {
                $codeUp = strtoupper((string) ($row->code ?? ''));
                if ($codeUp === '' || in_array($codeUp, $existing, true) || in_array($codeUp, $seenInBatch, true)) {
                    continue;
                }
                $seenInBatch[] = $codeUp;
                DB::table('titres_civilites')->insertOrIgnore([
                    'code'       => $row->code,
                    'libelle'    => $row->libelle,
                    'sigle'      => $row->libelle, // libelle court fait office de sigle
                    'etat'       => $row->etat ?? 'actif',
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        // 2. Drop la table civilites.
        if (Schema::hasTable('civilites')) {
            Schema::drop('civilites');
        }

        // 3. Purge feature + permissions RBAC `civilites`.
        $featureIds = DB::table('feature')->where('menu_url', 'civilites')->pluck('id')->all();
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
