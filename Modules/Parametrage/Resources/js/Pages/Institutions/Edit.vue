<script setup>
import { ref } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import InstitutionForm from './InstitutionForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };

const props = defineProps({
    title: String,
    institution: { type: Object, required: true },
    paysList: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    departements: { type: Array, default: () => [] },
    communes: { type: Array, default: () => [] },
    quartiers: { type: Array, default: () => [] },
    devises: { type: Array, default: () => [] },
    directeurs: { type: Array, default: () => [] },
});

const i = props.institution || {};

const form = useForm({
    nom: i.nom || '',
    sigle: i.sigle || '',
    devise_slogan: i.devise_slogan || '',
    devise_comptabilite_id: i.devise_comptabilite_id || null,
    logo: null,
    adresse_siege: i.adresse_siege || '',
    code_postal: i.code_postal || '',
    boite_postale: i.boite_postale || '',
    ville: i.ville || '',
    quartier_id: i.quartier_id || null,
    commune_id: i.commune_id || null,
    departement_id: i.departement_id || null,
    region_id: i.region_id || null,
    pays_id: i.pays_id || null,
    date_creation: i.date_creation || '',
    numero_autorisation: i.numero_autorisation || '',
    numero_agrement_2: i.numero_agrement_2 || '',
    numero_agrement_3: i.numero_agrement_3 || '',
    numero_agrement_4: i.numero_agrement_4 || '',
    ministere_tutelle_1: i.ministere_tutelle_1 || '',
    ministere_tutelle_2: i.ministere_tutelle_2 || '',
    ministere_tutelle_3: i.ministere_tutelle_3 || '',
    ministere_tutelle_4: i.ministere_tutelle_4 || '',
    promoteur: i.promoteur || '',
    gerant: i.gerant || '',
    email_principal: i.email_principal || '',
    telephone_principal: i.telephone_principal || '',
    site_web: i.site_web || '',
    telephone_1: i.telephone_1 || '',
    telephone_2: i.telephone_2 || '',
    telephone_3: i.telephone_3 || '',
    whatsapp_1: i.whatsapp_1 || '',
    whatsapp_2: i.whatsapp_2 || '',
    fax: i.fax || '',
    email_1: i.email_1 || '',
    email_2: i.email_2 || '',
    facebook: i.facebook || '',
    linkedin: i.linkedin || '',
    twitter: i.twitter || '',
    description: i.description || '',
    vision: i.vision || '',
    mission: i.mission || '',
    statut: i.statut || 'actif',
    _method: 'PUT', // pour passer le multipart en POST
});

const submitForm = () => {
    showUpdateLoader();
    // POST + _method=PUT pour supporter l'upload logo
    form.post(route('parametrage.institution.update', i.id), {
        forceFormData: true,
        onError: (errors) => {
            hideLoader();
            console.error('[Institution Edit] Erreurs:', errors);
            const firstErrorField = Object.keys(errors)[0];
            if (firstErrorField) {
                setTimeout(() => {
                    const el = document.querySelector(`[name="${firstErrorField}"]`);
                    el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        },
        onSuccess: () => {
            setTimeout(() => hideLoader(), 500);
        },
    });
};

const errorList = () => Object.entries(form.errors || {}).map(([field, msg]) => ({ field, msg }));
</script>

<template>
    <Head :title="title || (t('modules.parametrage.institutions.edit') || 'Modifier Institution')" />
    <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.parametrage.institutions.edit') || 'Modifier Institution' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />

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
                                    mode="edit"
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
