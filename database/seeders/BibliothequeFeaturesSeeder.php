<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Enregistre les fonctionnalités de la Bibliothèque (Liste, Entrées, Sorties,
 * Inventaire) dans le module Académique + leurs permissions, et les assigne aux
 * rôles administrateurs. Idempotent.
 *
 * A lancer : php artisan db:seed --class=Database\\Seeders\\BibliothequeFeaturesSeeder
 */
class BibliothequeFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $moduleId = DB::table('module')
            ->whereIn('libelle', ['Académique', 'Academique'])
            ->value('id') ?? 25;

        $features = [
            ['menu_url' => 'bibliotheque-structures', 'libelle' => 'Bibliothèques', 'libelle_en' => 'Libraries', 'icone' => 'fas fa-book-open'],
            ['menu_url' => 'entrees-livres', 'libelle' => 'Entrées de livres', 'libelle_en' => 'Book Entries', 'icone' => 'fas fa-arrow-down'],
            ['menu_url' => 'sorties-livres', 'libelle' => 'Sorties de livres', 'libelle_en' => 'Book Exits', 'icone' => 'fas fa-arrow-up'],
            ['menu_url' => 'inventaire-livres', 'libelle' => 'Inventaire', 'libelle_en' => 'Inventory', 'icone' => 'fas fa-boxes-stacked'],
        ];

        foreach ($features as $f) {
            $featureId = DB::table('feature')->where('menu_url', $f['menu_url'])->value('id');
            if (!$featureId) {
                $maxOrdre = (int) DB::table('feature')->where('module_id', $moduleId)->max('ordre');
                $featureId = DB::table('feature')->insertGetId([
                    'libelle' => $f['libelle'],
                    'libelle_en' => $f['libelle_en'],
                    'module_id' => $moduleId,
                    'menu_url' => $f['menu_url'],
                    'icone' => $f['icone'],
                    'ordre' => $maxOrdre + 1,
                    'source_system' => 'agree_sikul',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $perms = [];
            foreach (['list', 'create', 'update', 'edit', 'delete'] as $action) {
                $perms[] = Permission::firstOrCreate(
                    ['name' => $f['menu_url'] . '-' . $action, 'guard_name' => 'web'],
                    ['libelle' => ucfirst($action) . ' - ' . $f['libelle'], 'feature_id' => $featureId]
                );
            }

            foreach (['super_admin', 'superadmin', 'admin', 'directeur_general'] as $roleName) {
                optional(Role::where('name', $roleName)->first())->givePermissionTo($perms);
            }
        }
    }
}
