import { createI18n } from 'vue-i18n';
import fr from './locales/fr.json';
import en from './locales/en.json';

// Récupérer la locale depuis la page Inertia
const getInitialLocale = () => {
    // Essayer de récupérer depuis le HTML
    const htmlLang = document.documentElement.lang;
    if (htmlLang && ['fr', 'en'].includes(htmlLang)) {
        return htmlLang;
    }

    // Par défaut
    return 'fr';
};

const i18n = createI18n({
    legacy: false, // Mode Composition API
    locale: getInitialLocale(),
    fallbackLocale: 'fr',
    messages: {
        fr,
        en
    },
    globalInjection: true,
    warnHtmlMessage: false
});

export default i18n;
