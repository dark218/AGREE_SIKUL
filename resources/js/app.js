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
            if (pages[path]) return pages[path]();
        }

        // Gestion des modules avec "/" => "ModuleName/PagePath/PageName"
        if (name.includes('/')) {
            const parts = name.split('/');
            const module = parts[0];
            const page = parts.slice(1).join('/');
            const path = `../../Modules/${module}/Resources/js/Pages/${page}.vue`;
            if (pages[path]) return pages[path]();
        }

        const mainPath = `./Pages/${name}.vue`;
        if (pages[mainPath]) return pages[mainPath]();

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
