<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Garantit, par migration (et non par seed), la fonctionnalité « Catégorie
 * Apprenant » du menu Paramétrage → Apprenants & Enseignants : ligne feature +
 * permissions attribuées au super_admin. Les features vivant en base et
 * n'étant pas rejouées au déploiement Dokploy, tout doit être porté par migrate.
 * Idempotent : ne crée que ce qui manque.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Ligne feature.
        $featureId = null;
        if (Schema::hasTable('feature') && Schema::hasTable('module')) {
            $featureId = DB::table('feature')->where('menu_url', 'categories-apprenants')->value('id');
            if (!$featureId) {
                $moduleId = DB::table('module')->where('libelle', 'Paramétrage')->value('id');
                if ($moduleId) {
                    $featureId = DB::table('feature')->insertGetId([
                        'libelle'    => 'Catégorie Apprenant',
                        'libelle_en' => 'Student Category',
                        'module_id'  => $moduleId,
                        'menu_url'   => 'categories-apprenants',
                        'icone'      => 'fas fa-users',
                        'ordre'      => 40,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 2) Permissions + attribution au super_admin.
        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            $superAdminId = DB::table('roles')->where('name', 'super_admin')->value('id');

            foreach (['list', 'view', 'create', 'edit', 'delete'] as $action) {
                $name = 'categorie_apprenant-' . $action;
                $permId = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
                if (!$permId) {
                    $permId = DB::table('permissions')->insertGetId([
                        'name'       => $name,
                        'libelle'    => 'Catégorie Apprenant - ' . ucfirst($action),
                        'guard_name' => 'web',
                        'feature_id' => $featureId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if ($superAdminId && !DB::table('role_has_permissions')->where('role_id', $superAdminId)->where('permission_id', $permId)->exists()) {
                    DB::table('role_has_permissions')->insert(['role_id' => $superAdminId, 'permission_id' => $permId]);
                }
            }
        }
    }

    public function down(): void
    {
        // On ne supprime pas la feature/permissions au rollback : elles peuvent
        // préexister (seed historique). Migration purement additive/idempotente.
    }
};
