<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use File;

class CreateAllPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Clear cache
        app()['cache']->forget('spatie.permission.cache');

        echo "\n=== Scanning for all permission.check: calls in codebase ===\n";

        // Scan all PHP files in Modules directory for permission.check: patterns
        $files = File::allFiles(base_path('Modules'));
        $permissionPattern = '/permission\.check:([a-z0-9_\-]+)/i';
        $foundPermissions = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;

            $content = $file->getContents();
            if (preg_match_all($permissionPattern, $content, $matches)) {
                foreach ($matches[1] as $perm) {
                    $foundPermissions[$perm] = true;
                }
            }
        }

        echo "Found " . count($foundPermissions) . " unique permission bases\n";

        // Standard actions for each permission
        $actions = ['list', 'create', 'edit', 'delete', 'activate', 'update', 'statut'];

        // Special actions for specific permissions
        $specialActions = [
            'seances' => [],
            'ventepos' => ['cancel', 'create', 'edit', 'list', 'refund', 'refund-line', 'refund-mixte', 'refund-mixte-quantite', 'refund-quantite', 'validate'],
            'session-caisse-caissier' => ['fermerture', 'list', 'ouverture'],
            'session-caisse-manager' => ['create', 'fermerture', 'list'],
            'wallet' => ['edit', 'list', 'show-by-owner', 'statut'],
            'employement-contract' => ['create', 'delete', 'edit', 'list', 'update', 'validate'],
            'emploi-temps' => ['create', 'delete', 'edit', 'list'],
            'emplois-du-temps' => ['create', 'delete', 'list', 'update'],
        ];

        $allPermissions = [];

        echo "\n=== Creating all permissions ===\n";

        foreach ($foundPermissions as $permBase => $true) {
            // Get actions for this permission
            $actionsToUse = $specialActions[$permBase] ?? $actions;

            if (empty($actionsToUse)) {
                echo "⚠️  {$permBase} (no actions)\n";
                continue;
            }

            $count = 0;
            foreach ($actionsToUse as $action) {
                $permName = $permBase . '-' . $action;
                $permLabel = ucfirst($action) . ' ' . ucfirst(str_replace(['-', '_'], ' ', $permBase));

                $perm = Permission::firstOrCreate(
                    ['name' => $permName],
                    ['guard_name' => 'web', 'libelle' => $permLabel]
                );
                $allPermissions[] = $permName;
                $count++;
            }
            echo "✅ {$permBase} ({$count} actions)\n";
        }

        echo "\n=== Assigning to All Roles ===\n";

        if (empty($allPermissions)) {
            echo "⚠️  No permissions to assign\n";
            return;
        }

        // 1 requête pour tous les IDs de permission au lieu de N par rôle
        $permissionIds = Permission::whereIn('name', $allPermissions)->pluck('id')->all();
        $permissionCount = count($permissionIds);
        $roles = Role::all();

        $pivotTable = config('permission.table_names.role_has_permissions') ?: 'role_has_permissions';
        $roleCol = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $permCol = config('permission.column_names.permission_pivot_key') ?: 'permission_id';

        foreach ($roles as $role) {
            // 2 requêtes par rôle (delete + bulk insert) au lieu de N via syncPermissions
            DB::table($pivotTable)->where($roleCol, $role->id)->delete();
            if ($permissionCount > 0) {
                $rows = array_map(
                    fn ($pid) => [$roleCol => $role->id, $permCol => $pid],
                    $permissionIds
                );
                DB::table($pivotTable)->insert($rows);
            }
            echo "✅ {$role->name}: {$permissionCount} permissions assigned\n";
        }

        // Flush du cache Spatie une seule fois à la fin
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        echo "\n✅ All permissions created and assigned! (" . count($allPermissions) . " total)\n";
    }
}
