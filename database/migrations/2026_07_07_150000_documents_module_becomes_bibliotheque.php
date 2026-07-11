<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spec : « Remplacer le menu Document par Bibliothèque ».
 * Le module 14 « Documents » devient « Bibliothèque » et porte 4 sous-fonctionnalités :
 *   Liste (bibliotheque-structures), Entrée de livres (entrees-livres),
 *   Sortie de livres (sorties-livres), Inventaire (inventaire-livres).
 * Les anciennes features Documents/Catégories sont retirées du menu (soft delete).
 * Idempotent + défensif.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('module') || !Schema::hasTable('feature')) {
            return;
        }

        $now = now();
        $moduleId = DB::table('module')->where('libelle', 'Documents')->value('id')
            ?? DB::table('module')->where('menu_url', 'documents')->value('id')
            ?? DB::table('module')->where('menu_url', 'bibliotheque')->value('id')
            ?? DB::table('module')->where('id', 14)->value('id');

        if (!$moduleId) {
            // Aucun module Documents/14 (ex: base fraîche non seedée) → on crée le module.
            $moduleId = DB::table('module')->insertGetId([
                'libelle'    => 'Bibliothèque',
                'libelle_en' => 'Library',
                'menu_url'   => 'bibliotheque',
                'icone'      => 'fas fa-book',
                'ordre'      => 14,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            // 1) Documents -> Bibliothèque
            DB::table('module')->where('id', $moduleId)->update([
                'libelle'    => 'Bibliothèque',
                'libelle_en' => 'Library',
                'menu_url'   => 'bibliotheque',
                'icone'      => 'fas fa-book',
                'updated_at' => $now,
            ]);
        }

        // 2) Retirer les anciennes fonctionnalités Documents/Catégories du menu
        DB::table('feature')->where('module_id', $moduleId)
            ->whereIn('menu_url', ['documents', 'categories-documents'])
            ->update(['deleted_at' => $now, 'updated_at' => $now]);

        // 3) Les 4 sous-fonctionnalités + permissions
        $features = [
            ['libelle' => 'Liste',            'en' => 'List',         'menu_url' => 'bibliotheque-structures', 'icone' => 'fas fa-book-open',     'ordre' => 1, 'perms' => ['list', 'create', 'edit', 'delete']],
            ['libelle' => 'Entrée de livres', 'en' => 'Book Entries', 'menu_url' => 'entrees-livres',          'icone' => 'fas fa-arrow-down',    'ordre' => 2, 'perms' => ['list', 'create', 'edit', 'delete']],
            ['libelle' => 'Sortie de livres', 'en' => 'Book Exits',   'menu_url' => 'sorties-livres',          'icone' => 'fas fa-arrow-up',      'ordre' => 3, 'perms' => ['list', 'create', 'edit', 'delete']],
            ['libelle' => 'Inventaire',       'en' => 'Inventory',    'menu_url' => 'inventaire-livres',       'icone' => 'fas fa-boxes-stacked', 'ordre' => 4, 'perms' => ['list']],
        ];

        $superAdminId = DB::table('roles')->where('name', 'super_admin')->value('id');

        foreach ($features as $f) {
            $featureId = DB::table('feature')->where('menu_url', $f['menu_url'])->value('id');
            $data = [
                'libelle'    => $f['libelle'],
                'libelle_en' => $f['en'],
                'module_id'  => $moduleId,
                'menu_url'   => $f['menu_url'],
                'icone'      => $f['icone'],
                'ordre'      => $f['ordre'],
                'deleted_at' => null,
                'updated_at' => $now,
            ];

            if (!$featureId) {
                $data['created_at'] = $now;
                $featureId = DB::table('feature')->insertGetId($data);
            } else {
                DB::table('feature')->where('id', $featureId)->update($data);
            }

            foreach ($f['perms'] as $action) {
                $permName = $f['menu_url'] . '-' . $action;
                $permId = DB::table('permissions')->where('name', $permName)->where('guard_name', 'web')->value('id');
                if (!$permId) {
                    $permId = DB::table('permissions')->insertGetId([
                        'name'       => $permName,
                        'libelle'    => ucfirst($action) . ' ' . $f['libelle'],
                        'guard_name' => 'web',
                        'feature_id' => $featureId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('permissions')->where('id', $permId)->update(['feature_id' => $featureId, 'deleted_at' => null]);
                }

                if ($superAdminId && $permId) {
                    $exists = DB::table('role_has_permissions')
                        ->where('role_id', $superAdminId)->where('permission_id', $permId)->exists();
                    if (!$exists) {
                        DB::table('role_has_permissions')->insert(['role_id' => $superAdminId, 'permission_id' => $permId]);
                    }
                }
            }
        }

        // Vider les caches menu/permissions pour affichage immédiat.
        try { Cache::flush(); } catch (\Throwable $e) { /* ignore */ }
    }

    public function down(): void
    {
        if (!Schema::hasTable('module')) {
            return;
        }
        $moduleId = DB::table('module')->where('menu_url', 'bibliotheque')->value('id') ?? 14;
        DB::table('module')->where('id', $moduleId)->update([
            'libelle'    => 'Documents',
            'libelle_en' => 'Documents',
            'menu_url'   => 'documents',
            'updated_at' => now(),
        ]);
        try { Cache::flush(); } catch (\Throwable $e) {}
    }
};
