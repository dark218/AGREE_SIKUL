<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates permissions and LINKS them to features for proper visibility
     */
    public function run(): void
    {
        // Mapping entre les noms d'entité et les menu_url des features
        $entityToFeatureMap = [
            // PARAMETRAGE features
            'devise' => 'devise',
            'pays' => 'pays',
            'zones' => 'zone',
            'regions' => 'regions',
            'departements' => 'departements',
            'communes' => 'communes',
            'quartiers' => 'quartiers',
            'cycle_enseignement' => 'cycles-enseignement',
            'type_enseignement' => 'types-enseignement',
            'type_etablissement' => 'types-etablissement',
            'section' => 'sections',
            'niveau_etude' => 'niveaux-etudes',
            'type_cours' => 'types-cours',
            'nature_examen' => 'natures-examens',
            'type_examen' => 'types-examens',
            'unite_organisationnelle' => 'unites-organisationnelles',
            'matiere_unite' => 'matieres-unites',
            'groupe_matiere' => 'groupes-matieres',
            'type_apprenant' => 'type-apprenants',
            'categorie_apprenant' => 'categorie-apprenants',
            'titre_civilite' => 'titres-civilites',
            'type_evenement_agenda' => 'types-evenements',
            'periode_colaire' => 'periodes-colaires',
            'annee_scolaire' => 'annees-scolaires',
            'jour_ferie' => 'jours-feries',
            'nature_contrat' => 'natures-contrats',
            'categorie_enseignant' => 'categorie-enseignants',
            'fonction' => 'fonctions',
            'type_ressource' => 'types-ressources',

            // FINANCES modules
            'groupes_comptes' => 'groupes-comptes',
            'lignes_recettes' => 'lignes-recettes',
            'lignes_depenses' => 'lignes-depenses',
            'postes_recettes' => 'postes-recettes',
            'postes_depenses' => 'postes-depenses',

            // Autres modules
            'institutions' => 'institutions',
            'campuses' => 'campuses',
            'ecoles' => 'ecoles',
            'apprenants' => 'apprenants',
            'tuteurs' => 'tuteurs',
            'inscriptions' => 'inscriptions',
            'enseignants' => 'enseignants',
            'matieres' => 'matieres',
            'cours' => 'cours',
            'seances' => 'seances',
            'emplois_temps' => 'emplois-temps',
            'presences_seances' => 'presences',
            'absences_apprenants' => 'absences-apprenants',
            'evaluations' => 'evaluations',
            'notes' => 'notes',
            'bulletins' => 'bulletins',
            'devoirs' => 'devoirs',
            'messages' => 'messages',
            'annonces' => 'annonces',
            'frais' => 'frais',
            'paiements' => 'paiements',
            'depenses' => 'depenses',
            'bibliotheques' => 'bibliotheques',
            'ouvrages' => 'ouvrages',
            'emprunts' => 'emprunts',
            'documents' => 'documents',
            'equipements' => 'equipements',
            'categories_fournitures' => 'categories-fournitures',
            'fournitures' => 'fournitures',
            'rapports' => 'rapports',
        ];

        $actions = ['list', 'create', 'edit', 'view', 'delete'];

        $entities = array_keys($entityToFeatureMap);

        $createdCount = 0;

        foreach ($entities as $entity) {
            // Get feature_id from database using menu_url
            $featureMenuUrl = $entityToFeatureMap[$entity] ?? null;
            $featureId = null;

            if ($featureMenuUrl) {
                $featureId = DB::table('feature')
                    ->where('menu_url', $featureMenuUrl)
                    ->value('id');
            }

            foreach ($actions as $action) {
                $entityLabel = str_replace('_', ' ', ucfirst($entity));
                $actionLabel = $action;
                $libelle = ucfirst($actionLabel) . ' - ' . $entityLabel;

                $permission = Permission::firstOrCreate(
                    ['name' => $entity . '-' . $action],
                    [
                        'libelle' => $libelle,
                        'guard_name' => 'web',
                        'feature_id' => $featureId  // ✅ LINK TO FEATURE!
                    ]
                );

                // If permission already existed but has no feature_id, update it
                if ($featureId && !$permission->feature_id) {
                    $permission->update(['feature_id' => $featureId]);
                }

                $createdCount++;
            }
        }

        $this->command->info("✅ $createdCount permissions créées et liées aux features");
    }
}
