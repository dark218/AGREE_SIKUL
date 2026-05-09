<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import EcoleForm from './EcoleForm.vue';
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
    campuses: {
        type: Array,
        default: () => [],
    },
    typeEtablissements: {
        type: Array,
        default: () => [],
    },
    directeurs: {
        type: Array,
        default: () => [],
    },
    paysList: {
        type: Array,
        default: () => [],
    },
    typeCours: {
        type: Array,
        default: () => [],
    },
    institutions: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    code: '',
    nom: '',
    campus_id: null,
    type_enseignement: null,
    directeur_id: null,
    capacite_totale: null,
    statut: 'actif',
    // Adresse et localisation
    adresse_siege: '',
    code_postal: '',
    boite_postale: '',
    ville: '',
    quartier: '',
    commune: '',
    departement: '',
    region: '',
    pays_id: null,
    // Contacts - Téléphones
    telephone_principal: '',
    telephone_2: '',
    telephone_3: '',
    // Contacts - WhatsApp
    whatsapp_1: '',
    whatsapp_2: '',
    // Contacts - Autres
    fax: '',
    email_principal: '',
    email_1: '',
    site_web: '',
    facebook: '',
    linkedin: '',
    twitter: '',
    // Description
    description: '',
    vision: '',
    mission: '',
    // Dirigeants
    dirigeants: [],
});
const submitForm = () => {
    console.log('📝 Ecole Create - Form data:', form.data());
    console.log('🔗 Route:', route('parametrage.ecoles.store'));
    showStoreLoader();
    form.post(route('parametrage.ecoles.store'), {
        onSuccess: () => {
            console.log('✅ Ecole créée avec succès!');
            // Attendre que la redirection et la nouvelle page se chargent complètement
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: (errors) => {
            console.error('❌ Erreur création Ecole:', errors);
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
                                <h5 class="title mb-0">{{ t('common.add_new') }} - {{ t('common.ecole') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <EcoleForm
                                    :form="form"
                                    :campuses="campuses"
                                    :typeEtablissements="typeEtablissements"
                                    :directeurs="directeurs"
                                    :paysList="paysList"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.ecoles.index')" class="btn btn-danger">
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
