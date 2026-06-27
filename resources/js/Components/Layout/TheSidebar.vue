<script setup>
import { ref, inject, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@/Components/Common/AppLogo.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { buildFeatureHref } from '@/Composables/useAppFeatures';

// ============================================================================
// PROPS & INJECTIONS
// ============================================================================

const props = defineProps({
    isCollapsed: {
        type: Boolean,
        default: false
    },
    isMobileOpen: {
        type: Boolean,
        default: false
    }
});

const page = usePage();
const openLogoutModal = inject('openLogoutModal');
const { hasPermission, isSuperAdmin, hasRole } = usePermissions();

// Détecte si l'utilisateur est un élève
const isEleve = computed(() => hasRole('eleve'));

// Menu spécifique élève
const ELEVE_MENU_CONFIG = [
    {
        id: 'mon-espace',
        libelle: 'Mon Espace',
        libelle_en: 'My Space',
        icone: 'fas fa-user-graduate',
        feature: [
            { menu_url: 'apprenant/certificats', libelle: 'Certificats', libelle_en: 'Certificates', icone: 'fas fa-certificate' },
            { menu_url: 'apprenant/paiements', libelle: 'Paiements', libelle_en: 'Payments', icone: 'fas fa-wallet' },
            { menu_url: 'apprenant/notifications', libelle: 'Notifications', libelle_en: 'Notifications', icone: 'fas fa-bell' },
            { menu_url: 'apprenant/chat', libelle: 'Chat Admin', libelle_en: 'Chat Admin', icone: 'fas fa-comments' },
        ]
    }
];

// ============================================================================
// MENU CONFIGURATION
// ============================================================================

/**
 * Default menu structure (used as fallback if no navbars prop from backend)
 * Contains 5 main menus:
 * - Service Client (2 features)
 * - Business (2 features)
 * - Académique (20 features)
 * - Paramétrage (43 features)
 * - Administration (5 features)
 */
const DEFAULT_MENU_CONFIG = [
    {
        id: 'service-client',
        libelle: 'Service Client',
        libelle_en: 'Customer Service',
        icone: 'fas fa-headset',
        feature: [
            { menu_url: 'tickets', libelle: 'Tickets', libelle_en: 'Tickets', icone: 'fas fa-ticket-alt' },
            { menu_url: 'messages', libelle: 'Messages', libelle_en: 'Messages', icone: 'fas fa-envelope' },
            { menu_url: 'admin-chat', libelle: 'Chat Apprenants', libelle_en: 'Student Chat', icone: 'fas fa-comments' }
        ]
    },
    {
        id: 'business',
        libelle: 'Business',
        libelle_en: 'Business',
        icone: 'fas fa-briefcase',
        feature: [
            { menu_url: 'transactions', libelle: 'Transactions', libelle_en: 'Transactions', icone: 'fas fa-exchange-alt' },
            { menu_url: 'rapport/statistiques-ecole', libelle: 'Statistiques Écoles', libelle_en: 'School Statistics', icone: 'fas fa-chart-pie' },
            { menu_url: 'rapport/statistiques-classes', libelle: 'Statistiques Classes', libelle_en: 'Class Statistics', icone: 'fas fa-chart-line' }
        ]
    },
    {
        id: 'academique',
        libelle: 'Académique',
        libelle_en: 'Academic',
        icone: 'fas fa-graduation-cap',
        feature: [
            // Gestion Académique
            { menu_url: 'apprenants', libelle: 'Apprenants', libelle_en: 'Students', icone: 'fas fa-users' },
            { menu_url: 'enseignants', libelle: 'Enseignants', libelle_en: 'Teachers', icone: 'fas fa-chalkboard-user' },
            { menu_url: 'tuteurs', libelle: 'Tuteurs', libelle_en: 'Guardians', icone: 'fas fa-user-shield' },
            { menu_url: 'matieres', libelle: 'Matières', libelle_en: 'Subjects', icone: 'fas fa-book-open' },

            // Pédagogie
            { menu_url: 'cours', libelle: 'Cours', libelle_en: 'Courses', icone: 'fas fa-graduation-cap' },
            { menu_url: 'seances', libelle: 'Séances', libelle_en: 'Sessions', icone: 'fas fa-clock' },
            { menu_url: 'emplois-du-temps', libelle: 'Emplois du Temps', libelle_en: 'Timetables', icone: 'fas fa-calendar' },

            // Évaluations
            { menu_url: 'evaluations', libelle: 'Évaluations', libelle_en: 'Evaluations', icone: 'fas fa-list-check' },
            { menu_url: 'notes', libelle: 'Notes', libelle_en: 'Grades', icone: 'fas fa-star' },
            { menu_url: 'bulletins', libelle: 'Bulletins', libelle_en: 'Report Cards', icone: 'fas fa-file-pdf' },

            // Inscriptions & Dossiers
            { menu_url: 'inscriptions', libelle: 'Inscriptions', libelle_en: 'Enrollments', icone: 'fas fa-clipboard-check' },
            { menu_url: 'dossiers-apprenants', libelle: 'Dossiers', libelle_en: 'Student Files', icone: 'fas fa-folder' },

            // Absences & Présences
            { menu_url: 'absences-apprenants', libelle: 'Absences Apprenants', libelle_en: 'Student Absences', icone: 'fas fa-xmark-circle' },
            { menu_url: 'absences-enseignants', libelle: 'Absences Enseignants', libelle_en: 'Teacher Absences', icone: 'fas fa-calendar-xmark' },
            { menu_url: 'presences', libelle: 'Présences (Board)', libelle_en: 'Attendance Board', icone: 'fas fa-user-check' },
            { menu_url: 'presences-seances', libelle: 'Présences Séances', libelle_en: 'Session Attendance', icone: 'fas fa-check-circle' },
            { menu_url: 'justificatifs-absences', libelle: 'Justificatifs', libelle_en: 'Justifications', icone: 'fas fa-file-check' },

            // Travaux Académiques
            { menu_url: 'devoirs', libelle: 'Devoirs', libelle_en: 'Assignments', icone: 'fas fa-tasks' },
            { menu_url: 'rendus-devoirs', libelle: 'Rendus', libelle_en: 'Submissions', icone: 'fas fa-hand-fist' },
            { menu_url: 'moyennes-matieres', libelle: 'Moyennes', libelle_en: 'Averages', icone: 'fas fa-chart-bar' },

            // Examen & Finances
            { menu_url: 'planification-examens', libelle: 'Planification Examens', libelle_en: 'Exam Planning', icone: 'fas fa-calendar-check' },
            { menu_url: 'examens-en-ligne', libelle: 'Examens En Ligne', libelle_en: 'Online Exams', icone: 'fas fa-laptop' },
            { menu_url: 'mes-examens', libelle: 'Mes Examens', libelle_en: 'My Exams', icone: 'fas fa-graduation-cap' },
            { menu_url: 'exam-finance', libelle: 'Financements Examens', libelle_en: 'Exam Financing', icone: 'fas fa-coins' },

            // Administration
            { menu_url: 'personnels-administratifs', libelle: 'Personnel Admin', libelle_en: 'Admin Staff', icone: 'fas fa-briefcase' }
        ]
    },
    {
        id: 'finances',
        libelle: 'Finances',
        libelle_en: 'Finance',
        icone: 'fas fa-money-bill',
        feature: [
            { menu_url: 'ecolage', libelle: 'Écolage', libelle_en: 'School Fees', icone: 'fas fa-graduation-cap' },
            { menu_url: 'autres-revenus', libelle: 'Autres revenus', libelle_en: 'Other Income', icone: 'fas fa-coins' },
            { menu_url: 'versements', libelle: 'Versements', libelle_en: 'Payments Received', icone: 'fas fa-money-bill' },
            { menu_url: 'facturations-apprenants', libelle: 'Facturations apprenants', libelle_en: 'Student Invoices', icone: 'fas fa-file-invoice-dollar' },
            { menu_url: 'achats-depenses', libelle: 'Achats et dépenses', libelle_en: 'Purchases & Expenses', icone: 'fas fa-shopping-cart' },
            { menu_url: 'salaires', libelle: 'Salaires', libelle_en: 'Salaries', icone: 'fas fa-money-check-alt' },
            { menu_url: 'groupes-comptes', libelle: 'Groupes de comptes', libelle_en: 'Account Groups', icone: 'fas fa-folder-open' },
            { menu_url: 'lignes-recettes', libelle: 'Lignes de recettes', libelle_en: 'Revenue Lines', icone: 'fas fa-arrow-trending-up' },
            { menu_url: 'lignes-depenses', libelle: 'Lignes de dépenses', libelle_en: 'Expense Lines', icone: 'fas fa-arrow-trending-down' },
            { menu_url: 'postes-recettes', libelle: 'Postes de recettes', libelle_en: 'Revenue Items', icone: 'fas fa-list' },
            { menu_url: 'postes-depenses', libelle: 'Postes de dépenses', libelle_en: 'Expense Items', icone: 'fas fa-list' },
            { menu_url: 'rapports-financiers', libelle: 'Rapport Financier', libelle_en: 'Financial Report', icone: 'fas fa-chart-line' }
        ]
    },
    {
        id: 'services',
        libelle: 'Services',
        libelle_en: 'Services',
        icone: 'fas fa-concierge-bell',
        feature: [
            { menu_url: 'services-cantine', libelle: 'Cantine', libelle_en: 'Canteen', icone: 'fas fa-utensils' },
            { menu_url: 'menus', libelle: 'Menus', libelle_en: 'Menus', icone: 'fas fa-list' },
            { menu_url: 'inscriptions-cantine', libelle: 'Inscriptions Cantine', libelle_en: 'Canteen Enrollments', icone: 'fas fa-clipboard-check' },
            { menu_url: 'passages-cantine', libelle: 'Passages', libelle_en: 'Passages', icone: 'fas fa-door-open' },
            { menu_url: 'services-transport', libelle: 'Transport', libelle_en: 'Transport', icone: 'fas fa-bus' },
            { menu_url: 'inscriptions-transport', libelle: 'Inscriptions Transport', libelle_en: 'Transport Enrollments', icone: 'fas fa-clipboard-check' },
            { menu_url: 'consultations-infirmerie', libelle: 'Infirmerie', libelle_en: 'Infirmary', icone: 'fas fa-hospital' }
        ]
    },
    {
        id: 'parametrage',
        libelle: 'Paramétrage',
        libelle_en: 'Settings',
        icone: 'fas fa-cog',
        feature: [
            // Academic Organization
            { menu_url: 'annees_scolaires', libelle: 'Années Scolaires', libelle_en: 'School Years', icone: 'fas fa-calendar-alt' },
            { menu_url: 'periodes_colaires', libelle: 'Périodes Colaires', libelle_en: 'School Periods', icone: 'fas fa-hourglass' },
            { menu_url: 'niveaux', libelle: 'Niveaux', libelle_en: 'Levels', icone: 'fas fa-bars' },
            { menu_url: 'niveaux_etude', libelle: 'Niveaux Études', libelle_en: 'Study Levels', icone: 'fas fa-graduation-cap' },
            { menu_url: 'cycles_enseignement', libelle: 'Cycles Enseignement', libelle_en: 'Teaching Cycles', icone: 'fas fa-circle' },
            { menu_url: 'classes', libelle: 'Classes', libelle_en: 'Classes', icone: 'fas fa-chalkboard' },
            { menu_url: 'sections', libelle: 'Sections', libelle_en: 'Sections', icone: 'fas fa-cube' },

            // Teaching & Courses
            { menu_url: 'types_cours', libelle: 'Types Cours', libelle_en: 'Course Types', icone: 'fas fa-blackboard' },
            { menu_url: 'types_enseignement', libelle: 'Types Enseignement', libelle_en: 'Teaching Types', icone: 'fas fa-chalkboard-teacher' },
            { menu_url: 'groupes_matiere', libelle: 'Groupes Matière', libelle_en: 'Subject Groups', icone: 'fas fa-book-reader' },
            { menu_url: 'matiere_unites', libelle: 'Matière Unités', libelle_en: 'Subject Units', icone: 'fas fa-list' },

            // Exams & Assessments
            { menu_url: 'natures_examens', libelle: 'Natures Examens', libelle_en: 'Exam Types', icone: 'fas fa-pencil-alt' },
            { menu_url: 'types_examens', libelle: 'Types Examens', libelle_en: 'Exam Types', icone: 'fas fa-check-square' },

            // Students & Teachers
            { menu_url: 'types_apprenants', libelle: 'Types Apprenants', libelle_en: 'Student Types', icone: 'fas fa-user-graduate' },
            { menu_url: 'categories_apprenant', libelle: 'Catégories Apprenant', libelle_en: 'Student Categories', icone: 'fas fa-tags' },
            { menu_url: 'categories_enseignant', libelle: 'Catégories Enseignant', libelle_en: 'Teacher Categories', icone: 'fas fa-tags' },

            // Institutions
            { menu_url: 'campuses', libelle: 'Campuses', libelle_en: 'Campuses', icone: 'fas fa-building' },
            { menu_url: 'ecoles', libelle: 'Écoles', libelle_en: 'Schools', icone: 'fas fa-school' },
            { menu_url: 'institution', libelle: 'Institutions', libelle_en: 'Institutions', icone: 'fas fa-landmark' },
            { menu_url: 'types_etablissements', libelle: 'Types Établissements', libelle_en: 'Institution Types', icone: 'fas fa-home' },
            { menu_url: 'types_etablissement_spe', libelle: 'Spécialité Établissement', libelle_en: 'Institution Specialty', icone: 'fas fa-certificate' },

            // Organizational Structure
            { menu_url: 'unite_organisationnelles', libelle: 'Unités Organisationnelles', libelle_en: 'Organizational Units', icone: 'fas fa-object-group' },
            { menu_url: 'fonctions', libelle: 'Fonctions', libelle_en: 'Functions', icone: 'fas fa-briefcase' },

            // Contracts & Resources
            { menu_url: 'natures_contrat', libelle: 'Natures Contrat', libelle_en: 'Contract Types', icone: 'fas fa-file-contract' },
            { menu_url: 'types_ressource', libelle: 'Types Ressource', libelle_en: 'Resource Types', icone: 'fas fa-cubes' },
            { menu_url: 'fichiers', libelle: 'Fichiers', libelle_en: 'Files', icone: 'fas fa-file' },

            // Financial & Administrative
            { menu_url: 'devises', libelle: 'Devises', libelle_en: 'Currencies', icone: 'fas fa-money-bill' },

            // Geography & Calendar
            { menu_url: 'pays', libelle: 'Pays', libelle_en: 'Countries', icone: 'fas fa-earth' },
            { menu_url: 'regions', libelle: 'Régions', libelle_en: 'Regions', icone: 'fas fa-map' },
            { menu_url: 'departements', libelle: 'Départements', libelle_en: 'Departments', icone: 'fas fa-sitemap' },
            { menu_url: 'communes', libelle: 'Communes', libelle_en: 'Communes', icone: 'fas fa-city' },
            { menu_url: 'quartiers', libelle: 'Quartiers', libelle_en: 'Districts', icone: 'fas fa-compass' },
            { menu_url: 'zones', libelle: 'Zones', libelle_en: 'Zones', icone: 'fas fa-border-all' },
            { menu_url: 'jours_feries', libelle: 'Jours Fériés', libelle_en: 'Holidays', icone: 'fas fa-flag' },

            // People & Events
            { menu_url: 'titres_civilites', libelle: 'Titres Civilité', libelle_en: 'Titles', icone: 'fas fa-user-tie' },
            { menu_url: 'types_evenement', libelle: 'Types Événement', libelle_en: 'Event Types', icone: 'fas fa-calendar-alt' }
        ]
    },
    {
        id: 'administration',
        libelle: 'Administration',
        libelle_en: 'Administration',
        icone: 'fas fa-user-shield',
        feature: [
            { menu_url: 'profiles', libelle: 'Gestion des profils', libelle_en: 'Profile Management', icone: 'fas fa-users-cog' },
            { menu_url: 'users', libelle: 'Gestion des utilisateurs', libelle_en: 'User Management', icone: 'fas fa-users' },
            { menu_url: 'modules', libelle: 'Module', libelle_en: 'Module', icone: 'fas fa-puzzle-piece' },
            { menu_url: 'fonctionnalites', libelle: 'Fonctionnalité', libelle_en: 'Feature', icone: 'fas fa-wrench' },
            { menu_url: 'permissions', libelle: 'Permission', libelle_en: 'Permission', icone: 'fas fa-key' }
        ]
    }
];

// ============================================================================
// SOUS-GROUPES (sous-modules) DU PARAMÉTRAGE
// ============================================================================

/**
 * Regroupe les nombreuses fonctionnalités du module Paramétrage en sous-modules
 * pour faciliter la navigation. Les clés correspondent aux menu_url (forme
 * canonique) ; la correspondance est faite de façon souple (- / _ / pluriel).
 */
const PARAMETRAGE_GROUPS = [
    { id: 'org-academique', libelle: 'Organisation Académique', libelle_en: 'Academic Organization', icone: 'fas fa-sitemap',
      items: ['annees_scolaires', 'periodes_colaires', 'niveaux', 'niveaux_etude', 'cycles_enseignement', 'classes', 'sections'] },
    { id: 'enseignement', libelle: 'Enseignement & Cours', libelle_en: 'Teaching & Courses', icone: 'fas fa-chalkboard-teacher',
      items: ['types_cours', 'types_enseignement', 'groupes_matiere', 'matiere_unites'] },
    { id: 'examens', libelle: 'Examens & Évaluations', libelle_en: 'Exams & Assessments', icone: 'fas fa-pen-to-square',
      items: ['natures_examens', 'types_examens'] },
    { id: 'acteurs', libelle: 'Apprenants & Enseignants', libelle_en: 'Students & Teachers', icone: 'fas fa-users',
      items: ['types_apprenants', 'categories_apprenant', 'categories_enseignant'] },
    { id: 'institutions', libelle: 'Institutions', libelle_en: 'Institutions', icone: 'fas fa-school',
      items: ['campuses', 'ecoles', 'institution', 'types_etablissements', 'types_etablissement_spe'] },
    { id: 'structure', libelle: 'Structure Organisationnelle', libelle_en: 'Organizational Structure', icone: 'fas fa-object-group',
      items: ['unite_organisationnelles', 'fonctions'] },
    { id: 'contrats', libelle: 'Contrats & Ressources', libelle_en: 'Contracts & Resources', icone: 'fas fa-file-contract',
      items: ['natures_contrat', 'types_ressource', 'fichiers'] },
    { id: 'financier', libelle: 'Financier', libelle_en: 'Financial', icone: 'fas fa-money-bill',
      items: ['devises'] },
    { id: 'geographie', libelle: 'Géographie & Calendrier', libelle_en: 'Geography & Calendar', icone: 'fas fa-earth-africa',
      items: ['pays', 'regions', 'departements', 'communes', 'quartiers', 'zones', 'jours_feries'] },
    { id: 'personnes', libelle: 'Personnes & Événements', libelle_en: 'People & Events', icone: 'fas fa-user-tie',
      items: ['titres_civilites', 'types_evenement'] },
];

/**
 * Sous-groupes (sous-modules) du module Académique.
 */
const ACADEMIQUE_GROUPS = [
    { id: 'gestion', libelle: 'Gestion Académique', libelle_en: 'Academic Management', icone: 'fas fa-users',
      items: ['apprenants', 'enseignants', 'tuteurs', 'matieres'] },
    { id: 'pedagogie', libelle: 'Pédagogie', libelle_en: 'Teaching', icone: 'fas fa-chalkboard-teacher',
      items: ['cours', 'seances', 'emplois-du-temps'] },
    { id: 'evaluations', libelle: 'Évaluations & Notes', libelle_en: 'Assessments & Grades', icone: 'fas fa-star',
      items: ['evaluations', 'notes', 'bulletins', 'moyennes-matieres'] },
    { id: 'travaux', libelle: 'Travaux', libelle_en: 'Assignments', icone: 'fas fa-list-check',
      items: ['devoirs', 'rendus-devoirs'] },
    { id: 'inscriptions', libelle: 'Inscriptions & Dossiers', libelle_en: 'Enrollments & Files', icone: 'fas fa-clipboard-check',
      items: ['inscriptions', 'dossiers-apprenants'] },
    { id: 'absences', libelle: 'Absences & Présences', libelle_en: 'Absences & Attendance', icone: 'fas fa-user-check',
      items: ['absences-apprenants', 'absences-enseignants', 'presences', 'presences-seances', 'justificatifs-absences'] },
    { id: 'examens', libelle: 'Examens', libelle_en: 'Exams', icone: 'fas fa-file-pen',
      items: ['planification-examens', 'examens-en-ligne', 'mes-examens', 'exam-finance'] },
    { id: 'administration', libelle: 'Administration', libelle_en: 'Administration', icone: 'fas fa-briefcase',
      items: ['personnels-administratifs'] },
];

// Map module -> groupes. Plusieurs modules peuvent être groupés.
const MENU_GROUPS = {
    parametrage: PARAMETRAGE_GROUPS,
    academique: ACADEMIQUE_GROUPS,
};

/**
 * Normalise un identifiant : minuscules, sans accents, sans - / _ / espaces,
 * et chaque mot au singulier (on retire le "s" final de chaque mot). Absorbe
 * toutes les variations rencontrées (-, _, pluriel interne ou final).
 */
function normalizeKey(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '') // supprime les accents
        .toLowerCase()
        .split(/[-_\s]+/)
        .map(word => word.replace(/s$/, ''))
        .join('');
}

/**
 * Retrouve la définition de sous-groupes d'un module quelle que soit la source
 * (id numérique venant de la base OU id texte du fallback). On teste plusieurs
 * champs car le backend envoie un id numérique, pas la clé "parametrage".
 */
function resolveGroupsDef(menu) {
    const candidates = [menu.id, menu.code, menu.route_prefix, menu.libelle, menu.libelle_en]
        .filter(Boolean)
        .map(normalizeKey);

    for (const key of Object.keys(MENU_GROUPS)) {
        const normKey = normalizeKey(key);
        if (candidates.includes(normKey)) {
            return MENU_GROUPS[key];
        }
    }
    return null;
}

// ============================================================================
// REACTIVE STATE
// ============================================================================

// Menus expandus (pour mode non-collapsed)
const expandedMenus = ref([]);

// Sous-groupes expandus (sous-modules, ex: Paramétrage)
const expandedGroups = ref([]);

// Position du sous-menu popup (pour mode collapsed)
const submenuPosition = ref({ top: 0, left: 0 });

// Menu survolé (pour mode collapsed - hover)
const hoveredMenu = ref(null);

// Timer pour le délai de fermeture du sous-menu
let closeTimer = null;

// ============================================================================
// COMPUTED PROPERTIES
// ============================================================================

/**
 * Menu items from backend navbars prop, or fallback to default config
 * Check both existence AND length > 0 because empty array [] is truthy in JS
 */
const menuItems = computed(() => {
    const navbars = page.props.navbars;
    const baseMenus = (navbars && navbars.length > 0) ? navbars : DEFAULT_MENU_CONFIG;

    // Ajouter le menu élève si l'utilisateur a le rôle 'eleve'
    if (isEleve.value) {
        return [...ELEVE_MENU_CONFIG, ...baseMenus];
    }

    return baseMenus;
});

/**
 * Current locale (fr or en)
 */
const currentLocale = computed(() => page.props.locale || 'fr');

/**
 * Current active menu URL (from route)
 */
const currentMenu = computed(() => page.props.menu_url || '');

// ============================================================================
// PERMISSION CHECK FUNCTIONS
// ============================================================================

/**
 * Vérifie si l'utilisateur a la permission "list" pour une feature
 * Convention: permission = "{menu_url}-list"
 */
function canAccessFeature(feature) {
    // Super Admin a accès à tout
    if (isSuperAdmin.value) return true;

    // Les features du menu élève sont toujours accessibles pour les élèves
    if (isEleve.value && feature.menu_url?.startsWith('apprenant/')) return true;

    // Vérifier la permission "list" pour cette feature
    const permissionName = `${feature.menu_url}-list`;
    return hasPermission(permissionName);
}

/**
 * Filtre les features d'un menu selon les permissions
 */
function getVisibleFeatures(menu) {
    // Support both 'feature' (from DEFAULT_MENU_CONFIG) and 'fonctionnalitesActives' (from backend)
    const features = menu.fonctionnalitesActives || menu.feature || [];
    return features.filter(feature => canAccessFeature(feature));
}

/**
 * Vérifie si un menu doit être affiché (au moins une feature visible)
 */
function canAccessMenu(menu) {
    // Super Admin voit TOUS les modules, même sans features
    if (isSuperAdmin.value) return true;

    // Le menu élève est toujours visible pour les élèves
    if (isEleve.value && menu.id === 'mon-espace') return true;

    // Support both 'feature' and 'fonctionnalitesActives'
    const features = menu.fonctionnalitesActives || menu.feature || [];
    if (features.length === 0) return false;

    // Pour les autres, vérifier les permissions
    return getVisibleFeatures(menu).length > 0;
}

// ============================================================================
// MENU INTERACTION FUNCTIONS
// ============================================================================

/**
 * Basculer l'expansion d'un sous-menu (mode non-collapsed)
 */
function toggleSubmenu(menuId) {
    // En mode collapsed, ne pas utiliser le toggle click
    if (props.isCollapsed) return;

    const index = expandedMenus.value.indexOf(menuId);
    if (index > -1) {
        expandedMenus.value.splice(index, 1);
    } else {
        // Fermer les autres menus
        expandedMenus.value = [menuId];
    }
}

/**
 * Gestion du survol pour afficher le sous-menu en popup (mode collapsed)
 */
function handleMenuHover(menuId, event) {
    if (!props.isCollapsed) return;

    // Annuler le timer de fermeture si on survole à nouveau
    if (closeTimer) {
        clearTimeout(closeTimer);
        closeTimer = null;
    }

    hoveredMenu.value = menuId;

    // Calculer la position du popup relativement au bouton
    const button = event.currentTarget;
    const rect = button.getBoundingClientRect();
    submenuPosition.value = {
        top: rect.top,
        left: rect.right + 10
    };
}

/**
 * Gestion de la sortie du survol pour fermer le sous-menu (mode collapsed)
 * Utilise un délai pour éviter les fermetures accidentelles lors du passage au popup
 */
function handleMenuLeave() {
    if (!props.isCollapsed) return;

    // Délai d'une seconde avant de fermer
    closeTimer = setTimeout(() => {
        hoveredMenu.value = null;
    }, 1000);
}

/**
 * Vérifie si le sous-menu doit être visible
 */
function isSubmenuVisible(menuId) {
    if (props.isCollapsed) {
        return hoveredMenu.value === menuId;
    }
    return expandedMenus.value.includes(menuId);
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Chemin actuel normalisé (sans query string, sans slash final), en minuscules.
 * Source de vérité : l'URL réelle de la page Inertia.
 */
const currentPath = computed(() => {
    const raw = page.url || (typeof window !== 'undefined' ? window.location.pathname : '');
    const path = String(raw).split('?')[0].replace(/\/+$/, '');
    return ('/' + path.replace(/^\/+/, '')).toLowerCase();
});

/**
 * Convertit un href (absolu ou relatif) en chemin normalisé comparable.
 */
function hrefToPath(href) {
    try {
        const origin = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
        const path = new URL(href, origin).pathname.replace(/\/+$/, '');
        return ('/' + path.replace(/^\/+/, '')).toLowerCase();
    } catch (e) {
        const path = String(href).split('?')[0].replace(/\/+$/, '');
        return ('/' + path.replace(/^\/+/, '')).toLowerCase();
    }
}

/**
 * Vérifie si une feature est active en comparant son href réel à l'URL actuelle.
 * Gère aussi les sous-pages (create / edit / show) via le préfixe.
 */
function isActiveFeature(feature, menu) {
    const target = hrefToPath(getFeatureHref(feature, menu));
    if (target === '/' || target === '') {
        return currentPath.value === '/' || currentPath.value === '';
    }
    return currentPath.value === target || currentPath.value.startsWith(target + '/');
}

/**
 * Vérifie si un menu (parent) contient la feature actuellement active.
 * Sert à mettre en évidence le menu parent dans la sidebar.
 */
function isMenuActive(menu) {
    const features = menu.fonctionnalitesActives || menu.feature || [];
    return features.some(feature => isActiveFeature(feature, menu));
}

// ============================================================================
// SOUS-GROUPES (sous-modules)
// ============================================================================

/**
 * Retourne les sous-groupes d'un module (ex: paramétrage), avec leurs features
 * filtrées par permission, ou null si le module n'est pas groupé.
 * Les features non rattachées à un groupe sont placées dans un groupe "Autres".
 */
function getMenuGroups(menu) {
    const groupsDef = resolveGroupsDef(menu);
    if (!groupsDef) return null;

    const features = (menu.fonctionnalitesActives || menu.feature || [])
        .filter(feature => canAccessFeature(feature));
    if (features.length === 0) return [];

    // Jetons normalisés d'une feature : menu_url + libellés (robuste si menu_url diffère).
    const featureTokens = feature => [feature.menu_url, feature.libelle, feature.libelle_en]
        .filter(Boolean)
        .map(normalizeKey);

    const used = new Set();
    const groups = groupsDef.map(group => {
        const wanted = group.items.map(normalizeKey);
        const groupFeatures = features.filter(feature => {
            const tokens = featureTokens(feature);
            const match = wanted.some(w => tokens.includes(w));
            if (match) used.add(feature.menu_url);
            return match;
        });
        return { ...group, features: groupFeatures };
    }).filter(group => group.features.length > 0);

    // Features non classées -> groupe "Autres"
    const leftovers = features.filter(feature => !used.has(feature.menu_url));
    if (leftovers.length > 0) {
        groups.push({
            id: '__autres__',
            libelle: 'Autres',
            libelle_en: 'Others',
            icone: 'fas fa-ellipsis',
            features: leftovers,
        });
    }

    return groups;
}

/**
 * Un sous-groupe est ouvert s'il est dans la liste, contient la page active,
 * ou si la sidebar est réduite (popup : tout est visible).
 */
function isGroupOpen(menu, group) {
    if (props.isCollapsed) return true;
    const key = `${menu.id}:${group.id}`;
    return expandedGroups.value.includes(key) || isGroupActive(group, menu);
}

function isGroupActive(group, menu) {
    return group.features.some(feature => isActiveFeature(feature, menu));
}

function toggleGroup(menu, group) {
    if (props.isCollapsed) return;
    const key = `${menu.id}:${group.id}`;
    const index = expandedGroups.value.indexOf(key);
    if (index > -1) {
        expandedGroups.value.splice(index, 1);
    } else {
        expandedGroups.value.push(key);
    }
}

/**
 * Retourne le libellé du menu dans la langue actuelle
 */
function getMenuLabel(menu) {
    if (currentLocale.value === 'en' && menu.libelle_en) {
        return menu.libelle_en;
    }
    return menu.libelle;
}

/**
 * Construit le href approprié pour une feature en tenant compte du module.
 * Implémentation partagée avec le CommandPalette (source de vérité unique).
 */
function getFeatureHref(feature, menu) {
    return buildFeatureHref(feature, menu);
}

/**
 * Gère la déconnexion de l'utilisateur
 */
function handleLogout() {
    sessionStorage.removeItem('login');
    sessionStorage.removeItem('home');
    if (openLogoutModal) {
        openLogoutModal();
    }
}

// ============================================================================
// LIFECYCLE HOOKS
// ============================================================================

/**
 * Déplier automatiquement le menu parent de la feature actuellement active au chargement
 */
onMounted(() => {
    // Déplier le menu parent contenant la page active (et son sous-groupe).
    menuItems.value.forEach(menu => {
        if (isMenuActive(menu)) {
            expandedMenus.value = [menu.id || menu.libelle];

            const groups = getMenuGroups(menu);
            if (groups) {
                groups.forEach(group => {
                    if (isGroupActive(group, menu)) {
                        expandedGroups.value.push(`${menu.id}:${group.id}`);
                    }
                });
            }
        }
    });
});
</script>

<template>
    <aside
        class="sidebar"
        :class="{
            'collapsed': isCollapsed,
            'mobile-open': isMobileOpen
        }"
    >
        <div class="sidebar-inner">
            <!-- Logo -->
            <div class="sidebar-logo">
                <Link :href="route('home')">
                    <img v-if="!isCollapsed" :src="page.props.appConfig?.logo || '/images/image.png'" :alt="page.props.appConfig?.nom || 'AGREE SIKUL'" class="logo-full" />
                    <img v-else :src="page.props.appConfig?.logo || '/images/image.png'" :alt="page.props.appConfig?.sigle || 'AS'" class="logo-small" />
                </Link>
            </div>

            <!-- Menu -->
            <nav class="sidebar-nav">
                <ul class="sidebar-menu">
                    <!-- Accueil -->
                    <li class="menu-item">
                        <Link
                            :href="route('home')"
                            class="menu-link"
                            :class="{ 'active': currentMenu === 'home' || currentMenu === '' }"
                        >
                            <span class="menu-icon">
                                <i class="fas fa-th-large"></i>
                            </span>
                            <span class="menu-title">{{ currentLocale === 'en' ? 'Home' : 'Accueil' }}</span>
                        </Link>
                    </li>

                    <!-- Menus dynamiques avec sous-menus -->
                    <template v-for="menu in menuItems" :key="menu.id || menu.libelle">
                        <li
                            v-if="canAccessMenu(menu)"
                            class="menu-item has-submenu"
                            :class="{
                                'open': isSubmenuVisible(menu.id || menu.libelle),
                                'is-active': isMenuActive(menu)
                            }"
                            @mouseenter="handleMenuHover(menu.id || menu.libelle, $event)"
                            @mouseleave="handleMenuLeave"
                        >
                            <button
                                class="menu-link"
                                :class="{ 'active-parent': isMenuActive(menu) }"
                                @click="toggleSubmenu(menu.id || menu.libelle)"
                            >
                                <span class="menu-icon">
                                    <i :class="menu.icone"></i>
                                </span>
                                <span class="menu-title">{{ getMenuLabel(menu) }}</span>
                                <span class="menu-arrow">
                                    <i class="fas fa-chevron-down"></i>
                                </span>
                            </button>

                            <!-- Sous-menu -->
                            <Transition name="submenu">
                                <ul
                                    v-show="isSubmenuVisible(menu.id || menu.libelle)"
                                    class="submenu"
                                    :style="isCollapsed ? { position: 'fixed', top: submenuPosition.top + 'px', left: submenuPosition.left + 'px' } : {}"
                                >
                                    <!-- Module groupé en sous-modules (ex: Paramétrage) -->
                                    <template v-if="getMenuGroups(menu)">
                                        <li
                                            v-for="group in getMenuGroups(menu)"
                                            :key="group.id"
                                            class="submenu-group"
                                            :class="{ 'open': isGroupOpen(menu, group), 'is-active': isGroupActive(group, menu) }"
                                        >
                                            <button
                                                class="submenu-group-header"
                                                @click.stop="toggleGroup(menu, group)"
                                            >
                                                <span class="group-icon"><i :class="group.icone"></i></span>
                                                <span class="group-title">{{ getMenuLabel(group) }}</span>
                                                <span class="group-arrow"><i class="fas fa-chevron-down"></i></span>
                                            </button>
                                            <ul v-show="isGroupOpen(menu, group)" class="submenu-grouped">
                                                <li
                                                    v-for="feature in group.features"
                                                    :key="feature.menu_url"
                                                    class="submenu-item"
                                                >
                                                    <Link
                                                        :href="getFeatureHref(feature, menu)"
                                                        class="submenu-link"
                                                        :class="{ 'active': isActiveFeature(feature, menu) }"
                                                    >
                                                        <span class="submenu-icon">
                                                            <i :class="feature.icone"></i>
                                                        </span>
                                                        <span class="submenu-title">{{ getMenuLabel(feature) }}</span>
                                                    </Link>
                                                </li>
                                            </ul>
                                        </li>
                                    </template>

                                    <!-- Module plat (sans sous-modules) -->
                                    <template v-else>
                                        <template
                                            v-for="feature in (menu.fonctionnalitesActives || menu.feature || [])"
                                            :key="feature.menu_url"
                                        >
                                            <li
                                                v-if="canAccessFeature(feature)"
                                                class="submenu-item"
                                            >
                                                <Link
                                                    :href="getFeatureHref(feature, menu)"
                                                    class="submenu-link"
                                                    :class="{ 'active': isActiveFeature(feature, menu) }"
                                                >
                                                    <span class="submenu-icon">
                                                        <i :class="feature.icone"></i>
                                                    </span>
                                                    <span class="submenu-title">{{ getMenuLabel(feature) }}</span>
                                                </Link>
                                            </li>
                                        </template>
                                    </template>
                                </ul>
                            </Transition>
                        </li>
                    </template>

                    <!-- Déconnexion -->
                    <li class="menu-item menu-item-logout">
                        <button
                            class="menu-link"
                            @click="handleLogout"
                        >
                            <span class="menu-icon">
                                <i class="fas fa-sign-out-alt"></i>
                            </span>
                            <span class="menu-title">{{ currentLocale === 'en' ? 'Logout' : 'Déconnexion' }}</span>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>
</template>

<style scoped>
.sidebar::-webkit-scrollbar { width: 3px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
.sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }

.sidebar {
    width: 260px;
    position: fixed;
    background: #0c1e36;
    border: none;
    border-radius: 0;
    margin: 0;
    padding: 0;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 999;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: 'Poppins', sans-serif;
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.15);
}

.sidebar-inner {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: visible;
    padding: 0 10px;
}

/* Logo */
.sidebar-logo {
    padding: 15px 5px 12px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    margin-bottom: 10px;
}

.sidebar-logo a {
    display: block;
}

.sidebar-logo .logo-full {
    max-width: 160px;
    height: auto;
    transition: all 0.3s;
}

.sidebar-logo .logo-small {
    width: 40px;
    height: 40px;
    object-fit: contain;
    transition: all 0.3s;
}

/* Header */
.sidebar-header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding-bottom: 15px;
    margin-bottom: 15px;
}

.sidebar-header::before {
    position: absolute;
    content: "";
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, rgba(224, 225, 226, 0) 0%, rgb(224, 225, 226) 47.22%, rgba(224, 225, 226, 0.157) 94.44%);
}

.sidebar-header :deep(.logo) {
    margin: 0 auto;
    max-width: 130px;
}

.sidebar-header :deep(.logo) img {
    background: transparent !important;
}

/* Navigation */
.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding-top: 5px;
    padding-right: 2px;
}

.sidebar-menu {
    display: flex;
    flex-direction: column;
    gap: 0;
    list-style: none;
    margin: 0;
    padding: 0;
}

/* Menu items */
.menu-item {
    position: relative;
    margin-bottom: 1px;
    border-radius: 8px;
    padding: 0;
}

.menu-link {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    color: rgba(255, 255, 255, 0.55);
    font-weight: 500;
    padding: 9px 12px;
    transition: all 0.2s ease;
    text-decoration: none;
    background: transparent;
    border: none;
    cursor: pointer;
    text-align: left;
    font-family: 'Poppins', sans-serif;
    width: 100%;
    font-size: 13px;
    border-radius: 8px;
    position: relative;
}

.menu-link:hover {
    color: rgba(255, 255, 255, 0.9);
    background: rgba(255, 255, 255, 0.05);
}

.menu-link.active {
    color: #fff;
    background: #E5590C;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(229, 89, 12, 0.3);
}

.menu-link.active .menu-icon {
    color: #fff;
    background: rgba(255, 255, 255, 0.2);
}

.has-submenu.open {
    background: transparent;
    border-radius: 8px;
}

.has-submenu.open > .menu-link {
    color: rgba(255, 255, 255, 0.9);
}

.menu-icon {
    width: 32px;
    height: 32px;
    background: transparent;
    color: #E5590C;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 8px;
    font-size: 16px;
    margin-right: 10px;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.menu-link:hover .menu-icon {
    color: #E5590C;
}

.menu-link.active .menu-icon {
    color: #fff;
}

.menu-title {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.menu-arrow {
    transition: transform 0.2s ease;
    font-size: 12px;
    color: #4A5568;
}

.has-submenu.open .menu-arrow {
    transform: rotate(180deg);
}

/* Sous-menu */
.submenu {
    margin-top: 0;
    margin-bottom: 4px;
    padding: 2px 0 2px 0;
    list-style: none;
    background: rgba(255, 255, 255, 0.02);
    border-radius: 0 0 8px 8px;
    margin-left: 0;
    border-left: none;
}

.submenu-item { margin: 0; }

.submenu-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px 8px 44px;
    border-radius: 6px;
    color: rgba(255, 255, 255, 0.45);
    font-size: 12.5px;
    font-weight: 400;
    transition: all 0.2s ease;
    text-decoration: none;
    background: transparent;
    position: relative;
}

.submenu-link::before {
    content: '';
    position: absolute;
    left: 26px;
    top: 50%;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-50%);
    transition: all 0.2s;
}

.submenu-link:hover {
    color: rgba(255, 255, 255, 0.85);
    background: rgba(255, 255, 255, 0.04);
}

.submenu-link:hover::before {
    background: #E5590C;
}

.submenu-link.active {
    color: #E5590C;
    font-weight: 600;
    background: rgba(229, 89, 12, 0.08);
}

.submenu-link.active::before {
    background: #E5590C;
    width: 6px;
    height: 6px;
    box-shadow: 0 0 6px rgba(229, 89, 12, 0.5);
}

.submenu-icon {
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    opacity: 0.6;
}

.submenu-link.active .submenu-icon { opacity: 1; color: #E5590C; }

/* Parent menu actif (focus : on sait dans quel menu on se trouve) */
.menu-link.active-parent {
    color: #fff;
    background: rgba(229, 89, 12, 0.14);
    font-weight: 600;
}

.menu-link.active-parent .menu-icon {
    color: #E5590C;
}

/* Barre d'accent à gauche du menu actif */
.menu-item.has-submenu.is-active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 6px;
    bottom: 6px;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: #E5590C;
}

/* ===== Sous-modules (groupes) ===== */
.submenu-group {
    margin: 0;
    list-style: none;
}

.submenu-group-header {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 8px 12px 8px 24px;
    background: transparent;
    border: none;
    cursor: pointer;
    color: rgba(255, 255, 255, 0.5);
    font-family: 'Poppins', sans-serif;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    text-align: left;
    transition: all 0.2s ease;
    border-radius: 6px;
}

.submenu-group-header:hover {
    color: rgba(255, 255, 255, 0.85);
    background: rgba(255, 255, 255, 0.04);
}

.submenu-group.is-active > .submenu-group-header {
    color: #E5590C;
}

.group-icon {
    width: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #E5590C;
    opacity: 0.8;
    flex-shrink: 0;
}

.group-title {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.group-arrow {
    font-size: 9px;
    transition: transform 0.2s ease;
    opacity: 0.6;
}

.submenu-group.open > .submenu-group-header .group-arrow {
    transform: rotate(180deg);
}

.submenu-grouped {
    list-style: none;
    margin: 0 0 4px 0;
    padding: 0;
}

/* Indente un peu plus les liens à l'intérieur d'un sous-module */
.submenu-grouped .submenu-link {
    padding-left: 52px;
}

.submenu-grouped .submenu-link::before {
    left: 34px;
}

/* En mode réduit (popup), les sous-modules restent lisibles */
.sidebar.collapsed .submenu-group-header {
    padding-left: 12px;
}

.sidebar.collapsed .submenu-grouped .submenu-link {
    padding-left: 22px;
}

.sidebar.collapsed .submenu-grouped .submenu-link::before {
    display: none;
}

/* Logout */
.menu-item-logout {
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    margin-bottom: 10px;
}

.menu-item-logout .menu-link {
    background: transparent;
    color: rgba(255, 255, 255, 0.45);
}

.menu-item-logout .menu-link:hover {
    background: rgba(229, 89, 12, 0.1);
    color: #E5590C;
}

.menu-item-logout .menu-icon {
    color: rgba(255, 255, 255, 0.3);
}

/* Collapsed state - base */
.sidebar.collapsed {
    width: 70px;
    overflow: visible;
}

.sidebar.collapsed .sidebar-inner {
    padding: 0 6px;
}

.sidebar.collapsed .menu-title,
.sidebar.collapsed .menu-arrow {
    display: none;
}

.sidebar.collapsed .sidebar-header {
    justify-content: center;
    padding: 20px 8px;
}

.sidebar.collapsed .menu-link {
    justify-content: center;
    padding: 14px;
}

/* Collapsed: sous-menu caché par défaut */
.sidebar.collapsed .submenu {
    display: none;
}

/* Collapsed: popup sous-menu */
.sidebar.collapsed .has-submenu.open .submenu {
    display: block;
    min-width: 220px;
    background: #0c1e36;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
    padding: 8px 6px;
    z-index: 9999;
    margin-left: 0;
}

.sidebar.collapsed .has-submenu.open .submenu::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 18px;
    border: 8px solid transparent;
    border-right-color: #0c1e36;
}

.sidebar.collapsed .has-submenu.open .submenu .submenu-title { display: inline; }

.sidebar.collapsed .has-submenu.open .submenu .submenu-link {
    padding: 8px 14px 8px 14px;
    border-radius: 6px;
    background: transparent;
    color: rgba(255, 255, 255, 0.5);
    font-size: 12px;
}

.sidebar.collapsed .has-submenu.open .submenu .submenu-link::before { display: none; }

.sidebar.collapsed .has-submenu.open .submenu .submenu-link:hover {
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.9);
}

.sidebar.collapsed .has-submenu.open .submenu .submenu-link.active {
    background: #E5590C;
    color: #fff;
    font-weight: 600;
}

/* Submenu transition */
.submenu-enter-active,
.submenu-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}



.submenu-enter-from,
.submenu-leave-to {
    opacity: 0;
    max-height: 0;
}

.submenu-enter-to,
.submenu-leave-from {
    opacity: 1;
    max-height: 500px;
}

/* Tablettes - Forcer mode collapsed */
@media (max-width: 992px) and (min-width: 769px) {
    .sidebar {
        width: 80px;
        overflow: visible;
    }

    .sidebar .menu-title,
    .sidebar .menu-arrow {
        display: none;
    }

    .sidebar .menu-link {
        justify-content: center;
        padding: 14px;
    }

    .sidebar .submenu {
        display: none;
    }

    .sidebar .has-submenu.open .submenu {
        display: block;
        position: fixed;
        min-width: 220px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        padding: 12px 8px;
        z-index: 9999;
    }

    .sidebar .has-submenu.open .submenu .submenu-title {
        display: inline;
    }

    .sidebar .has-submenu.open .submenu .submenu-link {
        padding: 10px 14px;
        border-radius: 8px;
    }
}

/* Mobile */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        width: 250px;
    }

    .sidebar .menu-title,
    .sidebar .menu-arrow {
        display: inline;
    }

    .sidebar .menu-link {
        justify-content: flex-start;
        padding: 12px 15px;
    }

    .sidebar .submenu {
        display: block;
        position: relative !important;
        min-width: auto;
        background: rgba(12, 86, 219, 0.1);
        border: none;
        box-shadow: none;
    }

    .sidebar.mobile-open {
        transform: translateX(0);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .sidebar.collapsed {
        width: 250px;
    }

    .sidebar.collapsed .menu-title,
    .sidebar.collapsed .menu-arrow {
        display: inline;
    }

    .sidebar.collapsed .menu-link {
        justify-content: flex-start;
    }
}
</style>
