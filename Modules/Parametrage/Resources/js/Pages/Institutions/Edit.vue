<script setup>
import { ref } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import InstitutionForm from './InstitutionForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    institution: Object,
    paysList: {
        type: Array,
        default: () => [],
    },
    directeurs: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    code: props.institution?.code || '',
    nom: props.institution?.nom || '',
    sigle: props.institution?.sigle || '',
    type: props.institution?.type || '',
    statut_juridique: props.institution?.statut_juridique || '',
    numero_autorisation: props.institution?.numero_autorisation || '',
    date_creation: props.institution?.date_creation || '',
    directeur_general_id: props.institution?.directeur_general_id || null,
    email_principal: props.institution?.email_principal || '',
    telephone_principal: props.institution?.telephone_principal || '',
    site_web: props.institution?.site_web || '',
    adresse_siege: props.institution?.adresse_siege || '',
    code_postal: props.institution?.code_postal || '',
    boite_postale: props.institution?.boite_postale || '',
    quartier: props.institution?.quartier || '',
    commune: props.institution?.commune || '',
    ville: props.institution?.ville || '',
    departement: props.institution?.departement || '',
    region: props.institution?.region || '',
    pays_id: props.institution?.pays_id || null,
    devise_principale: props.institution?.devise_principale || '',
    ministere_tutelle_1: props.institution?.ministere_tutelle_1 || '',
    ministere_tutelle_2: props.institution?.ministere_tutelle_2 || '',
    ministere_tutelle_3: props.institution?.ministere_tutelle_3 || '',
    ministere_tutelle_4: props.institution?.ministere_tutelle_4 || '',
    telephone_1: props.institution?.telephone_1 || '',
    telephone_2: props.institution?.telephone_2 || '',
    telephone_3: props.institution?.telephone_3 || '',
    whatsapp_1: props.institution?.whatsapp_1 || '',
    whatsapp_2: props.institution?.whatsapp_2 || '',
    fax: props.institution?.fax || '',
    email_1: props.institution?.email_1 || '',
    email_2: props.institution?.email_2 || '',
    facebook: props.institution?.facebook || '',
    linkedin: props.institution?.linkedin || '',
    twitter: props.institution?.twitter || '',
    fuseau_horaire: props.institution?.fuseau_horaire || '',
    langue_principale: props.institution?.langue_principale || 'fr',
    description: props.institution?.description || '',
    vision: props.institution?.vision || '',
    mission: props.institution?.mission || '',
    statut: props.institution?.statut || 'actif',
});
const submitForm = () => {
    showUpdateLoader();
    form.put(route('parametrage.institution.update', props.institution?.id), {
        onError: (errors) => {
            console.error('Form validation errors:', errors);
        },
        onSuccess: () => {
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
                                <h5 class="title mb-0">{{ t('modules.parametrage.institutions.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <InstitutionForm :form="form" mode="edit" :paysList="paysList" :directeurs="directeurs" />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.institution.index')" class="btn btn-danger">
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
