<script setup>
import { ref, watch } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MessageForm from './MessageForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
});

const isCollapsed = ref(false);
const debugBox = ref(null);
const showDebug = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const form = useForm({
    destinataires_ids: [],
    objet: '',
    contenu: '',
});

// Watch for form errors
watch(() => form.errors, (newErrors) => {
    if (Object.keys(newErrors).length > 0) {
        debugBox.value = {
            type: 'error',
            message: 'Erreur de validation',
            errors: newErrors
        };
        showDebug.value = true;
        console.error('Form errors:', newErrors);
    }
}, { deep: true });

const submitForm = () => {
    debugBox.value = {
        type: 'info',
        message: 'Envoi du formulaire...',
        data: {
            destinataires_ids: form.destinataires_ids,
            objet: form.objet,
            contenu: form.contenu
        }
    };
    showDebug.value = true;
    console.log('Form data being sent:', form.data());

    showStoreLoader();
    form.post(route('messages.store'), {
        onSuccess: (response) => {
            debugBox.value = {
                type: 'success',
                message: 'Message envoyé avec succès!',
                response: response
            };
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: (errors) => {
            console.error('Form submission error:', errors);
            debugBox.value = {
                type: 'error',
                message: 'Erreur lors de l\'envoi',
                errors: errors,
                formErrors: form.errors
            };
            showDebug.value = true;
            hideLoader();
        }
    });
};
</script>

<template>
    <div class="body-wrapper">
        <!-- Debug Box -->
        <div v-if="showDebug && debugBox" class="debug-box" :class="`debug-${debugBox.type}`">
            <div class="debug-header">
                <h4>🔍 DEBUG: {{ debugBox.message }}</h4>
                <button type="button" @click="showDebug = false" class="close-btn">✕</button>
            </div>
            <div class="debug-content">
                <pre v-if="debugBox.errors">{{ JSON.stringify(debugBox.errors, null, 2) }}</pre>
                <pre v-if="debugBox.data">{{ JSON.stringify(debugBox.data, null, 2) }}</pre>
                <pre v-if="debugBox.formErrors">{{ JSON.stringify(debugBox.formErrors, null, 2) }}</pre>
            </div>
        </div>

        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.communication.messages.create') || 'Créer un message' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <MessageForm
                                    :form="form"
                                    :users="users"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="form-actions mt-4">
                                    <Link :href="route('messages.index')" class="btn btn-secondary btn-lg">
                                        <i class="fa fa-times"></i> {{ t('actions.cancel') || 'Annuler' }}
                                    </Link>
                                    <button
                                        type="submit"
                                        class="btn btn-primary btn-lg"
                                        :disabled="form.processing"
                                    >
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                        <i class="fa fa-save"></i> {{ t('actions.validate') || 'Valider' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>

<style scoped>
/* Debug Box */
.debug-box {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 400px;
    max-height: 400px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

.debug-box.debug-error {
    background: #fee;
    border: 2px solid #f33;
}

.debug-box.debug-info {
    background: #efe;
    border: 2px solid #3f3;
}

.debug-box.debug-success {
    background: #efe;
    border: 2px solid #0a0;
}

.debug-header {
    padding: 10px 15px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.debug-header h4 {
    margin: 0;
    font-size: 13px;
}

.close-btn {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
}

.debug-content {
    padding: 10px 15px;
    background: rgba(0, 0, 0, 0.05);
    max-height: 350px;
    overflow-y: auto;
}

.debug-content pre {
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
}

.form-actions {
    display: flex !important;
    gap: 1rem;
    justify-content: flex-end !important;
    padding-top: 2rem;
    border-top: 1px solid #e9ecef;
    width: 100%;
}

.btn {
    padding: 0.625rem 1.5rem;
    border-radius: 0.375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-primary {
    background-color: #0B5697;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: #084385;
}

.btn-primary:disabled {
    background-color: #0B5697;
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background-color: #4b5563;
}

.btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1rem;
}

.mt-4 {
    margin-top: 1.5rem;
}
</style>
