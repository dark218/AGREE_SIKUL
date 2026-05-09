<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DevisePermissionsSeeder extends Seeder
{
    public function run()
    {
        // Create permissions
        $permissions = [
            ['name' => 'devise-list', 'libelle' => 'Lister les devises'],
            ['name' => 'devise-create', 'libelle' => 'Créer une devise'],
            ['name' => 'devise-edit', 'libelle' => 'Modifier une devise'],
            ['name' => 'devise-statut', 'libelle' => 'Changer le statut d\'une devise'],
            ['name' => 'devise-delete', 'libelle' => 'Supprimer une devise']
        ];

        foreach ($permissions as $perm) {
            $p = Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['guard_name' => 'web', 'libelle' => $perm['libelle']]
            );
            echo "✅ Permission '{$perm['name']}' created\n";
        }

        // Assign to Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $permNames = array_column($permissions, 'name');
            $adminRole->syncPermissions($permNames);
            echo "✅ Permissions assigned to Admin role\n";
        } else {
            echo "⚠️ Admin role not found\n";
        }
    }
}
