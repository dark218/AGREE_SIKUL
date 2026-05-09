<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MoyenPaiementForm from '../../Components/MoyenPaiementForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    client: Object,
    types: Array,
    fournisseurs: Array,
});
const form = useForm({
    type: '',
    fournisseur_id: '',
    label: '',
    identifiant: '',
    token_provider: '',
    is_defaut: false,
    metadata: null,
});
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const submitForm = () => {
    showStoreLoader();
    form.post(route('client.moyens-paiement.store', props.client.uuid), {
        onFinish: () => {
            hideLoader();
        },
    });
};
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <Link :href="route('client.index')" class="text-decoration-none">
                                {{ t('modules.service_client.client.title') }}
                            </Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('client.show', client.uuid)" class="text-decoration-none">
                                {{ client.nom }} {{ client.prenoms }}
                            </Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('client.moyens-paiement', client.uuid)" class="text-decoration-none">
                                {{ t('modules.service_client.moyen_paiement.title') }}
                            </Link>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ t('actions.create') }}</li>
                    </ol>
                </nav>
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center"
                             @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.service_client.moyen_paiement.add') }}</h5>
                            </div>
                            <button type="button"
                                    class="collapse-toggle"
                                    :class="{ collapsed: isCollapsed }"
                                    @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <MoyenPaiementForm
                                    :form="form"
                                    :types="types"
                                    :fournisseurs="fournisseurs"
                                    mode="create" />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('client.moyens-paiement', client.uuid)"
                                                  class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button type="submit"
                                                    class="btn btn-primary"
                                                    :disabled="form.processing">
                                                <span v-if="form.processing"
                                                      class="spinner-border spinner-border-sm me-2"></span>
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
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
<style scoped>
.breadcrumb-item a:hover,
.breadcrumb-item a:active,
.breadcrumb-item a:focus {
    color: #0a58ca !important;
    text-decoration: underline;
}
</style>
