<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'super_admin' => [
                'name' => 'Super Admin',
                'description' => 'Accès complet au système',
            ],
            'directeur_general' => [
                'name' => 'Directeur Général',
                'description' => 'Gestion globale de l\'institution',
            ],
            'directeur_campus' => [
                'name' => 'Directeur Campus',
                'description' => 'Gestion du campus et ses écoles',
            ],
            'directeur_ecole' => [
                'name' => 'Directeur École',
                'description' => 'Gestion de l\'école',
            ],
            'enseignant' => [
                'name' => 'Enseignant',
                'description' => 'Gestion des cours et notes',
            ],
            'personnel_administratif' => [
                'name' => 'Personnel Administratif',
                'description' => 'Support administratif',
            ],
            'parent' => [
                'name' => 'Parent/Tuteur',
                'description' => 'Suivi de l\'enfant',
            ],
            'eleve' => [
                'name' => 'Élève',
                'description' => 'Accès aux ressources étudiantes',
            ],
            'bibliothecaire' => [
                'name' => 'Bibliothécaire',
                'description' => 'Gestion de la bibliothèque',
            ],
            'infirmier' => [
                'name' => 'Infirmier',
                'description' => 'Gestion de l\'infirmerie',
            ],
            'agent_securite' => [
                'name' => 'Agent de Sécurité',
                'description' => 'Sécurité du campus',
            ],
        ];

        foreach ($roles as $key => $roleData) {
            Role::firstOrCreate(
                ['name' => $key],
                [
                    'guard_name' => 'web',
                ]
            );
        }

        // Super admin obtient toutes les permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);

        // Directeur général : accès large (incluant Parametrage)
        $directorRole = Role::firstOrCreate(['name' => 'directeur_general']);
        $directorPermissions = Permission::where('name', 'like', '%devise%')
            ->orWhere('name', 'like', '%pays%')
            ->orWhere('name', 'like', '%zones%')
            ->orWhere('name', 'like', '%regions%')
            ->orWhere('name', 'like', '%departements%')
            ->orWhere('name', 'like', '%communes%')
            ->orWhere('name', 'like', '%institutions%')
            ->orWhere('name', 'like', '%campuses%')
            ->orWhere('name', 'like', '%ecoles%')
            ->orWhere('name', 'like', '%niveaux%')
            ->orWhere('name', 'like', '%classes%')
            ->orWhere('name', 'like', '%apprenants%')
            ->orWhere('name', 'like', '%enseignants%')
            ->orWhere('name', 'like', '%inscriptions%')
            ->orWhere('name', 'like', '%bulletins%')
            ->get();
        $directorRole->syncPermissions($directorPermissions);

        // Directeur école (incluant Parametrage)
        $ecolDirector = Role::firstOrCreate(['name' => 'directeur_ecole']);
        $ecolePermissions = Permission::where('name', 'like', '%devise%')
            ->orWhere('name', 'like', '%pays%')
            ->orWhere('name', 'like', '%zones%')
            ->orWhere('name', 'like', '%regions%')
            ->orWhere('name', 'like', '%communes%')
            ->orWhere('name', 'like', '%ecoles%')
            ->orWhere('name', 'like', '%classes%')
            ->orWhere('name', 'like', '%apprenants%')
            ->orWhere('name', 'like', '%enseignants%')
            ->orWhere('name', 'like', '%cours%')
            ->orWhere('name', 'like', '%matieres%')
            ->orWhere('name', 'like', '%bulletins%')
            ->orWhere('name', 'like', '%evaluations%')
            ->get();
        $ecolDirector->syncPermissions($ecolePermissions);

        // Enseignant (incluant Parametrage pour configurations)
        $teacherRole = Role::firstOrCreate(['name' => 'enseignant']);
        $teacherPermissions = Permission::where('name', 'like', '%devise%')
            ->orWhere('name', 'like', '%pays%')
            ->orWhere('name', 'like', '%cours%')
            ->orWhere('name', 'like', '%matieres%')
            ->orWhere('name', 'like', '%notes%')
            ->orWhere('name', 'like', '%evaluations%')
            ->orWhere('name', 'like', '%seances%')
            ->orWhere('name', 'like', '%apprenants-view%')
            ->orWhere('name', 'like', '%bulletins-view%')
            ->get();
        $teacherRole->syncPermissions($teacherPermissions);

        // Parent : accès limité
        $parentRole = Role::firstOrCreate(['name' => 'parent']);
        $parentPermissions = Permission::where('name', 'like', '%apprenants-view%')
            ->orWhere('name', 'like', '%bulletins-view%')
            ->orWhere('name', 'like', '%inscriptions-view%')
            ->get();
        $parentRole->syncPermissions($parentPermissions);

        // Élève : accès très limité
        $studentRole = Role::firstOrCreate(['name' => 'eleve']);
        $studentPermissions = Permission::where('name', 'like', '%bulletins-view%')
            ->orWhere('name', 'like', '%cours-view%')
            ->orWhere('name', 'like', '%documents-view%')
            ->get();
        $studentRole->syncPermissions($studentPermissions);

        $this->command->info('✅ ' . count($roles) . ' rôles créés avec permissions');
    }
}
