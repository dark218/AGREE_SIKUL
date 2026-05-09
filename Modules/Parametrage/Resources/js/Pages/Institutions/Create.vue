<script setup>
import { ref } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import InstitutionForm from './InstitutionForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
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
    code: '',
    nom: '',
    sigle: '',
    type: '',
    statut_juridique: '',
    numero_autorisation: '',
    date_creation: '',
    directeur_general_id: '',
    email_principal: '',
    telephone_principal: '',
    site_web: '',
    adresse_siege: '',
    code_postal: '',
    boite_postale: '',
    quartier: '',
    commune: '',
    ville: '',
    departement: '',
    region: '',
    pays_id: '',
    devise_principale: '',
    ministere_tutelle_1: '',
    ministere_tutelle_2: '',
    ministere_tutelle_3: '',
    ministere_tutelle_4: '',
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
    fuseau_horaire: '',
    langue_principale: '',
    description: '',
    vision: '',
    mission: '',
    statut: 'actif',
});
const submitForm = () => {
    showStoreLoader();
    form.post(route('parametrage.institution.store'), {
        onSuccess: () => {
            // Attendre que la redirection et la nouvelle page se chargent complètement
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
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('common.add_institution') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <InstitutionForm
                                    :form="form"
                                    mode="create"
                                    :pays-list="paysList"
                                    :directeurs="directeurs"
                                />
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
