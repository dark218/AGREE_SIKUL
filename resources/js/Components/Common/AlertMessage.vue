<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(false);
const currentMessage = ref('');
const currentType = ref('info');

// Surveiller les messages flash d'Inertia
watch(() => page.props.flash, (flash) => {
    if (flash) {
        if (flash.success) {
            currentType.value = 'success';
            currentMessage.value = flash.success;
            visible.value = true;
            autoHide();
        } else if (flash.error) {
            currentType.value = 'error';
            currentMessage.value = flash.error;
            visible.value = true;
            autoHide();
        } else if (flash.warning) {
            currentType.value = 'warning';
            currentMessage.value = flash.warning;
            visible.value = true;
            autoHide();
        } else if (flash.info) {
            currentType.value = 'info';
            currentMessage.value = flash.info;
            visible.value = true;
            autoHide();
        }
    }
}, { deep: true, immediate: true });

// Surveiller les erreurs de validation Inertia : si on a des erreurs sur des
// champs après un submit, on affiche une alerte en haut pour que l'utilisateur
// sache où chercher (les <span text-danger> sous chaque champ peuvent être
// hors vue).
watch(() => page.props.errors, (errors) => {
    if (!errors) return;
    const errorKeys = Object.keys(errors).filter(k => errors[k]);
    if (errorKeys.length === 0) return;

    // Cas spécial : si une seule erreur "_error" ou "error" générique → on l'affiche telle quelle
    if (errorKeys.length === 1 && (errorKeys[0] === '_error' || errorKeys[0] === 'error')) {
        currentType.value = 'error';
        currentMessage.value = errors[errorKeys[0]];
        visible.value = true;
        return;
    }

    // Multi-erreurs : on liste les 3 premières + compte total
    const firstThree = errorKeys.slice(0, 3).map(k => {
        const val = errors[k];
        const msg = Array.isArray(val) ? val[0] : val;
        return `• ${msg}`;
    }).join('\n');
    const more = errorKeys.length > 3 ? `\n… et ${errorKeys.length - 3} autre(s)` : '';
    currentType.value = 'error';
    currentMessage.value = `Le formulaire contient ${errorKeys.length} erreur(s) :\n${firstThree}${more}`;
    visible.value = true;
}, { deep: true, immediate: true });

const alertClass = computed(() => `alert-${currentType.value}`);

const iconPath = computed(() => {
    const icons = {
        success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    };
    return icons[currentType.value] || icons.info;
});

function dismiss() {
    visible.value = false;
}

function autoHide() {
    setTimeout(() => {
        visible.value = false;
    }, 5000);
}
</script>

<template>
    <Transition name="alert">
        <div v-if="visible && currentMessage" class="alert" :class="alertClass">
            <svg class="alert-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path :d="iconPath" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="alert-message" style="white-space: pre-line;">{{ currentMessage }}</span>
            <button
                class="alert-close"
                @click="dismiss"
                aria-label="Fermer"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </Transition>
</template>

<style scoped>
.alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 16px;
    font-size: 0.875rem;
}

.alert-success {
    background: #D1FAE5;
    color: #065F46;
    border: 1px solid #A7F3D0;
}

.alert-error {
    background: #FEE2E2;
    color: #991B1B;
    border: 1px solid #FECACA;
}

.alert-warning {
    background: #FEF3C7;
    color: #92400E;
    border: 1px solid #FDE68A;
}

.alert-info {
    background: #DBEAFE;
    color: #1E40AF;
    border: 1px solid #BFDBFE;
}

.alert-icon {
    flex-shrink: 0;
}

.alert-message {
    flex: 1;
}

.alert-close {
    flex-shrink: 0;
    padding: 4px;
    border-radius: 4px;
    background: none;
    border: none;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.15s ease;
}

.alert-close:hover {
    opacity: 1;
}

/* Transitions */
.alert-enter-active,
.alert-leave-active {
    transition: all 0.3s ease;
}

.alert-enter-from,
.alert-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
