<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import TraductionForm from './TraductionForm.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';

defineOptions({
    layout: DashboardLayout,
});

const { t } = useI18n();
const { showStoreLoader, hideLoader } = useLoader();

const formRef = ref(null);
const isSubmitting = ref(false);
const showCollapse = ref(true);

const form = ref({
    code_fr: '',
    intitule_fr: '',
    code_en: '',
    intitule_en: '',
    groupe: '',
    etat: 'actif',
    errors: {},
});

form.value.clearErrors = (field) => {
    if (form.value.errors && form.value.errors[field]) {
        delete form.value.errors[field];
    }
};

function submitForm(formData) {
    isSubmitting.value = true;
    router.post(route('traductions.store'), form.value, {
        onSuccess: () => {
            isSubmitting.value = false;
        },
        onError: (errors) => {
            form.value.errors = errors;
            isSubmitting.value = false;
        },
    });
}

const handleSubmit = () => {
    submitForm(form.value);
};
</script>

<template>
    <Head :title="t('modules.communication.traductions.create') || 'Créer une traduction'" />

    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('modules.communication.traductions.create') || 'Créer une traduction' }}</h4>
            <div class="header-actions">
                <button
                    @click="showCollapse = !showCollapse"
                    class="btn btn-secondary btn-sm"
                    type="button"
                >
                    <i :class="['fa', showCollapse ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                    {{ showCollapse ? t('common.collapse') : t('common.expand') }}
                </button>
            </div>
        </div>

        <AlertMessage />

        <div v-if="showCollapse" class="dash-payment-item-wrapper">
            <TraductionForm
                ref="formRef"
                :form="form"
                mode="create"
                @submit="handleSubmit"
            />

            <div class="form-actions mt-4">
                <Link
                    :href="route('traductions.index')"
                    class="btn btn-secondary btn-lg"
                >
                    <i class="fa fa-times"></i> {{ t('actions.cancel') || 'Annuler' }}
                </Link>
                <button
                    @click="handleSubmit"
                    class="btn btn-primary btn-lg"
                    :disabled="isSubmitting"
                >
                    <i class="fa fa-save"></i> {{ t('actions.create') || 'Créer' }}
                </button>
            </div>
        </div>

        <FullPageLoader :show="isSubmitting" :message="t('common.saving') || 'Enregistrement en cours...'" />
    </div>
</template>

<style scoped>
.body-wrapper {
    padding: 2rem;
}

.dashboard-header-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.dash-payment-item-wrapper {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 2rem;
    animation: slideDown 0.3s ease-in-out;
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

.btn-sm {
    padding: 0.375rem 0.875rem;
    font-size: 0.8125rem;
}

.btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1rem;
}

.mt-4 {
    margin-top: 1.5rem;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .body-wrapper {
        padding: 1rem;
    }

    .dashboard-header-wrapper {
        flex-direction: column;
        align-items: flex-start;
    }

    .dash-payment-item-wrapper {
        padding: 1.5rem;
    }
}
</style>
