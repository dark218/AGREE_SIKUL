<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Link existing permissions to their corresponding features.
     * This fixes the visibility issue where modules don't display in the sidebar.
     */
    public function up(): void
    {
        // Mapping entre les noms de permission et les menu_url des features
        $permissionToFeatureMap = [
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
            'mode_paiement' => 'modes-paiement',
            'type_ressource' => 'types-ressources',
            'fournisseur_paiement' => 'fournisseurpaiement',
            'banque' => 'banque',

            // Module 1: Institutions
            'institutions' => 'institutions',

            // Module 2: Campus
            'campuses' => 'campuses',

            // Module 3: Écoles
            'ecoles' => 'ecoles',
            'niveaux' => 'niveaux',
            'classes' => 'classes',

            // Module 4: Apprenants
            'apprenants' => 'apprenants',
            'tuteurs' => 'tuteurs',
            'inscriptions' => 'inscriptions',
            'dossiers_apprenants' => 'dossiers-apprenants',

            // Module 5: Enseignants & RH
            'enseignants' => 'enseignants',
            'personnels_administratifs' => 'personnels-administratifs',
            'absences_enseignants' => 'absences-enseignants',

            // Module 6: Cours & Horaires
            'matieres' => 'matieres',
            'cours' => 'cours',
            'seances' => 'seances',
            'emplois_temps' => 'emplois-temps',

            // Module 7: Présence & Absences
            'presences_seances' => 'presences-seances',
            'absences_apprenants' => 'absences-apprenants',
            'absences' => 'absences',
            'justificatifs_absences' => 'justificatifs-absences',
            'listes_manuels' => 'listes-manuels',

            // Module 8: Examens & Notes
            'evaluations' => 'evaluations',
            'notes' => 'notes',
            'bulletins' => 'bulletins',
            'moyennes_matieres' => 'moyennes',

            // Module 9: Devoirs
            'devoirs' => 'devoirs',
            'rendus_devoirs' => 'rendus-devoirs',

            // Module 10: Communication
            'messages' => 'messages',
            'annonces' => 'annonces',
            'commentaires_annonces' => 'commentaires',
            'notifications' => 'notifications',

            // Module 11: Finances
            'types_frais' => 'types-frais',
            'frais' => 'frais',
            'paiements' => 'paiements',
            'echeanciers' => 'echeanciers',
            'depenses' => 'depenses',

            // Module 12: Bibliothèque
            'bibliotheques' => 'bibliotheques',
            'ouvrages' => 'ouvrages',
            'exemplaires' => 'exemplaires',
            'emprunts' => 'emprunts',
            'reservations' => 'reservations',

            // Module 13: Services
            'services_cantines' => 'cantines',
            'menus' => 'menus',
            'inscriptions_cantines' => 'inscriptions-cantine',
            'passages_cantines' => 'passages',
            'services_transports' => 'transports',
            'inscriptions_transports' => 'inscriptions-transport',
            'consultations_infirmeries' => 'infirmerie',

            // Module 14: Documents
            'categories_documents' => 'categories-documents',
            'documents' => 'documents',

            // Module 15: Inventaire
            'categories_equipements' => 'categories-equipement',
            'equipements' => 'equipements',
            'maintenances_equipements' => 'maintenances',

            // Module 16: Rapports
            'modeles_rapports' => 'modeles-rapports',
            'rapports' => 'rapports',
        ];

        $linkedCount = 0;
        foreach ($permissionToFeatureMap as $permPrefix => $featureMenuUrl) {
            // Find the feature_id
            $featureId = DB::table('feature')
                ->where('menu_url', $featureMenuUrl)
                ->value('id');

            if ($featureId) {
                // Update all permissions for this entity
                $count = DB::table('permissions')
                    ->where('name', 'LIKE', $permPrefix . '-%')
                    ->update(['feature_id' => $featureId]);

                $linkedCount += $count;
            }
        }

        // Log the result
        info("✅ $linkedCount permissions liées aux features");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset all feature_id to null
        DB::table('permissions')->update(['feature_id' => null]);
    }
};
