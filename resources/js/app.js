import "./bootstrap";
import "../css/app.css";
import "../css/modules.css";

// --------------------------
// jQuery + Select2
// --------------------------
import $ from "jquery";
import "select2/dist/css/select2.min.css";
import select2 from "select2";

window.$ = $;
window.jQuery = $;
$.fn.select2 = select2;

// --------------------------
// Vue 3 + Inertia
// --------------------------
import { createApp, h } from "vue";
import { createInertiaApp, Head, Link } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

// --------------------------
// Toastification
// --------------------------
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";

// --------------------------
// i18n
// --------------------------
import { createI18n } from "vue-i18n";
import fr from "./locales/fr.json";
import en from "./locales/en.json";

const appName = import.meta.env.VITE_APP_NAME || "AGREE SIKUL";

// ---------------------------------------------------------------------------
// Récupération automatique après un déploiement (assets obsolètes)
// ---------------------------------------------------------------------------
// Après un nouveau déploiement, les fichiers JS changent de hash. Un onglet
// resté ouvert référence les anciens fichiers qui n'existent plus (404) : la
// navigation Inertia échoue alors et la page semble "figée".
// Ici on détecte ce cas et on recharge en dur UNE fois pour récupérer les
// nouveaux assets. Un garde-fou de 10s évite toute boucle de rechargement.
function reloadOnStaleChunk() {
    const KEY = 'chunkReloadAt';
    const now = Date.now();
    const last = parseInt(sessionStorage.getItem(KEY) || '0', 10);
    if (now - last > 10000) {
        sessionStorage.setItem(KEY, String(now));
        window.location.reload();
    }
}

// Message d'erreur d'import dynamique selon les navigateurs
function isDynamicImportError(err) {
    const msg = (err && err.message) ? err.message : String(err);
    return (
        msg.includes('Failed to fetch dynamically imported module') ||   // Chrome/Edge
        msg.includes('error loading dynamically imported module') ||      // Firefox
        msg.includes('Importing a module script failed')                  // Safari
    );
}

// Événement émis par Vite quand le préchargement d'un chunk échoue
window.addEventListener('vite:preloadError', (event) => {
    event.preventDefault();
    reloadOnStaleChunk();
});

// Charge un composant de page en récupérant proprement les chunks obsolètes
function loadPageComponent(importer) {
    return importer().catch((err) => {
        if (isDynamicImportError(err)) {
            reloadOnStaleChunk();
        }
        throw err;
    });
}

// Créer l'instance i18n
const i18n = createI18n({
    legacy: false, // Mode Composition API
    locale: "fr", // Langue par défaut
    fallbackLocale: "fr",
    messages: { fr, en },
    globalInjection: true,
});

// --------------------------
// Inertia App
// --------------------------
createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob([
            './Pages/**/*.vue',                       // Pages principales
            '../../Modules/*/Resources/js/Pages/**/*.vue' // Pages modules
        ]);

        // Gestion des modules avec "::" => "ModuleName::PageName"
        if (name.includes('::')) {
            const [module, page] = name.split('::');
            const path = `../../Modules/${module}/Resources/js/Pages/${page}.vue`;
            if (pages[path]) return loadPageComponent(pages[path]);
        }

        // Gestion des modules avec "/" => "ModuleName/PagePath/PageName"
        if (name.includes('/')) {
            const parts = name.split('/');
            const module = parts[0];
            const page = parts.slice(1).join('/');
            const path = `../../Modules/${module}/Resources/js/Pages/${page}.vue`;
            if (pages[path]) return loadPageComponent(pages[path]);
        }

        const mainPath = `./Pages/${name}.vue`;
        if (pages[mainPath]) return loadPageComponent(pages[mainPath]);

        console.log(pages);

        throw new Error(`Page Vue introuvable : ${name}`);
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .use(Toast, {
                position: "top-right",
                timeout: 8000,
                closeOnClick: true,
                pauseOnFocusLoss: true,
                pauseOnHover: true,
                draggable: true,
                draggablePercent: 0.6,
                showCloseButtonOnHover: false,
                hideProgressBar: false,
                closeButton: "button",
                icon: true,
                rtl: false,
            })
            .component('Head', Head)
            .component('Link', Link)
            .mount(el);
    },
    progress: {
        color: "#E5590C", // Orange de la marque
        delay: 150,        // n'affiche la barre que si la navigation dépasse 150ms
        includeCSS: true,
        showSpinner: true, // petit spinner en haut à droite
    },
});
