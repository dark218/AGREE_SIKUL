<script setup>
import { ref } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import EcoleForm from './EcoleForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };

const props = defineProps({
    title: String,
    ecole: { type: Object, required: true },
    campuses: { type: Array, default: () => [] },
    institutions: { type: Array, default: () => [] },
    typeEtablissements: { type: Array, default: () => [] },
    typeEnseignements: { type: Array, default: () => [] },
    typeCours: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    directeurs: { type: Array, default: () => [] },
    devises: { type: Array, default: () => [] },
    paysList: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    departements: { type: Array, default: () => [] },
    communes: { type: Array, default: () => [] },
    quartiers: { type: Array, default: () => [] },
});

const e = props.ecole || {};

const form = useForm({
    campus_id: e.campus_id || null,
    institution_id: e.institution_id || null,
    code: e.code || '',
    nom: e.nom || '',
    sigle: e.sigle || '',
    devise_slogan: e.devise_slogan || '',
    type_etablissement_id: e.type_etablissement_id || null,
    type_enseignement_id: e.type_enseignement_id || null,
    type_cours_id: e.type_cours_id || null,
    capacite_maximale: e.capacite_maximale || null,
    directeur_id: e.directeur_id || null,
    statut: e.statut || 'actif',
    adresse_siege: e.adresse_siege || '',
    code_postal: e.code_postal || '',
    boite_postale: e.boite_postale || '',
    ville: e.ville || '',
    quartier_id: e.quartier_id || null,
    commune_id: e.commune_id || null,
    departement_id: e.departement_id || null,
    region_id: e.region_id || null,
    pays_id: e.pays_id || null,
    date_creation: e.date_creation || '',
    numero_agrement: e.numero_agrement || '',
    ministere_tutelle: e.ministere_tutelle || '',
    section_id: e.section_id || null,
    devise_comptabilite_id: e.devise_comptabilite_id || null,
    logo: null,
    telephone_principal: e.telephone_principal || '',
    telephone_2: e.telephone_2 || '',
    telephone_3: e.telephone_3 || '',
    whatsapp_1: e.whatsapp_1 || '',
    whatsapp_2: e.whatsapp_2 || '',
    fax: e.fax || '',
    email_principal: e.email_principal || '',
    email_1: e.email_1 || '',
    site_web: e.site_web || '',
    facebook: e.facebook || '',
    linkedin: e.linkedin || '',
    twitter: e.twitter || '',
    description: e.description || '',
    vision: e.vision || '',
    mission: e.mission || '',
    dirigeants: e.dirigeants || [],
    _method: 'PUT',
});

const submitForm = () => {
    showUpdateLoader();
    form.post(route('parametrage.ecoles.update', e.id), {
        forceFormData: true,
        onError: (errors) => {
            hideLoader();
            console.error('[Ecole Edit] Erreurs:', errors);
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
    <Head :title="title || (t('actions.edit') + ' - ' + (e.nom || 'École'))" />
    <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('actions.edit') || 'Modifier' }} - {{ e.nom }}</h5>
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
                                    <li v-for="er in errorList()" :key="er.field">
                                        <strong>{{ er.field }}</strong> : {{ er.msg }}
                                    </li>
                                </ul>
                            </div>

                            <form @submit.prevent="submitForm">
                                <EcoleForm
                                    :form="form"
                                    mode="edit"
                                    :campuses="campuses"
                                    :institutions="institutions"
                                    :type-etablissements="typeEtablissements"
                                    :type-enseignements="typeEnseignements"
                                    :type-cours="typeCours"
                                    :sections="sections"
                                    :directeurs="directeurs"
                                    :devises="devises"
                                    :pays-list="paysList"
                                    :regions="regions"
                                    :departements="departements"
                                    :communes="communes"
                                    :quartiers="quartiers"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.ecoles.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                            </Link>
                                            <button type="submit" class="btn btn-primary ms-2" :disabled="form.processing">
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i>
                                                {{ form.processing ? (t('actions.saving') || 'Enregistrement...') : (t('actions.validate') || 'Valider') }}
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
    </div>
</template>
