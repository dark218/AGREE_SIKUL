<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ModulePermissionsSeeder extends Seeder
{
    public function run()
    {
        // Clear cache
        app()['cache']->forget('spatie.permission.cache');

        // Define all modules and their features
        $modules = [
            // ACADEMIQUE MODULE
            'academique' => [
                'listes-manuels' => 'Listes Manuels',
                'passages' => 'Passages',
                'classes-apprenants' => 'Classes Apprenants',
                'absences' => 'Absences',
                'affectations-enseignants' => 'Affectations Enseignants',
            ],
            // FINANCES MODULE
            'finances' => [
                'ecolages' => 'Écolages',
                'versements' => 'Versements',
                'achats-depenses' => 'Achats et Dépenses',
                'salaires' => 'Salaires',
                'facturation-apprenants' => 'Facturation Apprenants',
            ],
        ];

        // Action types
        $actions = ['list', 'create', 'edit', 'delete', 'activate'];

        echo "\n=== Creating Module Permissions ===\n";

        $allPermissions = [];

        foreach ($modules as $module => $features) {
            foreach ($features as $featureKey => $featureLabel) {
                $basePermName = "{$module}-" . str_replace('-', '-', $featureKey);

                foreach ($actions as $action) {
                    $permName = $basePermName . '-' . $action;
                    $permLabel = ucfirst($action) . ' ' . $featureLabel;

                    $perm = Permission::firstOrCreate(
                        ['name' => $permName],
                        ['guard_name' => 'web', 'libelle' => $permLabel]
                    );
                    echo "✅ {$permName}\n";
                    $allPermissions[] = $permName;
                }
            }
        }

        echo "\n=== Assigning Permissions to Roles ===\n";

        // Get all existing permissions from parametrage and finances
        $parametragePerms = Permission::where('name', 'like', 'parametrage-%')->pluck('name')->toArray();
        $allPermsToAssign = array_merge($allPermissions, $parametragePerms);

        // Assign to all roles
        $roles = Role::all();
        foreach ($roles as $role) {
            $role->syncPermissions($allPermsToAssign);
            echo "✅ Assigned " . count($allPermsToAssign) . " permissions to role: {$role->name}\n";
        }

        echo "\n✅ All module permissions created and assigned!\n";
    }
}
