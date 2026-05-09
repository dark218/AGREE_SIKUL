<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixParametragePermissions extends Command
{
    protected $signature = 'fix:parametrage-permissions';
    protected $description = 'Fix permission names to match controller expectations';

    public function handle()
    {
        $this->info("Fixing Parametrage permissions...\n");

        // Delete old incorrect permissions
        \DB::table('permissions')
            ->whereIn('name', [
                'regions-list', 'regions-create', 'regions-view', 'regions-edit', 'regions-delete',
                'departements-list', 'departements-create', 'departements-view', 'departements-edit', 'departements-delete',
                'communes-list', 'communes-create', 'communes-view', 'communes-edit', 'communes-delete',
                // ... etc for all features
            ])
            ->delete();

        $this->line("✓ Deleted old permissions\n");

        // Get all Parametrage features
        $features = \DB::table('feature')
            ->where('module_id', 23)
            ->orderBy('ordre')
            ->get();

        // Create correct permissions
        $actions = [
            'list' => 'Lister',
            'create' => 'Créer',
            'view' => 'Afficher',
            'edit' => 'Éditer',
            'delete' => 'Supprimer',
        ];

        $permissions = [];
        $nextPermId = \DB::table('permissions')->max('id') + 1;

        foreach ($features as $feature) {
            // Convert menu_url to singular form (regions -> region, departements -> departement)
            $singular = Str::singular($feature->menu_url);

            foreach ($actions as $action => $actionLabel) {
                $permName = "parametrage-{$singular}-{$action}";
                $permLabel = "{$actionLabel} - {$feature->libelle}";

                // Check if permission already exists
                $exists = \DB::table('permissions')
                    ->where('name', $permName)
                    ->exists();

                if (!$exists) {
                    $permissions[] = [
                        'libelle' => $permLabel,
                        'name' => $permName,
                        'guard_name' => 'web',
                        'source_system' => 'smilpay',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
        }

        // Insert all permissions
        if (count($permissions) > 0) {
            \DB::table('permissions')->insert($permissions);
            $this->line("✓ Created " . count($permissions) . " new permissions\n");
        }

        // Assign all parametrage permissions to super_admin role
        $superAdminRole = \DB::table('roles')->where('name', 'super_admin')->first();

        $parametragePerms = \DB::table('permissions')
            ->where('name', 'like', 'parametrage-%')
            ->get();

        $assignCount = 0;
        foreach ($parametragePerms as $perm) {
            $exists = \DB::table('role_has_permissions')
                ->where('permission_id', $perm->id)
                ->where('role_id', $superAdminRole->id)
                ->exists();

            if (!$exists) {
                \DB::table('role_has_permissions')->insert([
                    'permission_id' => $perm->id,
                    'role_id' => $superAdminRole->id
                ]);
                $assignCount++;
            }
        }

        $this->line("✓ Assigned {$assignCount} permissions to super_admin\n");

        // Clear cache
        \Artisan::call('cache:clear');
        $this->line("✓ Cache cleared\n");

        $this->info("✓ Done! Parametrage permissions fixed!");
    }
}
