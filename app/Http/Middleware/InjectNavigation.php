<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectNavigation
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ne pas écraser navbars si HandleInertiaRequests l'a déjà partagé
        // Ce middleware retourne simplement sans modification
        // Les modules sont maintenant retournés par HandleInertiaRequests::getModulesMenu()

        return $next($request);
    }

    private function getMenuItems(): array
    {
        return [
            // ============================================
            // MODULES AGREE SIKUL - GESTION SCOLAIRE (16)
            // ============================================

            // Module 1: Institution
            [
                'id' => 'institution',
                'libelle' => 'Institutions',
                'libelle_en' => 'Institutions',
                'icone' => 'fas fa-building',
                'feature' => [
                    ['menu_url' => 'institutions.index', 'libelle' => 'Institutions', 'libelle_en' => 'Institutions', 'icone' => 'fas fa-building'],
                ]
            ],

            // Module 2: Campus
            [
                'id' => 'campus',
                'libelle' => 'Campus',
                'libelle_en' => 'Campuses',
                'icone' => 'fas fa-map-location-dot',
                'feature' => [
                    ['menu_url' => 'campus.index', 'libelle' => 'Campus', 'libelle_en' => 'Campuses', 'icone' => 'fas fa-map-location-dot'],
                ]
            ],

            // Module 3: Écoles
            [
                'id' => 'ecole',
                'libelle' => 'Écoles',
                'libelle_en' => 'Schools',
                'icone' => 'fas fa-school',
                'feature' => [
                    ['menu_url' => 'ecoles.index', 'libelle' => 'Écoles', 'libelle_en' => 'Schools', 'icone' => 'fas fa-school'],
                    ['menu_url' => 'niveaux.index', 'libelle' => 'Niveaux', 'libelle_en' => 'Levels', 'icone' => 'fas fa-layer-group'],
                    ['menu_url' => 'classes.index', 'libelle' => 'Classes', 'libelle_en' => 'Classes', 'icone' => 'fas fa-chalkboard'],
                ]
            ],

            // Module 4: Gestion Apprenants
            [
                'id' => 'gestion-apprenants',
                'libelle' => 'Gestion Apprenants',
                'libelle_en' => 'Student Management',
                'icone' => 'fas fa-users',
                'feature' => [
                    ['menu_url' => 'apprenants.index', 'libelle' => 'Apprenants', 'libelle_en' => 'Students', 'icone' => 'fas fa-user-graduate'],
                    ['menu_url' => 'inscriptions.index', 'libelle' => 'Inscriptions', 'libelle_en' => 'Enrollments', 'icone' => 'fas fa-clipboard-list'],
                    ['menu_url' => 'tuteurs.index', 'libelle' => 'Tuteurs', 'libelle_en' => 'Guardians', 'icone' => 'fas fa-user-tie'],
                ]
            ],

            // Module 5: Enseignants & RH
            [
                'id' => 'enseignants-rh',
                'libelle' => 'Enseignants & RH',
                'libelle_en' => 'Teachers & HR',
                'icone' => 'fas fa-chalkboard-user',
                'feature' => [
                    ['menu_url' => 'enseignants.index', 'libelle' => 'Enseignants', 'libelle_en' => 'Teachers', 'icone' => 'fas fa-person-chalkboard'],
                    ['menu_url' => 'personnels-administratifs.index', 'libelle' => 'Personnel Admin', 'libelle_en' => 'Admin Staff', 'icone' => 'fas fa-user-tie'],
                ]
            ],

            // Module 6: Cours & Horaires
            [
                'id' => 'cours-horaires',
                'libelle' => 'Cours & Horaires',
                'libelle_en' => 'Courses & Schedules',
                'icone' => 'fas fa-calendar-alt',
                'feature' => [
                    ['menu_url' => 'matieres.index', 'libelle' => 'Matières', 'libelle_en' => 'Subjects', 'icone' => 'fas fa-book'],
                    ['menu_url' => 'cours.index', 'libelle' => 'Cours', 'libelle_en' => 'Courses', 'icone' => 'fas fa-book-open'],
                    ['menu_url' => 'emplois-temps.index', 'libelle' => 'Emplois du temps', 'libelle_en' => 'Schedules', 'icone' => 'fas fa-table'],
                ]
            ],

            // Module 7: Présence & Absences
            [
                'id' => 'presence',
                'libelle' => 'Présence & Absences',
                'libelle_en' => 'Attendance',
                'icone' => 'fas fa-check-circle',
                'feature' => [
                    ['menu_url' => 'presences.index', 'libelle' => 'Présences', 'libelle_en' => 'Attendance', 'icone' => 'fas fa-clipboard-check'],
                    ['menu_url' => 'absences.index', 'libelle' => 'Absences', 'libelle_en' => 'Absences', 'icone' => 'fas fa-times-circle'],
                ]
            ],

            // Module 8: Examens & Notes
            [
                'id' => 'examens-notes',
                'libelle' => 'Examens & Notes',
                'libelle_en' => 'Exams & Grades',
                'icone' => 'fas fa-file-alt',
                'feature' => [
                    ['menu_url' => 'evaluations.index', 'libelle' => 'Évaluations', 'libelle_en' => 'Evaluations', 'icone' => 'fas fa-star'],
                    ['menu_url' => 'notes.index', 'libelle' => 'Notes', 'libelle_en' => 'Grades', 'icone' => 'fas fa-list'],
                    ['menu_url' => 'bulletins.index', 'libelle' => 'Bulletins', 'libelle_en' => 'Report Cards', 'icone' => 'fas fa-certificate'],
                ]
            ],

            // Module 9: Devoirs
            [
                'id' => 'devoirs',
                'libelle' => 'Devoirs',
                'libelle_en' => 'Homework',
                'icone' => 'fas fa-pencil-alt',
                'feature' => [
                    ['menu_url' => 'devoirs.index', 'libelle' => 'Devoirs', 'libelle_en' => 'Homework', 'icone' => 'fas fa-tasks'],
                ]
            ],

            // Module 10: Communication
            [
                'id' => 'communication',
                'libelle' => 'Communication',
                'libelle_en' => 'Communication',
                'icone' => 'fas fa-comments',
                'feature' => [
                    ['menu_url' => 'messages.index', 'libelle' => 'Messages', 'libelle_en' => 'Messages', 'icone' => 'fas fa-envelope'],
                    ['menu_url' => 'annonces.index', 'libelle' => 'Annonces', 'libelle_en' => 'Announcements', 'icone' => 'fas fa-bullhorn'],
                ]
            ],

            // Module 11: Finances
            [
                'id' => 'finances',
                'libelle' => 'Finances',
                'libelle_en' => 'Finance',
                'icone' => 'fas fa-money-bill',
                'feature' => [
                    ['menu_url' => 'frais.index', 'libelle' => 'Frais Scolaires', 'libelle_en' => 'School Fees', 'icone' => 'fas fa-receipt'],
                    ['menu_url' => 'paiements.index', 'libelle' => 'Paiements', 'libelle_en' => 'Payments', 'icone' => 'fas fa-credit-card'],
                    ['menu_url' => 'depenses.index', 'libelle' => 'Dépenses', 'libelle_en' => 'Expenses', 'icone' => 'fas fa-cash-flow'],
                ]
            ],

            // Module 12: Bibliothèque
            [
                'id' => 'bibliotheque',
                'libelle' => 'Bibliothèque',
                'libelle_en' => 'Library',
                'icone' => 'fas fa-book-open',
                'feature' => [
                    ['menu_url' => 'parametrage.bibliotheques.index', 'libelle' => 'Bibliothèques', 'libelle_en' => 'Libraries', 'icone' => 'fas fa-book-open'],
                ]
            ],

            // Module 13: Services (Cantine, Transport, Infirmerie)
            [
                'id' => 'services',
                'libelle' => 'Services',
                'libelle_en' => 'Services',
                'icone' => 'fas fa-concierge-bell',
                'feature' => [
                    ['menu_url' => 'cantines.index', 'libelle' => 'Cantine', 'libelle_en' => 'Canteen', 'icone' => 'fas fa-utensils'],
                    ['menu_url' => 'transports.index', 'libelle' => 'Transports', 'libelle_en' => 'Transportation', 'icone' => 'fas fa-bus'],
                    ['menu_url' => 'infirmeries.index', 'libelle' => 'Infirmerie', 'libelle_en' => 'Clinic', 'icone' => 'fas fa-hospital'],
                ]
            ],

            // Module 14: Documents
            [
                'id' => 'documents',
                'libelle' => 'Documents',
                'libelle_en' => 'Documents',
                'icone' => 'fas fa-file',
                'feature' => [
                    ['menu_url' => 'documents.index', 'libelle' => 'Documents', 'libelle_en' => 'Documents', 'icone' => 'fas fa-file-pdf'],
                ]
            ],

            // Module 15: Inventaire
            [
                'id' => 'inventaire',
                'libelle' => 'Inventaire',
                'libelle_en' => 'Inventory',
                'icone' => 'fas fa-boxes',
                'feature' => [
                    ['menu_url' => 'equipements.index', 'libelle' => 'Équipements', 'libelle_en' => 'Equipment', 'icone' => 'fas fa-tools'],
                ]
            ],

            // Module 16: Rapports
            [
                'id' => 'rapports',
                'libelle' => 'Rapports',
                'libelle_en' => 'Reports',
                'icone' => 'fas fa-chart-bar',
                'feature' => [
                    ['menu_url' => 'rapports.index', 'libelle' => 'Rapports', 'libelle_en' => 'Reports', 'icone' => 'fas fa-file-chart'],
                ]
            ],

            // ============================================
            // MODULES EXISTANTS SMILPAY
            // ============================================

            // Module: Business
            [
                'id' => 'business',
                'libelle' => 'Business',
                'libelle_en' => 'Business',
                'icone' => 'fas fa-handshake',
                'feature' => [
                    ['menu_url' => 'business.index', 'libelle' => 'Business', 'libelle_en' => 'Business', 'icone' => 'fas fa-handshake'],
                ]
            ],

            // Module: Personnel
            [
                'id' => 'personnel',
                'libelle' => 'Personnel',
                'libelle_en' => 'Personnel',
                'icone' => 'fas fa-users-cog',
                'feature' => [
                    ['menu_url' => 'personnel.index', 'libelle' => 'Personnel', 'libelle_en' => 'Personnel', 'icone' => 'fas fa-users-cog'],
                ]
            ],

            // Module: Service Client
            [
                'id' => 'service-client',
                'libelle' => 'Service Client',
                'libelle_en' => 'Customer Service',
                'icone' => 'fas fa-headset',
                'feature' => [
                    ['menu_url' => 'service-client.index', 'libelle' => 'Tickets', 'libelle_en' => 'Tickets', 'icone' => 'fas fa-ticket-alt'],
                ]
            ],

            // Module: POS
            [
                'id' => 'pos',
                'libelle' => 'POS',
                'libelle_en' => 'Point of Sale',
                'icone' => 'fas fa-cash-register',
                'feature' => [
                    ['menu_url' => 'pos.index', 'libelle' => 'POS', 'libelle_en' => 'POS', 'icone' => 'fas fa-cash-register'],
                ]
            ],

            // Module: Gestion Stock
            [
                'id' => 'gestion-stock',
                'libelle' => 'Gestion Stock',
                'libelle_en' => 'Stock Management',
                'icone' => 'fas fa-warehouse',
                'feature' => [
                    ['menu_url' => 'gestion-stock.index', 'libelle' => 'Stock', 'libelle_en' => 'Stock', 'icone' => 'fas fa-warehouse'],
                ]
            ],

            // Module: Wallet
            [
                'id' => 'wallet',
                'libelle' => 'Wallet',
                'libelle_en' => 'Digital Wallet',
                'icone' => 'fas fa-wallet',
                'feature' => [
                    ['menu_url' => 'wallet.index', 'libelle' => 'Wallet', 'libelle_en' => 'Wallet', 'icone' => 'fas fa-wallet'],
                ]
            ],

            // ============================================
            // CONFIGURATION & ADMINISTRATION
            // ============================================

            // Module: Paramétrage (Dictionnaires)
            [
                'id' => 'parametrage',
                'libelle' => 'Paramétrage',
                'libelle_en' => 'Settings',
                'icone' => 'fas fa-cog',
                'feature' => [
                    // Géographie
                    ['menu_url' => 'regions.index', 'libelle' => 'Régions', 'libelle_en' => 'Regions', 'icone' => 'fas fa-map'],
                    ['menu_url' => 'departements.index', 'libelle' => 'Départements', 'libelle_en' => 'Departments', 'icone' => 'fas fa-map-marker'],
                    ['menu_url' => 'communes.index', 'libelle' => 'Communes', 'libelle_en' => 'Towns', 'icone' => 'fas fa-city'],
                    ['menu_url' => 'quartiers.index', 'libelle' => 'Quartiers', 'libelle_en' => 'Districts', 'icone' => 'fas fa-neighborhood'],
                    // Enseignement
                    ['menu_url' => 'cycles.index', 'libelle' => 'Cycles', 'libelle_en' => 'Cycles', 'icone' => 'fas fa-sync-alt'],
                    ['menu_url' => 'types-enseignement.index', 'libelle' => 'Types d\'enseignement', 'libelle_en' => 'Teaching Types', 'icone' => 'fas fa-graduation-cap'],
                    ['menu_url' => 'sections.index', 'libelle' => 'Sections', 'libelle_en' => 'Sections', 'icone' => 'fas fa-layer-group'],
                    ['menu_url' => 'niveaux-etudes.index', 'libelle' => 'Niveaux d\'études', 'libelle_en' => 'Study Levels', 'icone' => 'fas fa-level-up-alt'],
                    // Matières
                    ['menu_url' => 'matieres-unites.index', 'libelle' => 'Matières/Unités', 'libelle_en' => 'Subjects/Units', 'icone' => 'fas fa-book-open'],
                    ['menu_url' => 'groupes-matieres.index', 'libelle' => 'Groupes de matières', 'libelle_en' => 'Subject Groups', 'icone' => 'fas fa-layer-group'],
                    // Apprenants
                    ['menu_url' => 'type-apprenants.index', 'libelle' => 'Types d\'apprenants', 'libelle_en' => 'Student Types', 'icone' => 'fas fa-tags'],
                    ['menu_url' => 'categorie-apprenants.index', 'libelle' => 'Catégories d\'apprenants', 'libelle_en' => 'Student Categories', 'icone' => 'fas fa-sitemap'],
                    // Calendrier
                    ['menu_url' => 'periodes-scolaires.index', 'libelle' => 'Périodes scolaires', 'libelle_en' => 'School Periods', 'icone' => 'fas fa-calendar'],
                    ['menu_url' => 'annees-scolaires.index', 'libelle' => 'Années scolaires', 'libelle_en' => 'School Years', 'icone' => 'fas fa-calendar-alt'],
                    ['menu_url' => 'jours-feries.index', 'libelle' => 'Jours fériés', 'libelle_en' => 'Public Holidays', 'icone' => 'fas fa-flag'],
                    // RH & Finances
                    ['menu_url' => 'fonctions.index', 'libelle' => 'Fonctions', 'libelle_en' => 'Positions', 'icone' => 'fas fa-briefcase'],
                    ['menu_url' => 'categorie-enseignants.index', 'libelle' => 'Catégories d\'enseignants', 'libelle_en' => 'Teacher Categories', 'icone' => 'fas fa-user-tag'],
                    ['menu_url' => 'natures-contrats.index', 'libelle' => 'Natures de contrats', 'libelle_en' => 'Contract Types', 'icone' => 'fas fa-file-contract'],
                    ['menu_url' => 'modes-paiement.index', 'libelle' => 'Modes de paiement', 'libelle_en' => 'Payment Modes', 'icone' => 'fas fa-money-check'],
                    ['menu_url' => 'titres-civilites.index', 'libelle' => 'Titres de civilité', 'libelle_en' => 'Titles', 'icone' => 'fas fa-id-badge'],
                    ['menu_url' => 'types-evenements.index', 'libelle' => 'Types d\'événements', 'libelle_en' => 'Event Types', 'icone' => 'fas fa-calendar-check'],
                ]
            ],

            // Module: Administration
            [
                'id' => 'administration',
                'libelle' => 'Administration',
                'libelle_en' => 'Administration',
                'icone' => 'fas fa-tools',
                'feature' => [
                    ['menu_url' => 'users.index', 'libelle' => 'Utilisateurs', 'libelle_en' => 'Users', 'icone' => 'fas fa-users'],
                    ['menu_url' => 'roles.index', 'libelle' => 'Rôles', 'libelle_en' => 'Roles', 'icone' => 'fas fa-shield-alt'],
                    ['menu_url' => 'permissions.index', 'libelle' => 'Permissions', 'libelle_en' => 'Permissions', 'icone' => 'fas fa-lock'],
                    ['menu_url' => 'modules.index', 'libelle' => 'Modules', 'libelle_en' => 'Modules', 'icone' => 'fas fa-cube'],
                    ['menu_url' => 'features.index', 'libelle' => 'Features', 'libelle_en' => 'Features', 'icone' => 'fas fa-star'],
                ]
            ],
        ];
    }
}
