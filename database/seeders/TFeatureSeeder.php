<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * ARCHITECTURE CORRECTE:
     * Module 23 (Paramétrage) = 40 features (une pour chaque entité de configuration)
     * Module 25 (Académique) = 20 features (une pour chaque entité académique)
     * Une entité = une fonctionnalité
     *
     * PERMISSIONS:
     * 120 permissions total (6 par entité académique × 20 entités):
     * - [entity]-list (Voir la liste)
     * - [entity]-create (Créer)
     * - [entity]-read (Voir détails)
     * - [entity]-update (Modifier)
     * - [entity]-delete (Supprimer)
     * - [entity]-activate (Activer/Désactiver)
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate the feature table to avoid duplicate key errors
        DB::table('feature')->truncate();

        DB::table('feature')->insert([

            // ========== MODULE 23: PARAMÉTRAGE (40 FEATURES) ==========
            ['id' => 3, 'libelle' => 'Cycle Enseignement', 'libelle_en' => 'Education Cycles', 'module_id' => 23, 'menu_url' => 'cycles-enseignement', 'icone' => 'fas fa-ring', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'libelle' => 'Département', 'libelle_en' => 'Departments', 'module_id' => 23, 'menu_url' => 'departements', 'icone' => 'fas fa-city', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'libelle' => 'Fonction', 'libelle_en' => 'Functions', 'module_id' => 23, 'menu_url' => 'fonctions', 'icone' => 'fas fa-briefcase', 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'libelle' => 'Groupe Matière', 'libelle_en' => 'Subject Groups', 'module_id' => 23, 'menu_url' => 'groupes-matieres', 'icone' => 'fas fa-book-bookmark', 'ordre' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'libelle' => 'Niveau Étude', 'libelle_en' => 'Study Levels', 'module_id' => 23, 'menu_url' => 'niveaux-etudes', 'icone' => 'fas fa-level-up-alt', 'ordre' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'libelle' => 'Quartier', 'libelle_en' => 'Districts', 'module_id' => 23, 'menu_url' => 'quartiers', 'icone' => 'fas fa-home', 'ordre' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'libelle' => 'Titre Civilité', 'libelle_en' => 'Titles', 'module_id' => 23, 'menu_url' => 'titres-civilites', 'icone' => 'fas fa-user-tie', 'ordre' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'libelle' => 'Type Cours', 'libelle_en' => 'Course Types', 'module_id' => 23, 'menu_url' => 'types-cours', 'icone' => 'fas fa-book-reader', 'ordre' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'libelle' => 'Type Enseignement', 'libelle_en' => 'Education Types', 'module_id' => 23, 'menu_url' => 'types-enseignements', 'icone' => 'fas fa-graduation-cap', 'ordre' => 11, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'libelle' => 'Type Établissement', 'libelle_en' => 'Establishment Types', 'module_id' => 23, 'menu_url' => 'types-etablissements', 'icone' => 'fas fa-school', 'ordre' => 12, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'libelle' => 'Pays', 'libelle_en' => 'Countries', 'module_id' => 23, 'menu_url' => 'pays', 'icone' => 'fas fa-globe', 'ordre' => 13, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'libelle' => 'Région', 'libelle_en' => 'Regions', 'module_id' => 23, 'menu_url' => 'regions', 'icone' => 'fas fa-map-marked-alt', 'ordre' => 14, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 16, 'libelle' => 'Institution', 'libelle_en' => 'Institutions', 'module_id' => 23, 'menu_url' => 'institutions', 'icone' => 'fas fa-building', 'ordre' => 15, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 17, 'libelle' => 'Campus', 'libelle_en' => 'Campus', 'module_id' => 23, 'menu_url' => 'campuses', 'icone' => 'fas fa-map-location-dot', 'ordre' => 16, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 18, 'libelle' => 'École', 'libelle_en' => 'Schools', 'module_id' => 23, 'menu_url' => 'ecoles', 'icone' => 'fas fa-school', 'ordre' => 17, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'libelle' => 'Classe', 'libelle_en' => 'Classes', 'module_id' => 23, 'menu_url' => 'classes', 'icone' => 'fas fa-chair', 'ordre' => 19, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'libelle' => 'Commune', 'libelle_en' => 'Municipalities', 'module_id' => 23, 'menu_url' => 'communes', 'icone' => 'fas fa-town-hall', 'ordre' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 22, 'libelle' => 'Section', 'libelle_en' => 'Sections', 'module_id' => 23, 'menu_url' => 'sections', 'icone' => 'fas fa-layer-group', 'ordre' => 21, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 23, 'libelle' => 'Devise', 'libelle_en' => 'Currencies', 'module_id' => 23, 'menu_url' => 'devises', 'icone' => 'fas fa-coins', 'ordre' => 22, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 24, 'libelle' => 'Matière Unité', 'libelle_en' => 'Unit Subjects', 'module_id' => 23, 'menu_url' => 'matieres-unites', 'icone' => 'fas fa-book', 'ordre' => 23, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 25, 'libelle' => 'Type Apprenant', 'libelle_en' => 'Student Types', 'module_id' => 23, 'menu_url' => 'types-apprenants', 'icone' => 'fas fa-user-graduate', 'ordre' => 24, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 26, 'libelle' => 'Zone', 'libelle_en' => 'Zones', 'module_id' => 23, 'menu_url' => 'zones', 'icone' => 'fas fa-map', 'ordre' => 25, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 27, 'libelle' => 'Catégorie Apprenant', 'libelle_en' => 'Student Categories', 'module_id' => 23, 'menu_url' => 'categories-apprenants', 'icone' => 'fas fa-tag', 'ordre' => 26, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 28, 'libelle' => 'Moyens Paiement', ...] — supprimé (commercial)
            ['id' => 29, 'libelle' => 'Unité Organisationnelle', 'libelle_en' => 'Organizational Units', 'module_id' => 23, 'menu_url' => 'unites-organisationnelles', 'icone' => 'fas fa-sitemap', 'ordre' => 28, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 30, 'libelle' => 'Jour Férié', 'libelle_en' => 'Holidays', 'module_id' => 23, 'menu_url' => 'jours-feries', 'icone' => 'fas fa-calendar-day', 'ordre' => 29, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 31, 'libelle' => 'Type Examen', 'libelle_en' => 'Exam Types', 'module_id' => 23, 'menu_url' => 'types-examens', 'icone' => 'fas fa-file-alt', 'ordre' => 30, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 32, 'libelle' => 'Type Ressource', 'libelle_en' => 'Resource Types', 'module_id' => 23, 'menu_url' => 'types-ressources', 'icone' => 'fas fa-cube', 'ordre' => 31, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 33, 'libelle' => 'Type Événement Agenda', 'libelle_en' => 'Event Types', 'module_id' => 23, 'menu_url' => 'types-evenements', 'icone' => 'fas fa-calendar-alt', 'ordre' => 32, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 34, 'libelle' => 'Nature Contrat', 'libelle_en' => 'Contract Types', 'module_id' => 23, 'menu_url' => 'natures-contrats', 'icone' => 'fas fa-file-contract', 'ordre' => 33, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 35, 'libelle' => 'Année Scolaire', 'libelle_en' => 'Academic Years', 'module_id' => 23, 'menu_url' => 'annees-scolaires', 'icone' => 'fas fa-calendar-year', 'ordre' => 34, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 36, 'libelle' => 'Période Colaire', 'libelle_en' => 'School Periods', 'module_id' => 23, 'menu_url' => 'periodes-colaires', 'icone' => 'fas fa-calendar-week', 'ordre' => 35, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 37, 'libelle' => 'Catégorie Enseignant', 'libelle_en' => 'Teacher Categories', 'module_id' => 23, 'menu_url' => 'categories-enseignants', 'icone' => 'fas fa-id-card', 'ordre' => 36, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 38, 'libelle' => 'Nature Examen', 'libelle_en' => 'Exam Natures', 'module_id' => 23, 'menu_url' => 'natures-examens', 'icone' => 'fas fa-clipboard', 'ordre' => 37, 'created_at' => now(), 'updated_at' => now()],

            // ========== MODULE 25: ACADÉMIQUE (20 FEATURES) ==========
            ['id' => 42, 'libelle' => 'Absence Apprenant', 'libelle_en' => 'Student Absences', 'module_id' => 25, 'menu_url' => 'absences-apprenants', 'icone' => 'fas fa-xmark-circle', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 43, 'libelle' => 'Absence Enseignant', 'libelle_en' => 'Teacher Absences', 'module_id' => 25, 'menu_url' => 'absences-enseignants', 'icone' => 'fas fa-calendar-xmark', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 44, 'libelle' => 'Apprenant', 'libelle_en' => 'Students', 'module_id' => 25, 'menu_url' => 'apprenants', 'icone' => 'fas fa-users', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 45, 'libelle' => 'Bulletin', 'libelle_en' => 'Report Cards', 'module_id' => 25, 'menu_url' => 'bulletins', 'icone' => 'fas fa-file-pdf', 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 46, 'libelle' => 'Cours', 'libelle_en' => 'Courses', 'module_id' => 25, 'menu_url' => 'cours', 'icone' => 'fas fa-graduation-cap', 'ordre' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 47, 'libelle' => 'Devoir', 'libelle_en' => 'Assignments', 'module_id' => 25, 'menu_url' => 'devoirs', 'icone' => 'fas fa-tasks', 'ordre' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 48, 'libelle' => 'Dossier Apprenant', 'libelle_en' => 'Student Files', 'module_id' => 25, 'menu_url' => 'dossiers-apprenants', 'icone' => 'fas fa-folder', 'ordre' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 49, 'libelle' => 'Emploi Temps', 'libelle_en' => 'Timetables', 'module_id' => 25, 'menu_url' => 'emplois-du-temps', 'icone' => 'fas fa-calendar', 'ordre' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 50, 'libelle' => 'Enseignant', 'libelle_en' => 'Teachers', 'module_id' => 25, 'menu_url' => 'enseignants', 'icone' => 'fas fa-chalkboard-user', 'ordre' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 51, 'libelle' => 'Évaluation', 'libelle_en' => 'Evaluations', 'module_id' => 25, 'menu_url' => 'evaluations', 'icone' => 'fas fa-list-check', 'ordre' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 52, 'libelle' => 'Inscription', 'libelle_en' => 'Enrollments', 'module_id' => 25, 'menu_url' => 'inscriptions', 'icone' => 'fas fa-clipboard-check', 'ordre' => 11, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 54, 'libelle' => 'Matière', 'libelle_en' => 'Subjects', 'module_id' => 25, 'menu_url' => 'matieres', 'icone' => 'fas fa-book-open', 'ordre' => 13, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 55, 'libelle' => 'Moyenne Matière', 'libelle_en' => 'Subject Averages', 'module_id' => 25, 'menu_url' => 'moyennes-matieres', 'icone' => 'fas fa-chart-bar', 'ordre' => 14, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 56, 'libelle' => 'Note', 'libelle_en' => 'Grades', 'module_id' => 25, 'menu_url' => 'notes', 'icone' => 'fas fa-star', 'ordre' => 15, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 57, 'libelle' => 'Personnel Administratif', 'libelle_en' => 'Admin Staff', 'module_id' => 25, 'menu_url' => 'personnels-administratifs', 'icone' => 'fas fa-briefcase', 'ordre' => 16, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 58, 'libelle' => 'Présence Séance', 'libelle_en' => 'Session Attendance', 'module_id' => 25, 'menu_url' => 'presences-seances', 'icone' => 'fas fa-check-circle', 'ordre' => 17, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 59, 'libelle' => 'Rendu Devoir', 'libelle_en' => 'Assignment Submissions', 'module_id' => 25, 'menu_url' => 'rendus-devoirs', 'icone' => 'fas fa-hand-fist', 'ordre' => 18, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 60, 'libelle' => 'Séance', 'libelle_en' => 'Sessions', 'module_id' => 25, 'menu_url' => 'seances', 'icone' => 'fas fa-clock', 'ordre' => 19, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 62, 'libelle' => 'Présences', 'libelle_en' => 'Attendance', 'module_id' => 25, 'menu_url' => 'presences', 'icone' => 'fas fa-user-check', 'ordre' => 21, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 132, 'libelle' => 'Classes des apprenants', 'libelle_en' => 'Student Classes', 'module_id' => 25, 'menu_url' => 'classes-apprenants', 'icone' => 'fas fa-school', 'ordre' => 23, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 133, 'libelle' => 'Passage', 'libelle_en' => 'Grade Advancement', 'module_id' => 25, 'menu_url' => 'passages', 'icone' => 'fas fa-arrow-up', 'ordre' => 24, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 134, 'libelle' => 'Affectation des enseignants', 'libelle_en' => 'Teacher Assignments', 'module_id' => 25, 'menu_url' => 'affectations-enseignants', 'icone' => 'fas fa-user-tie', 'ordre' => 25, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 135, 'libelle' => 'Liste des manuels', 'libelle_en' => 'Textbooks List', 'module_id' => 25, 'menu_url' => 'manuels', 'icone' => 'fas fa-book', 'ordre' => 26, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 136, 'libelle' => 'Bibliothèques', 'libelle_en' => 'Libraries', 'module_id' => 25, 'menu_url' => 'bibliotheques', 'icone' => 'fas fa-book', 'ordre' => 27, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 154, 'libelle' => 'Planification des examens', 'libelle_en' => 'Exam Planning', 'module_id' => 25, 'menu_url' => 'planification-examens', 'icone' => 'fas fa-calendar-check', 'ordre' => 28, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 155, 'libelle' => 'Examens en ligne', 'libelle_en' => 'Online Exams', 'module_id' => 25, 'menu_url' => 'examens-en-ligne', 'icone' => 'fas fa-laptop', 'ordre' => 29, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 156, 'libelle' => 'Résultats des examens', 'libelle_en' => 'Exam Results', 'module_id' => 25, 'menu_url' => 'resultats-examens', 'icone' => 'fas fa-chart-line', 'ordre' => 30, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 163, 'libelle' => 'Financements Examens', 'libelle_en' => 'Exam Financing', 'module_id' => 25, 'menu_url' => 'exam-finance', 'icone' => 'fas fa-coins', 'ordre' => 31, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 165, 'libelle' => 'Mes Examens', 'libelle_en' => 'My Exams', 'module_id' => 25, 'menu_url' => 'mes-examens', 'icone' => 'fas fa-graduation-cap', 'ordre' => 32, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 137, 'libelle' => 'Ouvrages', 'libelle_en' => 'Books', 'module_id' => 25, 'menu_url' => 'ouvrages', 'icone' => 'fas fa-books', 'ordre' => 28, 'created_at' => now(), 'updated_at' => now()], // Ouvrages exists in RessourcesLogistique module instead
  



            // ========== MODULE 10: COMMUNICATION ==========
            ['id' => 64, 'libelle' => 'Messages', 'libelle_en' => 'Messages', 'module_id' => 10, 'menu_url' => 'messages', 'icone' => 'fas fa-envelope', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 65, 'libelle' => 'Annonces', 'libelle_en' => 'Announcements', 'module_id' => 10, 'menu_url' => 'annonces', 'icone' => 'fas fa-bullhorn', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 66, 'libelle' => 'Commentaires', 'libelle_en' => 'Comments', 'module_id' => 10, 'menu_url' => 'commentaires', 'icone' => 'fas fa-comments', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 130, 'libelle' => 'Traductions', 'libelle_en' => 'Translations', 'module_id' => 10, 'menu_url' => 'traductions', 'icone' => 'fas fa-language', 'ordre' => 5, 'created_at' => now(), 'updated_at' => now()],

            // // ========== MODULE 11: FINANCES ==========
            // ['id' => 68, 'libelle' => 'Types Frais', 'libelle_en' => 'Fee Types', 'module_id' => 11, 'menu_url' => 'types-frais', 'icone' => 'fas fa-tags', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 69, 'libelle' => 'Frais', 'libelle_en' => 'Fees', 'module_id' => 11, 'menu_url' => 'frais', 'icone' => 'fas fa-receipt', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 70, 'libelle' => 'Paiements', 'libelle_en' => 'Payments', 'module_id' => 11, 'menu_url' => 'paiements', 'icone' => 'fas fa-credit-card', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 71, 'libelle' => 'Échéanciers', 'libelle_en' => 'Payment Plans', 'module_id' => 11, 'menu_url' => 'echeanciers', 'icone' => 'fas fa-calendar-check', 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 72, 'libelle' => 'Dépenses', 'libelle_en' => 'Expenses', 'module_id' => 11, 'menu_url' => 'depenses', 'icone' => 'fas fa-wallet', 'ordre' => 5, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 131, 'libelle' => 'Autres revenus', 'libelle_en' => 'Other Income', 'module_id' => 11, 'menu_url' => 'autres-revenus', 'icone' => 'fas fa-money-bill-wave', 'ordre' => 6, 'created_at' => now(), 'updated_at' => now()],

            // // ========== MODULE 12: BIBLIOTHÈQUE ==========
            // ['id' => 73, 'libelle' => 'Bibliothèques', 'libelle_en' => 'Libraries', 'module_id' => 12, 'menu_url' => 'bibliotheques', 'icone' => 'fas fa-book', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 74, 'libelle' => 'Ouvrages', 'libelle_en' => 'Books', 'module_id' => 12, 'menu_url' => 'ouvrages', 'icone' => 'fas fa-books', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 75, 'libelle' => 'Exemplaires', 'libelle_en' => 'Copies', 'module_id' => 12, 'menu_url' => 'exemplaires', 'icone' => 'fas fa-copy', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            // // ['id' => 76, 'libelle' => 'Emprunts', 'libelle_en' => 'Loans', 'module_id' => 12, 'menu_url' => 'emprunts', 'icone' => 'fas fa-hand-holding-heart', 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            // // ['id' => 77, 'libelle' => 'Réservations', 'libelle_en' => 'Reservations', 'module_id' => 12, 'menu_url' => 'reservations', 'icone' => 'fas fa-bookmark', 'ordre' => 5, 'created_at' => now(), 'updated_at' => now()],

            // ========== MODULE 13: SERVICES ==========
            ['id' => 78, 'libelle' => 'Cantine', 'libelle_en' => 'Canteen', 'module_id' => 13, 'menu_url' => 'cantine', 'icone' => 'fas fa-utensils', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 79, 'libelle' => 'Menus', 'libelle_en' => 'Menus', 'module_id' => 13, 'menu_url' => 'menus', 'icone' => 'fas fa-list', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 80, 'libelle' => 'Inscriptions Cantine', 'libelle_en' => 'Canteen Registrations', 'module_id' => 13, 'menu_url' => 'inscriptions-cantine', 'icone' => 'fas fa-clipboard', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 81, 'libelle' => 'Passages', 'libelle_en' => 'Passages', 'module_id' => 13, 'menu_url' => 'passages', 'icone' => 'fas fa-arrow-right', 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 82, 'libelle' => 'Transport', 'libelle_en' => 'Transport', 'module_id' => 13, 'menu_url' => 'services-transport', 'icone' => 'fas fa-bus', 'ordre' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 83, 'libelle' => 'Inscriptions Transport', 'libelle_en' => 'Transport Registrations', 'module_id' => 13, 'menu_url' => 'inscriptions-transport', 'icone' => 'fas fa-ticket', 'ordre' => 6, 'created_at' => now(), 'updated_at' => now()],

            // ========== MODULE 14: DOCUMENTS ==========
            ['id' => 85, 'libelle' => 'Catégories', 'libelle_en' => 'Categories', 'module_id' => 14, 'menu_url' => 'categories-documents', 'icone' => 'fas fa-folder-open', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 86, 'libelle' => 'Documents', 'libelle_en' => 'Documents', 'module_id' => 14, 'menu_url' => 'documents', 'icone' => 'fas fa-file-pdf', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],

            // ========== MODULE 15: INVENTAIRE ==========
            ['id' => 87, 'libelle' => 'Catégories Équipement', 'libelle_en' => 'Equipment Categories', 'module_id' => 15, 'menu_url' => 'categories-equipement', 'icone' => 'fas fa-tag', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 88, 'libelle' => 'Équipements', 'libelle_en' => 'Equipment', 'module_id' => 15, 'menu_url' => 'equipements', 'icone' => 'fas fa-tools', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 89, 'libelle' => 'Maintenances', 'libelle_en' => 'Maintenance', 'module_id' => 15, 'menu_url' => 'maintenances', 'icone' => 'fas fa-wrench', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 90, 'libelle' => 'Catégories Fournitures', 'libelle_en' => 'Supply Categories', 'module_id' => 15, 'menu_url' => 'categories-fournitures', 'icone' => 'fas fa-tag', 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 91, 'libelle' => 'Fournitures', 'libelle_en' => 'Supplies', 'module_id' => 15, 'menu_url' => 'fournitures', 'icone' => 'fas fa-boxes-alt', 'ordre' => 5, 'created_at' => now(), 'updated_at' => now()],

            // ========== MODULE 16: RAPPORTS ==========

            ['id' => 152, 'libelle' => 'Statistiques Ecole', 'libelle_en' => 'School Statistics', 'module_id' => 16, 'menu_url' => 'statistiques-ecole', 'icone' => 'fas fa-chart-pie', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 153, 'libelle' => 'Statistiques Classes', 'libelle_en' => 'Class Statistics', 'module_id' => 16, 'menu_url' => 'statistiques-classes', 'icone' => 'fas fa-chart-line', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 162, 'libelle' => 'Rapports', 'libelle_en' => ' Reports', 'module_id' => 16, 'menu_url' => 'rapports', 'icone' => 'fas fa-file-alt', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            // // ========== MODULE 17: BUSINESS ==========
            // ['id' => 92, 'libelle' => 'Marchands', 'libelle_en' => 'Merchants', 'module_id' => 17, 'menu_url' => 'marchands', 'icone' => 'fas fa-shop', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 93, 'libelle' => 'Points de Vente', 'libelle_en' => 'Sales Points', 'module_id' => 17, 'menu_url' => 'points-vente', 'icone' => 'fas fa-store', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 94, 'libelle' => 'Caisses', 'libelle_en' => 'Cash Registers', 'module_id' => 17, 'menu_url' => 'caisses', 'icone' => 'fas fa-cash-register', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 95, 'libelle' => 'Employés', 'libelle_en' => 'Employees', 'module_id' => 17, 'menu_url' => 'employes', 'icone' => 'fas fa-users', 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 96, 'libelle' => 'Comptes Bancaires', 'libelle_en' => 'Bank Accounts', 'module_id' => 17, 'menu_url' => 'comptes-bancaires', 'icone' => 'fas fa-university', 'ordre' => 5, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 97, 'libelle' => 'Terminaux', 'libelle_en' => 'Terminals', 'module_id' => 17, 'menu_url' => 'terminaux', 'icone' => 'fas fa-tv', 'ordre' => 6, 'created_at' => now(), 'updated_at' => now()],

            // ========== MODULE 18: PERSONNEL ==========
            ['id' => 102, 'libelle' => 'Parents', 'libelle_en' => 'Parents', 'module_id' => 18, 'menu_url' => 'parents', 'icone' => 'fas fa-child', 'ordre' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 103, 'libelle' => 'Accompagnateurs', 'libelle_en' => 'Accompanying Staff', 'module_id' => 18, 'menu_url' => 'accompagnateurs', 'icone' => 'fas fa-user-friends', 'ordre' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 61, 'libelle' => 'Tuteur', 'libelle_en' => 'Guardians', 'module_id' => 18, 'menu_url' => 'tuteurs', 'icone' => 'fas fa-user-shield', 'ordre' => 7, 'created_at' => now(), 'updated_at' => now()],

            // ========== MODULE 19: SERVICE CLIENT (supprimé — commercial) ==========
            // ['id' => 104, 'libelle' => 'Clients', ...],
            // ['id' => 105, 'libelle' => 'Moyens Paiement', ...],

            // ========== MODULE 20: POS (supprimé — commercial) ==========
            // ['id' => 106, 'libelle' => 'Ventes POS', ...],
            // ['id' => 107, 'libelle' => 'Sessions Caisse', ...],
            // ['id' => 108, 'libelle' => 'QR Codes', ...],
            // ['id' => 109, 'libelle' => 'Remboursements', ...],

            // ========== MODULE 21: GESTION STOCK (supprimé — commercial) ==========
            // ['id' => 110, 'libelle' => 'Articles', ...],
            // ['id' => 111, 'libelle' => 'Mouvements Stock', ...],
            // ['id' => 112, 'libelle' => 'Inventaires', ...],
            // ['id' => 113, 'libelle' => 'Transferts Stock', ...],

            // ========== MODULE 22: WALLET ==========
            // ['id' => 114, 'libelle' => 'Portefeuilles', 'libelle_en' => 'Wallets', 'module_id' => 22, 'menu_url' => 'wallets', 'icone' => 'fas fa-wallet', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 115, 'libelle' => 'Mouvements', 'libelle_en' => 'Movements', 'module_id' => 22, 'menu_url' => 'mouvements-wallet', 'icone' => 'fas fa-exchange-alt', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 116, 'libelle' => 'Transactions', 'libelle_en' => 'Transactions', 'module_id' => 22, 'menu_url' => 'transactions-wallet', 'icone' => 'fas fa-money-check', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],

            // ========== MODULE 24: ADMINISTRATION ==========
            ['id' => 117, 'libelle' => 'Utilisateurs', 'libelle_en' => 'Users', 'module_id' => 24, 'menu_url' => 'users', 'icone' => 'fas fa-users', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 118, 'libelle' => 'Rôles', 'libelle_en' => 'Roles', 'module_id' => 24, 'menu_url' => 'roles', 'icone' => 'fas fa-mask', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 119, 'libelle' => 'Modules', 'libelle_en' => 'Modules', 'module_id' => 24, 'menu_url' => 'modules', 'icone' => 'fas fa-puzzle-piece', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 120, 'libelle' => 'Fonctionnalités', 'libelle_en' => 'Features', 'module_id' => 24, 'menu_url' => 'fonctionnalites', 'icone' => 'fas fa-wrench', 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 121, 'libelle' => 'Permissions', 'libelle_en' => 'Permissions', 'module_id' => 24, 'menu_url' => 'permissions', 'icone' => 'fas fa-key', 'ordre' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 122, 'libelle' => 'Logs Erreur', 'libelle_en' => 'Error Logs', 'module_id' => 24, 'menu_url' => 'error-logs', 'icone' => 'fas fa-exclamation-circle', 'ordre' => 6, 'created_at' => now(), 'updated_at' => now()],

            // ========== MODULE 27: PARAMÉTRAGE GÉNÉRAUX ==========
            ['id' => 166, 'libelle' => 'Paramétrage Généraux', 'libelle_en' => 'General Settings', 'module_id' => 27, 'menu_url' => 'parametrage-generaux', 'icone' => 'fas fa-sliders-h', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],

            // ========== MODULE 11: FINANCES (ADDITIONS) ==========
            ['id' => 123, 'libelle' => 'Écolage', 'libelle_en' => 'School Fees', 'module_id' => 11, 'menu_url' => 'ecolage', 'icone' => 'fas fa-graduation-cap', 'ordre' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 124, 'libelle' => 'Autres revenus', 'libelle_en' => 'Other Income', 'module_id' => 11, 'menu_url' => 'autres-revenus', 'icone' => 'fas fa-coins', 'ordre' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 125, 'libelle' => 'Versements', 'libelle_en' => 'Payments Received', 'module_id' => 11, 'menu_url' => 'versements', 'icone' => 'fas fa-money-bill', 'ordre' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 126, 'libelle' => 'Facturations apprenants', 'libelle_en' => 'Student Invoices', 'module_id' => 11, 'menu_url' => 'facturations-apprenants', 'icone' => 'fas fa-file-invoice-dollar', 'ordre' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 127, 'libelle' => 'Achats et dépenses', 'libelle_en' => 'Purchases & Expenses', 'module_id' => 11, 'menu_url' => 'achats-depenses', 'icone' => 'fas fa-shopping-cart', 'ordre' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 128, 'libelle' => 'Salaires', 'libelle_en' => 'Salaries', 'module_id' => 11, 'menu_url' => 'salaires', 'icone' => 'fas fa-money-check-alt', 'ordre' => 11, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 157, 'libelle' => 'Groupes de comptes', 'libelle_en' => 'Account Groups', 'module_id' => 11, 'menu_url' => 'groupes-comptes', 'icone' => 'fas fa-folder-open', 'ordre' => 12, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 158, 'libelle' => 'Lignes de recettes', 'libelle_en' => 'Revenue Lines', 'module_id' => 11, 'menu_url' => 'lignes-recettes', 'icone' => 'fas fa-arrow-trending-up', 'ordre' => 13, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 159, 'libelle' => 'Lignes de dépenses', 'libelle_en' => 'Expense Lines', 'module_id' => 11, 'menu_url' => 'lignes-depenses', 'icone' => 'fas fa-arrow-trending-down', 'ordre' => 14, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 160, 'libelle' => 'Postes de recettes', 'libelle_en' => 'Revenue Items', 'module_id' => 11, 'menu_url' => 'postes-recettes', 'icone' => 'fas fa-list', 'ordre' => 15, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 161, 'libelle' => 'Postes de dépenses', 'libelle_en' => 'Expense Items', 'module_id' => 11, 'menu_url' => 'postes-depenses', 'icone' => 'fas fa-list', 'ordre' => 16, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 164, 'libelle' => 'Rapport Financier', 'libelle_en' => 'Financial Report', 'module_id' => 11, 'menu_url' => 'rapports-financiers', 'icone' => 'fas fa-chart-line', 'ordre' => 17, 'created_at' => now(), 'updated_at' => now()],

            // ========== MODULE 26: RESSOURCES LOGISTIQUE ==========
            ['id' => 140, 'libelle' => 'Catégories Fournitures', 'libelle_en' => 'Supply Categories', 'module_id' => 26, 'menu_url' => 'categories-fournitures', 'icone' => 'fas fa-tag', 'ordre' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 142, 'libelle' => 'Fournitures', 'libelle_en' => 'Supplies', 'module_id' => 26, 'menu_url' => 'fournitures', 'icone' => 'fas fa-boxes', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 143, 'libelle' => 'Équipements', 'libelle_en' => 'Equipment', 'module_id' => 26, 'menu_url' => 'equipements-logistique', 'icone' => 'fas fa-tools', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 144, 'libelle' => 'Mobilier', 'libelle_en' => 'Furniture', 'module_id' => 26, 'menu_url' => 'mobilier', 'icone' => 'fas fa-chair', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 145, 'libelle' => 'Personnel Logistique', 'libelle_en' => 'Logistics Staff', 'module_id' => 26, 'menu_url' => 'personnel-logistique', 'icone' => 'fas fa-users', 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
            // ['id' => 146, 'libelle' => 'Demandes de Ressources', 'libelle_en' => 'Resource Requests', 'module_id' => 26, 'menu_url' => 'demandes-ressources', 'icone' => 'fas fa-clipboard-list', 'ordre' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 147, 'libelle' => 'Inventaire Logistique', 'libelle_en' => 'Logistics Inventory', 'module_id' => 26, 'menu_url' => 'inventaire-logistique', 'icone' => 'fas fa-dolly', 'ordre' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 148, 'libelle' => 'Ouvrages', 'libelle_en' => 'Books', 'module_id' => 26, 'menu_url' => 'ouvrages', 'icone' => 'fas fa-books', 'ordre' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 149, 'libelle' => 'Exemplaires', 'libelle_en' => 'Copies', 'module_id' => 26, 'menu_url' => 'exemplaires', 'icone' => 'fas fa-copy', 'ordre' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 150, 'libelle' => 'Emprunts', 'libelle_en' => 'Loans', 'module_id' => 26, 'menu_url' => 'emprunts', 'icone' => 'fas fa-hand-holding-heart', 'ordre' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 151, 'libelle' => 'Réservations', 'libelle_en' => 'Reservations', 'module_id' => 26, 'menu_url' => 'reservations', 'icone' => 'fas fa-check-square', 'ordre' => 10, 'created_at' => now(), 'updated_at' => now()],

        ]);
 
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
