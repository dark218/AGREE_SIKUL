<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AcademiquePermissionsCompletSeeder extends Seeder
{
    public function run(): void
    {
        app()['cache']->forget('spatie.permission.cache');

        // TOUTES les entités académiques
        $entities = [
            'apprenants',
            'absences',
            'absences-apprenants',
            'absences-enseignants',
            'affectations-enseignants',
            'bibliotheques',
            'bulletins',
            'classes-apprenants',
            'cours',
            'devoirs',
            'dossiers-apprenants',
            'emploi-temps',
            'enseignants',
            'evaluations',
            'inscriptions',
            'justificatifs-absences',
            'listes-manuels',
            'manuels',
            'matieres',
            'moyennes-matieres',
            'notes',
            'passages',
            'personnels-administratifs',
            'presence-seances',
            'presences',
            'rendus-devoirs',
            'seances',
            'tuteurs',
        ];

        $actions = ['list', 'create', 'edit', 'delete', 'update', 'statut', 'activate'];

        echo "\n=== Creating Academique Permissions ===\n";

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $permName = $entity . '-' . $action;
                Permission::firstOrCreate(
                    ['name' => $permName],
                    [
                        'guard_name' => 'web',
                        'libelle' => ucfirst($action) . ' ' . ucfirst(str_replace('-', ' ', $entity))
                    ]
                );
                echo "✅ {$permName}\n";
            }
        }

        echo "\n=== Assigning to All Roles ===\n";

        // 1 requête pour tous les IDs au lieu de N*R via syncPermissions
        $permissionIds = Permission::pluck('id')->all();
        $permissionCount = count($permissionIds);
        $roles = Role::all();

        $pivotTable = config('permission.table_names.role_has_permissions') ?: 'role_has_permissions';
        $roleCol = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $permCol = config('permission.column_names.permission_pivot_key') ?: 'permission_id';

        foreach ($roles as $role) {
            DB::table($pivotTable)->where($roleCol, $role->id)->delete();
            if ($permissionCount > 0) {
                $rows = array_map(
                    fn ($pid) => [$roleCol => $role->id, $permCol => $pid],
                    $permissionIds
                );
                DB::table($pivotTable)->insert($rows);
            }
            echo "✅ {$role->name}: {$permissionCount} permissions\n";
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        echo "\n✅ Academique permissions COMPLETE!\n";
    }
}
