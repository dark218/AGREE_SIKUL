<script setup>
import { ref } from 'vue';
import { useForm, Link, usePage, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import InstitutionForm from './InstitutionForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };

defineProps({
    paysList: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    departements: { type: Array, default: () => [] },
    communes: { type: Array, default: () => [] },
    quartiers: { type: Array, default: () => [] },
    devises: { type: Array, default: () => [] },
    directeurs: { type: Array, default: () => [] },
});

const form = useForm({
    nom: '',
    sigle: '',
    devise_slogan: '',
    devise_comptabilite_id: null,
    logo: null,
    // Adresse legacy + FK
    adresse_siege: '',
    code_postal: '',
    boite_postale: '',
    ville: '',
    quartier_id: null,
    commune_id: null,
    departement_id: null,
    region_id: null,
    pays_id: null,
    // Création / agrément
    date_creation: '',
    numero_autorisation: '',
    numero_agrement_2: '',
    numero_agrement_3: '',
    numero_agrement_4: '',
    ministere_tutelle_1: '',
    ministere_tutelle_2: '',
    ministere_tutelle_3: '',
    ministere_tutelle_4: '',
    // Dirigeants
    promoteur: '',
    gerant: '',
    // Contacts
    email_principal: '',
    telephone_principal: '',
    site_web: '',
    telephone_1: '',
    telephone_2: '',
    telephone_3: '',
    whatsapp_1: '',
    whatsapp_2: '',
    fax: '',
    email_1: '',
    email_2: '',
    facebook: '',
    linkedin: '',
    twitter: '',
    // Description
    description: '',
    vision: '',
    mission: '',
    statut: 'actif',
});

const submitForm = () => {
    showStoreLoader();
    form.post(route('parametrage.institution.store'), {
        forceFormData: true, // pour le fichier logo
        onSuccess: () => {
            setTimeout(() => hideLoader(), 500);
        },
        onError: (errors) => {
            hideLoader();
            console.error('[Institution Create] Erreurs de validation:', errors);
            // Scroll au premier champ en erreur
            const firstErrorField = Object.keys(errors)[0];
            if (firstErrorField) {
                setTimeout(() => {
                    const el = document.querySelector(`[name="${firstErrorField}"], [v-model*="${firstErrorField}"]`);
                    el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        },
    });
};

const errorList = () => Object.entries(form.errors || {}).map(([field, msg]) => ({ field, msg }));
</script>

<template>
    <Head :title="t('common.add_institution') || 'Ajouter Institution'" />
    <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('common.add_institution') || 'Ajouter Institution' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />

                            <!-- Récap erreurs de validation -->
                            <div v-if="Object.keys(form.errors || {}).length > 0" class="alert alert-danger mb-3">
                                <strong>{{ t('validation.error_title') || 'Erreurs de validation' }}</strong>
                                <ul class="mb-0 mt-2">
                                    <li v-for="e in errorList()" :key="e.field">
                                        <strong>{{ e.field }}</strong> : {{ e.msg }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Bouton "Valider" géré par le FormStepper (dernière étape). -->
                            <div>
                                <InstitutionForm
                                    :form="form"
                                    mode="create"
                                    :pays-list="paysList"
                                    :regions="regions"
                                    :departements="departements"
                                    :communes="communes"
                                    :quartiers="quartiers"
                                    :devises="devises"
                                    :directeurs="directeurs"
                                    @submit="submitForm"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-start">
                                            <Link :href="route('parametrage.institution.index')" class="btn btn-outline-secondary">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
