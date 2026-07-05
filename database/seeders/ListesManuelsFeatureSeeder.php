<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Enregistre la fonctionnalité "Liste des manuels et fournitures" (menu_url:
 * listes-manuels) dans le module Académique + ses permissions, et les assigne
 * aux rôles administrateurs. Idempotent.
 *
 * A lancer : php artisan db:seed --class=Database\\Seeders\\ListesManuelsFeatureSeeder
 */
class ListesManuelsFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $menuUrl = 'listes-manuels';
        $libelle = 'Liste des manuels et fournitures';

        $moduleId = DB::table('module')
            ->whereIn('libelle', ['Académique', 'Academique'])
            ->value('id') ?? 25;

        $featureId = DB::table('feature')->where('menu_url', $menuUrl)->value('id');
        if (!$featureId) {
            $maxOrdre = (int) DB::table('feature')->where('module_id', $moduleId)->max('ordre');
            $featureId = DB::table('feature')->insertGetId([
                'libelle' => $libelle,
                'libelle_en' => 'Textbooks & Supplies List',
                'module_id' => $moduleId,
                'menu_url' => $menuUrl,
                'icone' => 'fas fa-book',
                'ordre' => $maxOrdre + 1,
                'source_system' => 'agree_sikul',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $perms = [];
        foreach (['list', 'create', 'update', 'edit', 'delete'] as $action) {
            $perms[] = Permission::firstOrCreate(
                ['name' => $menuUrl . '-' . $action, 'guard_name' => 'web'],
                ['libelle' => ucfirst($action) . ' - ' . $libelle, 'feature_id' => $featureId]
            );
        }

        foreach (['super_admin', 'superadmin', 'admin', 'directeur_general'] as $roleName) {
            optional(Role::where('name', $roleName)->first())->givePermissionTo($perms);
        }
    }
}
