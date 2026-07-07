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

        // §10.7 : la gestion bibliothèque a migré vers RessourcesLogistique.
        //   Les URLs pointent désormais vers les routes de ce module :
        //     - /bibliotheques (BibliothequeController)
        //     - /ouvrages     (OuvrageController)
        //     - /exemplaires  (ExemplaireController)
        //     - /emprunts     (EmpruntController)
        //   Les entrées legacy `bibliotheque-structures`, `entrees-livres`,
        //   `sorties-livres`, `inventaire-livres` sont purgées par la migration
        //   `2026_07_07_160000_purge_matiere_feature_variants.php` (Bibliothèque
        //   academic → retiré).
        $features = [
            ['menu_url' => 'bibliotheques', 'libelle' => 'Bibliothèques', 'libelle_en' => 'Libraries', 'icone' => 'fas fa-book-open'],
            ['menu_url' => 'ouvrages',      'libelle' => 'Ouvrages',      'libelle_en' => 'Books',      'icone' => 'fas fa-book'],
            ['menu_url' => 'exemplaires',   'libelle' => 'Exemplaires',   'libelle_en' => 'Copies',     'icone' => 'fas fa-books'],
            ['menu_url' => 'emprunts',      'libelle' => 'Emprunts',      'libelle_en' => 'Loans',      'icone' => 'fas fa-hand-holding'],
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
