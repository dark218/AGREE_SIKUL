<script setup>
import { ref } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FournisseurPaiementForm from './FournisseurPaiementForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    fournisseur: Object,
    paysdevises: Array,
    types: Object,
    paysCurrent: [Number, String, null],
});
const form = useForm({
    pays_devise_id: props.fournisseur?.pays_devise_id || '',
    libelle: props.fournisseur?.nom || '',
    code: props.fournisseur?.code || '',
    type: props.fournisseur?.type || '',
    statut: props.fournisseur?.statut || 'actif',
    config: props.fournisseur?.config || '',
    metadata: props.fournisseur?.metadata || '',
    logo: null,
});
const logoPreview = ref(props.fournisseur?.logo || 'https://caer.univ-amu.fr/wp-content/uploads/default-placeholder.png');
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.logo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};
const submitForm = () => {
    showUpdateLoader();
    form.put(route('parametrage.fournisseurs_paiement.update', props.fournisseur?.id), {
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
                                <h5 class="title mb-0">{{ t('modules.parametrage.paymentProviders.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <FournisseurPaiementForm
                                    :form="form"
                                    :paysdevises="paysdevises"
                                    :types="types"
                                    :paysCurrent="paysCurrent"
                                    :logoPreview="logoPreview"
                                    mode="edit"
                                    @logo-change="handleLogoChange"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.fournisseurs_paiement.index')" class="btn btn-danger">
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
