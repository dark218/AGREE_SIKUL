<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ParentForm from './ParentForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

// Debug logging
console.log('Parent Create component loading...');

onMounted(() => {
    console.log('✓ Parent Create component mounted successfully');
});

const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    apprenants: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    institutions: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    apprenant_id: '',
    classe_id: '',
    ecole_id: '',
    institution_id: '',
    campus_id: '',
    pere_nom: '',
    pere_prenoms: '',
    pere_nom_complet: '',
    pere_profession: '',
    pere_organisation_travail: '',
    pere_ville_travail: '',
    pere_pays_travail: '',
    pere_adresse_residence: '',
    pere_quartier: '',
    pere_commune: '',
    pere_departement: '',
    pere_region: '',
    pere_code_postal: '',
    pere_boite_postal: '',
    pere_telephone_1: '',
    pere_telephone_2: '',
    pere_whatsapp_1: '',
    pere_whatsapp_2: '',
    pere_email_1: '',
    pere_email_2: '',
    mere_nom: '',
    mere_prenoms: '',
    mere_nom_complet: '',
    mere_profession: '',
    mere_organisation_travail: '',
    mere_ville_travail: '',
    mere_pays_travail: '',
    mere_adresse_residence: '',
    mere_quartier: '',
    mere_commune: '',
    mere_departement: '',
    mere_region: '',
    mere_code_postal: '',
    mere_boite_postal: '',
    mere_telephone_1: '',
    mere_telephone_2: '',
    mere_whatsapp_1: '',
    mere_whatsapp_2: '',
    mere_email_1: '',
    mere_email_2: '',
    tuteur1_nom: '',
    tuteur1_prenoms: '',
    tuteur1_nom_complet: '',
    tuteur1_profession: '',
    tuteur1_organisation_travail: '',
    tuteur1_ville_travail: '',
    tuteur1_pays_travail: '',
    tuteur1_adresse_residence: '',
    tuteur1_quartier: '',
    tuteur1_commune: '',
    tuteur1_arrondissement: '',
    tuteur1_ville: '',
    tuteur1_departement: '',
    tuteur1_region: '',
    tuteur1_pays: '',
    tuteur1_code_postal: '',
    tuteur1_boite_postal: '',
    tuteur1_telephone_1: '',
    tuteur1_telephone_2: '',
    tuteur1_email: '',
    tuteur1_whatsapp_1: '',
    tuteur1_whatsapp_2: '',
    tuteur2_nom: '',
    tuteur2_prenoms: '',
    tuteur2_nom_complet: '',
    tuteur2_profession: '',
    tuteur2_organisation_travail: '',
    tuteur2_ville_travail: '',
    tuteur2_pays_travail: '',
    tuteur2_adresse_residence: '',
    tuteur2_quartier: '',
    tuteur2_commune: '',
    tuteur2_arrondissement: '',
    tuteur2_ville: '',
    tuteur2_departement: '',
    tuteur2_region: '',
    tuteur2_pays: '',
    tuteur2_code_postal: '',
    tuteur2_boite_postal: '',
    tuteur2_telephone_1: '',
    tuteur2_telephone_2: '',
    tuteur2_email: '',
    tuteur2_whatsapp_1: '',
    tuteur2_whatsapp_2: '',
    etat: 'actif',
});

const submitForm = () => {
    console.log('📋 Form submission initiated');
    console.log('Form data:', form.data());
    console.log('Route URL:', route('parents.store'));

    showStoreLoader();
    form.post(route('parents.store'), {
        onError: (errors) => {
            console.error('❌ Form validation errors:', errors);
            hideLoader();
        },
        onSuccess: (response) => {
            console.log('✓ Form submitted successfully', response);
            hideLoader();
        },
        onFinish: () => {
            console.log('Form submission finished');
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
                                <span class="dash-payment-badge">
                                    <i class="fa fa-plus"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('modules.personnel.parents.create') || 'Créer un parent' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <ParentForm
                                    :form="form"
                                    :apprenants="apprenants"
                                    :classes="classes"
                                    :ecoles="ecoles"
                                    :institutions="institutions"
                                    :campuses="campuses"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parents.index')" class="btn btn-danger">
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
