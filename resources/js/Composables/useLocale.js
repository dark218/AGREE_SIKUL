import { computed, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

export function useLocale() {
    const page = usePage();
    const { locale, t } = useI18n();

    // Récupérer la locale depuis Laravel
    const currentLocale = computed(() => page.props.locale || 'fr');

    // Synchroniser vue-i18n avec Laravel lors du montage
    locale.value = currentLocale.value;

    // Surveiller les changements de locale depuis Laravel
    watch(currentLocale, (newLocale) => {
        if (locale.value !== newLocale) {
            locale.value = newLocale;
        }
    });

    /**
     * Changer la langue
     * @param {string} newLocale - Le code de la nouvelle langue ('fr' ou 'en')
     */
    async function changeLocale(newLocale) {
        if (!['fr', 'en'].includes(newLocale)) {
            console.warn(`Locale non supportée: ${newLocale}`);
            return;
        }

        // Mettre à jour vue-i18n immédiatement
        locale.value = newLocale;

        // Envoyer la requête à Laravel pour persister la langue et recharger les props Inertia
        try {
            await fetch(`/lang/${newLocale}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            // Recharger uniquement les props Inertia sans refresh complet de la page
            router.reload({ preserveScroll: true });

        } catch (error) {
            console.warn('Erreur lors du changement de langue:', error);
        }
    }

    return {
        locale,
        currentLocale,
        changeLocale,
        t,
    };
}
