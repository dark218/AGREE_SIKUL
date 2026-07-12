<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fonctionnalité « Plan comptable » du menu Finances : ligne feature +
 * permissions attribuées au super_admin. Tout par migration (zéro seed).
 */
return new class extends Migration
{
    public function up(): void
    {
        $featureId = null;
        if (Schema::hasTable('feature') && Schema::hasTable('module')) {
            $featureId = DB::table('feature')->where('menu_url', 'plan-comptes')->value('id');
            if (!$featureId) {
                $moduleId = DB::table('module')->where('libelle', 'Finances')->value('id');
                if ($moduleId) {
                    $featureId = DB::table('feature')->insertGetId([
                        'libelle'    => 'Plan comptable',
                        'libelle_en' => 'Chart of Accounts',
                        'module_id'  => $moduleId,
                        'menu_url'   => 'plan-comptes',
                        'icone'      => 'fas fa-book',
                        'ordre'      => 5,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            $superAdminId = DB::table('roles')->where('name', 'super_admin')->value('id');
            foreach (['list', 'create', 'edit', 'delete'] as $action) {
                $name = 'plan-comptes-' . $action;
                $permId = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
                if (!$permId) {
                    $permId = DB::table('permissions')->insertGetId([
                        'name' => $name,
                        'libelle' => 'Plan comptable - ' . ucfirst($action),
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
    }
};
