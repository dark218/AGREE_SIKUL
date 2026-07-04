<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FixPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Clear cache
        app()['cache']->forget('spatie.permission.cache');

        $permissions = [
            // ACADEMIQUE PERMISSIONS (EXACT names as used in controllers)
            'absences_apprenants' => 'Absences Apprenants',
            'absences_enseignants' => 'Absences Enseignants',
            'affectations-enseignants' => 'Affectations Enseignants',
            'affectations_enseignants' => 'Affectations Enseignants',
            'apprenants' => 'Apprenants',
            'bulletins' => 'Bulletins',
            'classes-apprenants' => 'Classes Apprenants',
            'cours' => 'Cours',
            'devoirs' => 'Devoirs',
            'dossiers_apprenants' => 'Dossiers Apprenants',
            'emploi-temps' => 'Emplois Temps',
            'enseignants' => 'Enseignants',
            'evaluations' => 'Évaluations',
            'inscriptions' => 'Inscriptions',
            'justificatifs-absences' => 'Justificatifs Absences',
            'justificatifs_absences' => 'Justificatifs Absences',
            'listes-manuels' => 'Listes Manuels',
            'manuels' => 'Manuels',
            'matieres' => 'Matières',
            'moyennes-matieres' => 'Moyennes Matières',
            'moyennes_matieres' => 'Moyennes Matières',
            'notes' => 'Notes',
            'passages' => 'Passages',
            'personnels-administratifs' => 'Personnels Administratifs',
            'admin' => 'Administrateurs',
            'agent' => 'Agents',
            'parents' => 'Parents',
            'service_client' => 'Service Client',
            'service_validateur' => 'Service Validateur',
            'accompagnateurs' => 'Accompagnateurs',
            'presence-seances' => 'Présences Séances',
            'presences-seances' => 'Présences Séances',
            'presences_seances' => 'Présences Séances',
            'presences_seances-seance' => 'Présences Séances',
            'presences' => 'Présences',
            'rendus_devoirs' => 'Rendus Devoirs',
            'seances' => 'Séances',
            'tuteurs' => 'Tuteurs',
            'planification-examens' => 'Planifications Examens',
            'examens-en-ligne' => 'Examens En Ligne',
            'composition-examens' => 'Composition Examens',
            'resultats-examens' => 'Résultats Examens',
            'exam-finance' => 'Financements d\'Examens',

            // FINANCES PERMISSIONS (mostly SINGULAR as used in controllers)
            'ecolage' => 'Écolages',
            'versement' => 'Versements',
            'finances-salaire' => 'Salaires',
            'finances-achats-depenses' => 'Achats et Dépenses',
            'finances-facturation-apprenant' => 'Facturation Apprenants',
            'depenses' => 'Dépenses',
            'frais' => 'Frais',
            'types-frais' => 'Types Frais',
            'paiements' => 'Paiements',
            'echeanciers' => 'Échéanciers',
            'configurations-finances' => 'Configurations Finances',
            'autres-revenus' => 'Autres Revenus',
            'groupes-comptes' => 'Groupes Comptes',
            'lignes-recettes' => 'Lignes Recettes',
            'lignes-depenses' => 'Lignes Dépenses',
            'postes-recettes' => 'Postes Recettes',
            'postes-depenses' => 'Postes Dépenses',
            'exam-finance-report' => 'Rapports Financement d\'Examens',
            'rapports-financiers' => 'Rapports Financiers',

            // SERVICES PERMISSIONS
            'services-transport' => 'Services Transport',
            'inscriptions-transport' => 'Inscriptions Transport',
            'services-cantine' => 'Services Cantine',
            'menus' => 'Menus',
            'inscriptions-cantine' => 'Inscriptions Cantine',
            'passages-cantine' => 'Passages Cantine',

            // COMMUNICATION PERMISSIONS
            'messages' => 'Messages',
            'annonces' => 'Annonces',
            'notifications' => 'Notifications',
            'commentaires-annonces' => 'Commentaires Annonces',
            'traductions' => 'Traductions',

            // ACADEMIQUE BIBLIOTHÈQUES PERMISSIONS
            'bibliotheques' => 'Bibliothèques',

            // RESSOURCES LOGISTIQUE PERMISSIONS (ALL 10 MODULES)
            'ouvrages' => 'Ouvrages',
            'categorie_document' => 'Catégories Documents',
            'equipements' => 'Équipements',
            'documents' => 'Documents',
            'exemplaires' => 'Exemplaires',
            'emprunts' => 'Emprunts',
            'reservations' => 'Réservations',
            'bibliotheque' => 'Bibliothèque',
            'maintenance_equipement' => 'Maintenance Équipements',
            'categorie_equipement' => 'Catégories Équipements',

            // PARAMETRAGE PERMISSIONS (ALL 31 PARAMETRAGE ENTITIES - USING UNDERSCORES TO MATCH VUE COMPONENTS)
            'titres_civilites' => 'Titres Civilités',
            'types_cours' => 'Types Cours',
            'types_enseignements' => 'Types Enseignements',
            'types_etablissements' => 'Types Établissements',
            'pays' => 'Pays',
            'regions' => 'Régions',
            'institutions' => 'Institutions',
            'campuses' => 'Campus',
            'ecoles' => 'Écoles',
            'classes' => 'Classes',
            'communes' => 'Communes',
            'sections' => 'Sections',
            'devise' => 'Devises',
            'cycles_enseignement' => 'Cycles Enseignement',
            'departements' => 'Départements',
            'groupe_matieres' => 'Groupes Matière',
            'niveaux_etudes' => 'Niveaux Étude',
            'quartiers' => 'Quartiers',
            'matieres_unites' => 'Matières Unités',
            'types_apprenants' => 'Types Apprenants',
            'zone' => 'Zones',
            'categories_apprenants' => 'Catégories Apprenants',
            'unites_organisationnelles' => 'Unités Organisationnelles',
            'jours_feries' => 'Jours Fériés',
            'types_examens' => 'Types Examens',
            'types_ressources' => 'Types Ressources',
            'types_evenements' => 'Types Événements',
            'natures_contrats' => 'Natures Contrats',
            'annees_scolaires' => 'Années Scolaires',
            'periodes_scolaires' => 'Périodes Scolaires',
            'categories_enseignants' => 'Catégories Enseignants',
            'natures-examens' => 'Natures Examens',
            'paysdevise' => 'Devises Pays',
            'types_etablissement_spe' => 'Types Établissements Spécialisés',
            'fournisseur_paiement' => 'Fournisseurs Paiements',
            'banque' => 'Banques',
            'niveau' => 'Niveaux',
            'matiere' => 'Matières',
            'fichier' => 'Fichiers',
            'fonction' => 'Fonctions',
        ];

        $actions = ['list', 'create', 'edit', 'delete', 'activate', 'update', 'statut'];

        echo "\n=== Creating CORRECT Permissions ===\n";

        $allPermissions = [];
        foreach ($permissions as $key => $label) {
            foreach ($actions as $action) {
                $permName = $key . '-' . $action;
                $permLabel = ucfirst($action) . ' ' . $label;

                $perm = Permission::firstOrCreate(
                    ['name' => $permName],
                    ['guard_name' => 'web', 'libelle' => $permLabel]
                );
                echo "✅ {$permName}\n";
                $allPermissions[] = $permName;
            }
        }

        echo "\n=== Assigning to All Roles ===\n";

        // Récupère les IDs en 1 requête: créés ici + parametrage-*
        $permissionIds = Permission::where(function ($q) use ($allPermissions) {
            $q->whereIn('name', $allPermissions)
              ->orWhere('name', 'like', 'parametrage-%');
        })->pluck('id')->all();
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
            echo "✅ {$role->name}: {$permissionCount} permissions\n";
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        echo "\n✅ All CORRECT permissions created!\n";
    }
}
