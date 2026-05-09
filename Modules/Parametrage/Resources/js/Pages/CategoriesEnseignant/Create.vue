<script setup>
import { ref } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import CategoriesEnseignantForm from './CategoriesEnseignantForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
defineProps({
    ecoles: {
        type: Array,
        default: () => [],
    },
    pays: {
        type: Array,
        default: () => [],
    },
});
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const form = useForm({
    code: '',
    libelle: '',
    ecole_id: null,
    etat: 'actif',
    niveau_qualification: '',
    charge_horaire_min: null,
    charge_horaire_max: null,
    taux_horaire_base: null,
    peut_etre_titulaire: false,
    anciennete_requise: null,
});
const submitForm = () => {
    showStoreLoader();
    form.post(route('parametrage.categories_enseignant.store'), {
        onError: (errors) => {
            console.error('Form validation errors:', errors);
            hideLoader();
            // Log each error for debugging
            Object.entries(errors).forEach(([field, message]) => {
                console.error(`[${field}] ${message}`);
            });
        },
        onSuccess: () => {
            console.log('Form submitted successfully');
        },
        onFinish: () => {
            hideLoader();
        }
    });
};
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('actions.create') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <!-- Show validation errors if any -->
                            <div v-if="Object.keys(form.errors).length > 0" class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>⚠️ Erreurs de validation:</strong>
                                <ul class="mb-0 mt-2">
                                    <li v-for="(error, field) in form.errors" :key="field">
                                        <strong>[{{ field }}]:</strong> {{ error }}
                                    </li>
                                </ul>
                                <button type="button" class="btn-close" @click="Object.assign(form.errors, {})"></button>
                            </div>
                            <form @submit.prevent="submitForm">
                                <CategoriesEnseignantForm :form="form" :ecoles="page.props.ecoles" :pays="page.props.pays" mode="create" />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.categories_enseignant.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i> {{ t('actions.validate') }}
                                            </button>
                                        </div>
                                    </div>
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
