import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';

/**
 * Expose la liste aplatie des fonctionnalités (features) accessibles à
 * l'utilisateur courant, indexable pour la recherche (CommandPalette),
 * ainsi que le résolveur d'URL partagé avec la sidebar.
 *
 * Source : `page.props.navbars` (injecté côté backend).
 * Filtrage : par permission `{feature.menu_url}-list` (sauf Super Admin).
 */

// Fonctionnalités masquées partout (sidebar + recherche Ctrl+K), même pour le
// Super Admin. Comparaison insensible aux tirets/underscores/espaces.
const HIDDEN_FEATURES = ['absences'];
function isHiddenFeature(menuUrl) {
    const norm = String(menuUrl || '').toLowerCase().replace(/[-_\s]+/g, '');
    return HIDDEN_FEATURES.some((h) => h.toLowerCase().replace(/[-_\s]+/g, '') === norm);
}

// Mapping module -> préfixe de route (identique à la sidebar).
const moduleRouteMap = {
    'academique': 'academique',
    'Académique': 'academique',
    'Academic': 'academique',
    'parametrage': 'parametrage',
    'Paramétrage': 'parametrage',
    'Settings': 'parametrage',
    'administration': 'administration',
    'Administration': 'administration',
    'business': 'business',
    'Business': 'business',
    'service-client': 'service-client',
    'Service Client': 'service-client',
    'Customer Service': 'service-client',
    'services': 'services',
    'Services': 'services',
    'finances': 'finances',
    'Finances': 'finances',
};

// Routes particulières qui ne suivent pas la convention {prefix}.{url}.index.
const specialRoutes = {
    // Administration module
    'users': 'administration.users.index',
    'roles': 'administration.roles.index',
    'modules': 'administration.modules.index',
    'fonctionnalites': 'administration.features.index',
    'permissions': 'administration.permissions.index',
    'error-logs': 'administration.errorlog.index',
    // Academique module
    'personnels-administratifs': 'personnels_administratifs.index',
    'emplois-du-temps': 'academique.emplois_du_temps.index',
    'listes-manuels': 'academique.listes-manuels.index',
    'bibliotheque-structures': 'academique.bibliotheque-structures.index',
    'entrees-livres': 'academique.entrees-livres.index',
    'sorties-livres': 'academique.sorties-livres.index',
    'inventaire-livres': 'academique.inventaire-livres.index',
    'exam-finance': 'academique.exam-finance.index',
    'planification-examens': 'academique.planification-examens.index',
    'examens-en-ligne': 'academique.examens-en-ligne.index',
    'mes-examens': 'academique.composition.mes-examens',
    // Services module
    'cantine': 'services-cantine.index',
    'menus': 'menus.index',
    'inscriptions-cantine': 'inscriptions-cantine.index',
    'passages-cantine': 'passages-cantine.index',
    'services-transport': 'services-transport.index',
    'inscriptions-transport': 'inscriptions-transport.index',
    'consultations-infirmerie': 'consultations-infirmerie.index',
    // Finances module
    'ecolage': 'finances.ecolage.index',
    'autres-revenus': 'finances.autres-revenus.index',
    'versements': 'finances.versements.index',
    'facturations-apprenants': 'finances.facturation-apprenants.index',
    'achats-depenses': 'finances.achats-depenses.index',
    'salaires': 'finances.salaires.index',
    'groupes-comptes': 'finances.groupes-comptes.index',
    'lignes-recettes': 'finances.lignes-recettes.index',
    'lignes-depenses': 'finances.lignes-depenses.index',
    'postes-recettes': 'finances.postes-recettes.index',
    'postes-depenses': 'finances.postes-depenses.index',
    'rapports-financiers': 'finances.rapports-financiers.index',
    // Rapport module
    'rapport/statistiques-ecole': 'rapport.statistiques-ecole.index',
    'rapport/statistiques-classes': 'rapport.statistiques-classes.index',
    'parametrage-generaux': 'parametrage-generaux.index',
    'admin-chat': 'admin-chat.index',
};

/**
 * Construit le href d'une fonctionnalité. Source de vérité unique, partagée
 * entre la sidebar et le CommandPalette.
 */
export function buildFeatureHref(feature, menu) {
    if (!feature || !feature.menu_url) return null;

    // Si le menu_url inclut déjà un slash, on l'utilise tel quel.
    if (feature.menu_url.includes('/')) {
        if (specialRoutes[feature.menu_url]) {
            try { return route(specialRoutes[feature.menu_url]); } catch (e) { /* fallback */ }
        }
        return `/${feature.menu_url}`;
    }

    const menuKey = menu?.id || menu?.libelle || menu?.libelle_en;
    const modulePrefix =
        moduleRouteMap[menu?.libelle] ||
        moduleRouteMap[menu?.libelle_en] ||
        moduleRouteMap[menuKey];

    if (specialRoutes[feature.menu_url]) {
        try { return route(specialRoutes[feature.menu_url]); } catch (e) { /* fallback */ }
    }

    if (modulePrefix) {
        const routePart = feature.menu_url.replace(/-/g, '_');
        const routeName = `${modulePrefix}.${routePart}.index`;
        try {
            return route(routeName);
        } catch (e) {
            return `/${modulePrefix}/${feature.menu_url}`;
        }
    }

    return `/${feature.menu_url}`;
}

function labelFor(entity, locale) {
    if (locale === 'en' && entity?.libelle_en) return entity.libelle_en;
    return entity?.libelle || entity?.menu_url || '';
}

export function useAppFeatures() {
    const page = usePage();
    const { hasPermission, isSuperAdmin } = usePermissions();

    const currentLocale = computed(() => page.props.locale || 'fr');
    const rawMenus = computed(() => page.props.navbars || []);

    const features = computed(() => {
        const flat = [];

        rawMenus.value.forEach((menu) => {
            if (menu.id === 'dashboard' || menu.menu_url === 'dashboard') return;

            const list = menu.fonctionnalitesActives || menu.feature || [];
            if (list.length === 0) return;

            const moduleLabel = labelFor(menu, currentLocale.value);
            const section = moduleLabel || 'Autres';

            list.forEach((feature) => {
                // Fonctionnalité masquée (prioritaire, même pour Super Admin)
                if (isHiddenFeature(feature.menu_url)) return;

                // Teste les variantes dash/underscore de menu_url : la DB
                // peut utiliser l'une et le fallback JS l'autre.
                const rawMenu = String(feature.menu_url || '');
                const permVariants = new Set([
                    `${rawMenu}-list`,
                    `${rawMenu.replace(/_/g, '-')}-list`,
                    `${rawMenu.replace(/-/g, '_')}-list`,
                ]);
                const allowed = isSuperAdmin.value
                    || Array.from(permVariants).some(p => hasPermission(p));
                if (!allowed) return;

                const href = buildFeatureHref(feature, menu);
                if (!href) return;

                const featureLabel = labelFor(feature, currentLocale.value);

                flat.push({
                    id: `${menu.id || menu.libelle}:${feature.menu_url}`,
                    label: featureLabel,
                    section,
                    module: moduleLabel,
                    icone: feature.icone,
                    feature_url: feature.menu_url,
                    href,
                    keywords: [
                        featureLabel,
                        moduleLabel,
                        feature.menu_url,
                    ].join(' ').toLowerCase(),
                });
            });
        });

        return flat;
    });

    /**
     * Recherche : tous les tokens de la requête doivent apparaître dans les
     * keywords. Tri par pertinence (exact > début > autre).
     */
    function searchFeatures(query) {
        const q = (query || '').trim().toLowerCase();
        if (!q) return features.value;

        const tokens = q.split(/\s+/).filter(Boolean);
        return features.value
            .filter((f) => tokens.every((t) => f.keywords.includes(t)))
            .sort((a, b) => {
                const aRank = a.label.toLowerCase() === q ? 3
                    : a.label.toLowerCase().startsWith(q) ? 2 : 1;
                const bRank = b.label.toLowerCase() === q ? 3
                    : b.label.toLowerCase().startsWith(q) ? 2 : 1;
                if (aRank !== bRank) return bRank - aRank;
                return a.label.localeCompare(b.label);
            });
    }

    /**
     * Regroupe les features par section (module) pour l'affichage.
     */
    function groupBySection(list) {
        const groups = {};
        list.forEach((f) => {
            if (!groups[f.section]) groups[f.section] = [];
            groups[f.section].push(f);
        });
        return groups;
    }

    return {
        features,
        searchFeatures,
        groupBySection,
    };
}
