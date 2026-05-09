<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RbacPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // Entités académiques et administratives
        $entities = [
            // Structures
            'institutions', 'campuses', 'ecoles', 'niveaux', 'classes', 'annees_scolaires',

            // Apprenants et tuteurs
            'apprenants', 'tuteurs', 'inscriptions', 'dossiers_apprenants',

            // Personnel
            'enseignants', 'personnels_administratifs', 'absences_enseignants',

            // Cours et horaires
            'matieres', 'cours', 'seances', 'emplois_temps',

            // Présence et absences
            'presences', 'presences_seances', 'absences_apprenants', 'justificatifs_absences',

            // Évaluations
            'evaluations', 'notes', 'bulletins', 'moyennes_matieres',

            // Devoirs
            'devoirs', 'rendus_devoirs',

            // Communication
            'messages', 'annonces', 'commentaires_annonces', 'notifications',

            // Finances
            'types_frais', 'frais', 'paiements', 'echeanciers', 'depenses',

            // Bibliothèque
            'bibliotheques', 'ouvrages', 'exemplaires', 'emprunts', 'reservations',

            // Services
            'services_cantines', 'menus', 'inscriptions_cantines', 'passages_cantines',
            'services_transports', 'inscriptions_transports', 'consultations_infirmeries',

            // Documents et Inventaire
            'categories_documents', 'documents',
            'categories_equipements', 'equipements', 'maintenances_equipements',
            'modeles_rapports', 'rapports',
        ];

        // Actions CRUD
        $actions = ['list', 'create', 'read', 'update', 'delete', 'export'];

        // Créer les permissions
        $actionLabels = [
            'list' => 'Liste',
            'create' => 'Nouveau',
            'read' => 'Voir',
            'update' => 'Modifier',
            'delete' => 'Supprimer',
            'export' => 'Exporter',
        ];

        $permissions = [];
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $permissionName = $entity . '-' . $action;
                $permission = Permission::firstOrCreate(
                    ['name' => $permissionName],
                    [
                        'guard_name' => 'web',
                        'libelle' => $actionLabels[$action] ?? ucfirst($action)
                    ]
                );
                $permissions[$permissionName] = $permission;
            }
        }

        // Créer les rôles avec leurs permissions
        $this->createRolesWithPermissions($permissions, $entities, $actions);

        $this->command->info('RBAC Permissions created successfully!');
    }

    private function createRolesWithPermissions($permissions, $entities, $actions)
    {
        // Super Admin - Toutes les permissions
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );
        $superAdmin->syncPermissions(array_values($permissions));

        // Directeur Général
        $directorGeneral = Role::firstOrCreate(
            ['name' => 'directeur_general'],
            ['guard_name' => 'web']
        );
        $dgPermissions = $this->getPermissions($permissions, [
            'institutions', 'campuses', 'ecoles', 'apprenants', 'enseignants',
            'inscriptions', 'frais', 'paiements', 'depenses', 'rapports',
            'bulletins', 'absences_apprenants'
        ], ['list', 'read', 'update', 'export']);
        $directorGeneral->syncPermissions($dgPermissions);

        // Directeur Campus
        $directorCampus = Role::firstOrCreate(
            ['name' => 'directeur_campus'],
            ['guard_name' => 'web']
        );
        $campusPermissions = $this->getPermissions($permissions, [
            'campuses', 'ecoles', 'classes', 'apprenants', 'enseignants',
            'inscriptions', 'frais', 'bulletins', 'absences_apprenants',
            'services_cantines', 'services_transports'
        ], ['list', 'read', 'update', 'create', 'delete', 'export']);
        $directorCampus->syncPermissions($campusPermissions);

        // Directeur École
        $directorSchool = Role::firstOrCreate(
            ['name' => 'directeur_ecole'],
            ['guard_name' => 'web']
        );
        $schoolPermissions = $this->getPermissions($permissions, [
            'ecoles', 'niveaux', 'classes', 'apprenants', 'enseignants',
            'inscriptions', 'matieres', 'cours', 'emplois_temps',
            'bulletins', 'absences_apprenants', 'absences_enseignants',
            'frais', 'depenses', 'equipements', 'documents'
        ], ['list', 'read', 'create', 'update', 'export']);
        $directorSchool->syncPermissions($schoolPermissions);

        // Enseignant
        $teacher = Role::firstOrCreate(
            ['name' => 'enseignant'],
            ['guard_name' => 'web']
        );
        $teacherPermissions = $this->getPermissions($permissions, [
            'classes', 'apprenants', 'inscriptions', 'evaluations', 'notes',
            'devoirs', 'rendus_devoirs', 'seances', 'presences_seances',
            'absences_apprenants', 'bulletins', 'messages', 'annonces',
            'moyennes_matieres'
        ], ['list', 'read', 'create', 'update', 'export']);
        $teacher->syncPermissions($teacherPermissions);

        // Parent/Tuteur
        $parent = Role::firstOrCreate(
            ['name' => 'parent'],
            ['guard_name' => 'web']
        );
        $parentPermissions = $this->getPermissions($permissions, [
            'apprenants', 'bulletins', 'absences_apprenants',
            'messages', 'annonces', 'notifications'
        ], ['list', 'read']);
        $parent->syncPermissions($parentPermissions);

        // Élève
        $student = Role::firstOrCreate(
            ['name' => 'eleve'],
            ['guard_name' => 'web']
        );
        $studentPermissions = $this->getPermissions($permissions, [
            'devoirs', 'rendus_devoirs', 'notes', 'bulletins',
            'messages', 'annonces', 'notifications', 'reservations',
            'emprunts'
        ], ['list', 'read', 'create']);
        $student->syncPermissions($studentPermissions);

        // Personnel Administratif
        $admin = Role::firstOrCreate(
            ['name' => 'personnel_administratif'],
            ['guard_name' => 'web']
        );
        $adminPermissions = $this->getPermissions($permissions, [
            'apprenants', 'inscriptions', 'dossiers_apprenants', 'frais',
            'paiements', 'echeanciers', 'absences_apprenants',
            'depenses', 'documents', 'equipements'
        ], ['list', 'read', 'create', 'update', 'export']);
        $admin->syncPermissions($adminPermissions);

        // Bibliothécaire
        $librarian = Role::firstOrCreate(
            ['name' => 'bibliothecaire'],
            ['guard_name' => 'web']
        );
        $librarianPermissions = $this->getPermissions($permissions, [
            'bibliotheques', 'ouvrages', 'exemplaires', 'emprunts',
            'reservations', 'documents'
        ], ['list', 'read', 'create', 'update', 'delete', 'export']);
        $librarian->syncPermissions($librarianPermissions);

        // Infirmier
        $nurse = Role::firstOrCreate(
            ['name' => 'infirmier'],
            ['guard_name' => 'web']
        );
        $nursePermissions = $this->getPermissions($permissions, [
            'apprenants', 'consultations_infirmeries', 'documents',
            'messages', 'notifications'
        ], ['list', 'read', 'create', 'update', 'export']);
        $nurse->syncPermissions($nursePermissions);

        // Agent de Sécurité
        $security = Role::firstOrCreate(
            ['name' => 'agent_securite'],
            ['guard_name' => 'web']
        );
        $securityPermissions = $this->getPermissions($permissions, [
            'apprenants', 'absences_apprenants', 'presences_seances',
            'passages_cantines'
        ], ['list', 'read', 'update']);
        $security->syncPermissions($securityPermissions);
    }

    private function getPermissions($permissions, $entities, $actions)
    {
        $result = [];
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $key = $entity . '-' . $action;
                if (isset($permissions[$key])) {
                    $result[] = $permissions[$key];
                }
            }
        }
        return $result;
    }
}
