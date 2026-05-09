import { ref } from 'vue';

// État global du loader
const isLoading = ref(false);
const loaderMessage = ref('Traitement en cours...');
const loaderSubMessage = ref('Veuillez patienter');
const loaderVariant = ref('primary');

/**
 * Composable pour gérer le loader fullpage global
 * @returns {Object} Les méthodes et états du loader
 */
export function useLoader() {
    /**
     * Affiche le loader avec un message personnalisé
     * @param {Object} options - Options du loader
     * @param {string} options.message - Message principal
     * @param {string} options.subMessage - Sous-message
     * @param {string} options.variant - Variante de couleur (primary, success, warning, danger, info)
     */
    const showLoader = (options = {}) => {
        loaderMessage.value = options.message || 'Traitement en cours...';
        loaderSubMessage.value = options.subMessage || 'Veuillez patienter';
        loaderVariant.value = options.variant || 'primary';
        isLoading.value = true;
    };

    /**
     * Cache le loader
     */
    const hideLoader = () => {
        isLoading.value = false;
    };

    /**
     * Affiche le loader pour une action de sauvegarde (store)
     */
    const showStoreLoader = () => {
        showLoader({
            message: 'Enregistrement en cours...',
            subMessage: 'Création de l\'élément',
            variant: 'primary',
        });
    };

    /**
     * Affiche le loader pour une action de mise à jour (update)
     */
    const showUpdateLoader = () => {
        showLoader({
            message: 'Mise à jour en cours...',
            subMessage: 'Modification de l\'élément',
            variant: 'info',
        });
    };

    /**
     * Affiche le loader pour une action de validation
     */
    const showValidateLoader = () => {
        showLoader({
            message: 'Validation en cours...',
            subMessage: 'Traitement de la validation',
            variant: 'success',
        });
    };

    /**
     * Affiche le loader pour une action de rejet
     */
    const showRejectLoader = () => {
        showLoader({
            message: 'Rejet en cours...',
            subMessage: 'Traitement du rejet',
            variant: 'warning',
        });
    };

    /**
     * Affiche le loader pour une action de blocage
     */
    const showBlockLoader = () => {
        showLoader({
            message: 'Blocage en cours...',
            subMessage: 'Traitement du blocage',
            variant: 'danger',
        });
    };

    /**
     * Affiche le loader pour une action de suspension
     */
    const showSuspendLoader = () => {
        showLoader({
            message: 'Suspension en cours...',
            subMessage: 'Traitement de la suspension',
            variant: 'warning',
        });
    };

    /**
     * Affiche le loader pour une action de suppression
     */
    const showDeleteLoader = () => {
        showLoader({
            message: 'Suppression en cours...',
            subMessage: 'Traitement de la suppression',
            variant: 'danger',
        });
    };

    /**
     * Affiche le loader pour une action d'activation
     */
    const showActivateLoader = () => {
        showLoader({
            message: 'Activation en cours...',
            subMessage: 'Traitement de l\'activation',
            variant: 'success',
        });
    };

    /**
     * Affiche le loader pour une action de désactivation
     */
    const showDeactivateLoader = () => {
        showLoader({
            message: 'Désactivation en cours...',
            subMessage: 'Traitement de la désactivation',
            variant: 'warning',
        });
    };

    return {
        // États (retournés directement pour la réactivité)
        isLoading,
        loaderMessage,
        loaderSubMessage,
        loaderVariant,

        // Méthodes générales
        showLoader,
        hideLoader,

        // Méthodes spécifiques aux actions
        showStoreLoader,
        showUpdateLoader,
        showValidateLoader,
        showRejectLoader,
        showBlockLoader,
        showSuspendLoader,
        showDeleteLoader,
        showActivateLoader,
        showDeactivateLoader,
    };
}
