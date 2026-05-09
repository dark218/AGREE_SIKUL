<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MessageForm from './MessageForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    item: Object,
    users: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    destinataires_ids: props.item?.destinataires_ids || [],
    objet: props.item?.objet || '',
    contenu: props.item?.contenu || '',
    etat: props.item?.etat || 'actif',
});

const submitForm = () => {
    showStoreLoader();
    form.put(route('messages.update', props.item.id), {
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: () => {
            hideLoader();
        }
    });
};
</script>

<template>
    <Head :title="t('common.edit')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.communication.messages.edit') || 'Modifier le message' }}</h5>
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
                                    mode="edit"
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
