<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionMatrixSeeder extends Seeder
{
    /**
     * Matrice des accès par rôle
     * Chaque rôle a une liste de menu_url auxquels il a accès
     * Les permissions list/create/edit/delete/statut seront assignées automatiquement
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $matrix = [
            // =====================================================
            // SUPER ADMIN — TOUT
            // =====================================================
            'super_admin' => 'ALL',

            // =====================================================
            // DIRECTEUR GÉNÉRAL — Vue globale, pas d'admin technique
            // =====================================================
            'directeur_general' => [
                // Académique
                'apprenants', 'enseignants', 'bulletins', 'notes', 'examens-en-ligne',
                'resultats-examens', 'emplois-du-temps', 'moyennes-matieres', 'cours',
                'seances', 'devoirs', 'rendus-devoirs', 'evaluations', 'inscriptions',
                'dossiers-apprenants', 'absences', 'absences-apprenants', 'absences-enseignants',
                'presences', 'presences-seances', 'classes-apprenants', 'passages',
                'affectations-enseignants', 'manuels', 'bibliotheques',
                'planification-examens', 'exam-finance',
                // Finances
                'ecolage', 'versements', 'salaires', 'achats-depenses', 'autres-revenus',
                'facturations-apprenants', 'groupes-comptes', 'lignes-recettes', 'lignes-depenses',
                'postes-recettes', 'postes-depenses', 'rapports-financiers',
                // Personnel
                'tuteurs', 'parents', 'accompagnateurs', 'personnels-administratifs',
                // Services
                'cantine', 'menus', 'inscriptions-cantine', 'passages', 'services-transport', 'inscriptions-transport',
                // Communication
                'annonces', 'messages',
                // Ressources
                'ouvrages', 'exemplaires', 'emprunts', 'reservations',
                // Rapports
                'statistiques-ecole', 'statistiques-classes', 'rapports',
            ],

            // =====================================================
            // DIRECTEUR CAMPUS / ÉCOLE — Gestion de son établissement
            // =====================================================
            'directeur_campus' => [
                'apprenants', 'enseignants', 'bulletins', 'notes', 'examens-en-ligne',
                'resultats-examens', 'emplois-du-temps', 'moyennes-matieres', 'cours',
                'seances', 'devoirs', 'rendus-devoirs', 'evaluations', 'inscriptions',
                'dossiers-apprenants', 'absences', 'absences-apprenants', 'absences-enseignants',
                'presences', 'presences-seances', 'classes-apprenants', 'passages',
                'affectations-enseignants', 'manuels', 'bibliotheques',
                'planification-examens',
                'ecolage', 'versements', 'facturations-apprenants',
                'personnels-administratifs', 'accompagnateurs',
                'cantine', 'menus', 'inscriptions-cantine',
                'annonces',
                'statistiques-ecole', 'statistiques-classes', 'rapports',
            ],

            'directeur_ecole' => [
                'apprenants', 'enseignants', 'bulletins', 'notes', 'examens-en-ligne',
                'resultats-examens', 'emplois-du-temps', 'moyennes-matieres', 'cours',
                'seances', 'devoirs', 'rendus-devoirs', 'evaluations', 'inscriptions',
                'dossiers-apprenants', 'absences', 'absences-apprenants', 'absences-enseignants',
                'presences', 'presences-seances', 'classes-apprenants', 'passages',
                'affectations-enseignants', 'manuels', 'bibliotheques',
                'planification-examens',
                'ecolage', 'versements', 'facturations-apprenants',
                'personnels-administratifs', 'accompagnateurs',
                'cantine', 'menus', 'inscriptions-cantine',
                'annonces',
                'statistiques-ecole', 'statistiques-classes', 'rapports',
            ],

            // =====================================================
            // ENSEIGNANT — Ses cours, notes, examens
            // =====================================================
            'enseignant' => [
                'cours', 'seances', 'notes', 'devoirs', 'rendus-devoirs',
                'examens-en-ligne', 'resultats-examens', 'emplois-du-temps',
                'absences-apprenants', 'presences', 'presences-seances',
                'evaluations', 'moyennes-matieres', 'bulletins',
                'planification-examens',
                'annonces',
            ],

            // =====================================================
            // APPRENANT — Son parcours
            // =====================================================
            'apprenant' => [
                'mes-examens', 'composition-examens',
                'emplois-du-temps', 'devoirs',
            ],

            'eleve' => [
                'mes-examens', 'composition-examens',
                'emplois-du-temps', 'devoirs',
            ],

            // =====================================================
            // PARENT — Suivi de son enfant
            // =====================================================
            'parent' => [
                'bulletins', 'notes', 'absences-apprenants',
                'emplois-du-temps', 'annonces', 'messages',
                'ecolage',
            ],

            // =====================================================
            // PERSONNEL ADMINISTRATIF — Gestion quotidienne
            // =====================================================
            'personnel_administratif' => [
                'apprenants', 'inscriptions', 'dossiers-apprenants',
                'classes-apprenants', 'absences', 'absences-apprenants',
                'presences', 'presences-seances', 'bulletins',
                'ecolage', 'versements', 'facturations-apprenants',
                'paiements', 'echeanciers',
                'cantine', 'inscriptions-cantine', 'services-transport', 'inscriptions-transport',
                'annonces', 'messages', 'notifications',
            ],

            // =====================================================
            // BIBLIOTHÉCAIRE — Bibliothèque
            // =====================================================
            'bibliothecaire' => [
                'bibliotheques', 'ouvrages', 'exemplaires', 'emprunts', 'reservations',
            ],

            // =====================================================
            // INFIRMIER — Infirmerie
            // =====================================================
            'infirmier' => [
                'consultations-infirmerie',
                'apprenants',
            ],

            // =====================================================
            // AGENT SÉCURITÉ / GARDIEN — Contrôle accès
            // =====================================================
            'agent_securite' => [
                'passages-cantine', 'apprenants',
            ],

            'GARDIEN' => [
                'passages-cantine', 'apprenants',
            ],
        ];

        // Actions possibles par feature
        $actions = ['list', 'create', 'edit', 'delete', 'activate', 'update', 'statut'];

        // Rôles lecture seule (pas de create/edit/delete)
        $readOnlyRoles = ['parent', 'apprenant', 'eleve', 'agent_securite', 'GARDIEN'];

        // Charge toutes les permissions en mémoire UNE fois : [name => id]
        $permIdByName = Permission::pluck('id', 'name')->all();
        $allPermIds = array_values($permIdByName);
        $allPermCount = count($allPermIds);

        // Charge tous les rôles en une fois : [name => id]
        $roleIdByName = Role::pluck('id', 'name')->all();

        $pivotTable = config('permission.table_names.role_has_permissions', 'role_has_permissions');

        foreach ($matrix as $roleName => $features) {
            if (!isset($roleIdByName[$roleName])) {
                $this->command->warn("Rôle '$roleName' non trouvé, ignoré.");
                continue;
            }
            $roleId = $roleIdByName[$roleName];

            if ($features === 'ALL') {
                $this->syncRolePermissions($pivotTable, $roleId, $allPermIds);
                $this->command->info("$roleName: TOUTES les permissions ($allPermCount)");
                continue;
            }

            $allowedActions = in_array($roleName, $readOnlyRoles) ? ['list'] : $actions;

            $permIds = [];
            foreach ($features as $menuUrl) {
                foreach ($allowedActions as $action) {
                    foreach ([
                        $menuUrl . '-' . $action,
                        str_replace('-', '_', $menuUrl) . '-' . $action,
                        str_replace('_', '-', $menuUrl) . '-' . $action,
                    ] as $permName) {
                        if (isset($permIdByName[$permName])) {
                            $permIds[$permIdByName[$permName]] = true;
                            break;
                        }
                    }
                }
            }
            $permIds = array_keys($permIds);

            $this->syncRolePermissions($pivotTable, $roleId, $permIds);
            $this->command->info("$roleName: " . count($permIds) . " permissions assignées");
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info("\nMatrice des permissions appliquée avec succès !");
    }

    /**
     * Remplace les permissions d'un rôle via insert direct par chunks
     * pour éviter la surcharge mémoire de Spatie\Permission\syncPermissions.
     */
    private function syncRolePermissions(string $pivotTable, int $roleId, array $permIds): void
    {
        DB::table($pivotTable)->where('role_id', $roleId)->delete();

        if (empty($permIds)) {
            return;
        }

        foreach (array_chunk($permIds, 500) as $chunk) {
            $rows = array_map(fn ($pid) => [
                'permission_id' => $pid,
                'role_id' => $roleId,
            ], $chunk);
            DB::table($pivotTable)->insert($rows);
        }
    }
}
