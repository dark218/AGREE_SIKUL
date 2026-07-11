<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AllPermissionsSeeder extends Seeder
{
    public function run()
    {
        // List of all resources with their Parametrage module names (40 modules)
        $resources = [
            'parametrage-anneescolaire' => 'Années Scolaires',
            'parametrage-banque' => 'Banques',
            'parametrage-campus' => 'Campus',
            'parametrage-categorieapprenant' => 'Catégories Apprenant',
            'parametrage-categorieenseignant' => 'Catégories Enseignant',
            'parametrage-classe' => 'Classes',
            'parametrage-commune' => 'Communes',
            'parametrage-cycleenseignement' => 'Cycles Enseignement',
            'parametrage-departement' => 'Départements',
            'parametrage-devise' => 'Devises',
            'parametrage-devisepays' => 'Devises Pays',
            'parametrage-ecole' => 'Écoles',
            'parametrage-fichier' => 'Fichiers',
            'parametrage-fonction' => 'Fonctions',
            'parametrage-fournisseurpaiement' => 'Fournisseurs Paiement',
            'parametrage-groupematiere' => 'Groupes Matière',
            'parametrage-institution' => 'Institutions',
            'parametrage-jourferie' => 'Jours Fériés',
            'parametrage-matiereunite' => 'Matière Unités',
            'parametrage-modepaiement' => 'Modes Paiement',
            'parametrage-natureexamen' => 'Natures Examens',
            'parametrage-naturecontrat' => 'Natures Contrat',
            'parametrage-niveau' => 'Niveaux',
            'parametrage-niveauetude' => 'Niveaux Études',
            'parametrage-pays' => 'Pays',
            'parametrage-periodescolaire' => 'Périodes Scolaires',
            'parametrage-quartier' => 'Quartiers',
            'parametrage-region' => 'Régions',
            'parametrage-section' => 'Sections',
            'parametrage-titrecivilite' => 'Titres Civilité',
            'parametrage-typeapprenant' => 'Types Apprenants',
            'parametrage-typecours' => 'Types Cours',
            'parametrage-typeetablissement' => 'Types Établissements',
            'parametrage-typeexamen' => 'Types Examens',
            'parametrage-typeenseignement' => 'Types Enseignement',
            'parametrage-typeressource' => 'Types Ressource',
            'parametrage-typeetablissementspe' => 'Types Établissement Spé',
            'parametrage-typeevenement' => 'Types Événement',
            'parametrage-uniteorganisationnelle' => 'Unités Organisationnelles',
            'parametrage-zone' => 'Zones',
        ];

        $actions = ['list', 'create', 'edit', 'delete', 'statut', 'activate'];

        echo "\n=== Creating Permissions ===\n";

        foreach ($resources as $resourceKey => $label) {
            foreach ($actions as $action) {
                $permName = $resourceKey . '-' . $action;
                $permLabel = ucfirst($action) . ' ' . $label;

                $perm = Permission::firstOrCreate(
                    ['name' => $permName],
                    ['guard_name' => 'web', 'libelle' => $permLabel]
                );
                echo "✅ {$permName}\n";
            }
        }

        echo "\n=== Assigning to All Roles ===\n";

        $permNames = [];
        foreach ($resources as $resourceKey => $label) {
            foreach ($actions as $action) {
                $permNames[] = $resourceKey . '-' . $action;
            }
        }

        $permissionIds = Permission::whereIn('name', $permNames)->pluck('id')->all();
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
            echo "✅ Assigned to {$role->name} ({$permissionCount} permissions)\n";
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        echo "\n✅ All permissions created and assigned!\n";
    }
}
