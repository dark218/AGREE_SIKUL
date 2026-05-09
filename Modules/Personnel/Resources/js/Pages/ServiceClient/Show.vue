<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PersonnelForm from '../../Components/PersonnelForm.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import RejectModal from '@/Components/Common/RejectModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showValidateLoader, showRejectLoader, hideLoader } = useLoader();
const props = defineProps({
    serviceClient: Object,
    payss: Array,
    typePieces: Array,
    kycStatuts: Object,
    userStatuts: Object,
});
const showKycValidateModal = ref(false);
const showKycRejectModal = ref(false);
const showStatutValidateModal = ref(false);
const showStatutRejectModal = ref(false);
const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value;
};
const openKycValidateModal = () => { showKycValidateModal.value = true; };
const openKycRejectModal = () => { showKycRejectModal.value = true; };
const openStatutValidateModal = () => { showStatutValidateModal.value = true; };
const openStatutRejectModal = () => { showStatutRejectModal.value = true; };
const validerKyc = () => {
    showValidateLoader();
    router.post(route('service_client.kyc.validation', [props.serviceClient.uuid, 'valider']), {}, {
        preserveScroll: true,
        onSuccess: () => { showKycValidateModal.value = false; },
        onFinish: () => { hideLoader(); },
    });
};
const rejeterKyc = (motif) => {
    showRejectLoader();
    router.post(route('service_client.kyc.validation', [props.serviceClient.uuid, 'rejeter']), { motif }, {
        preserveScroll: true,
        onSuccess: () => { showKycRejectModal.value = false; },
        onFinish: () => { hideLoader(); },
    });
};
const validerStatut = () => {
    showValidateLoader();
    router.post(route('service_client.validation', [props.serviceClient.uuid, 'valider']), {}, {
        preserveScroll: true,
        onSuccess: () => { showStatutValidateModal.value = false; },
        onFinish: () => { hideLoader(); },
    });
};
const rejeterStatut = (motif) => {
    showRejectLoader();
    router.post(route('service_client.validation', [props.serviceClient.uuid, 'rejeter']), { motif }, {
        preserveScroll: true,
        onSuccess: () => { showStatutRejectModal.value = false; },
        onFinish: () => { hideLoader(); },
    });
};
const canValidateKyc = () => {
    const status = props.serviceClient?.kyc_status;
    return status === 'non_verifie' || status === 'en_attente' || status === 'rejete';
};
const canValidateStatut = () => {
    const status = props.serviceClient?.statut;
    return status === 'non_actif' || status === 'suspendu' || status === 'bloque';
};
const getKycBadgeClass = (status) => {
    const classes = { 'non_verifie': 'bg-warning', 'en_attente': 'bg-info', 'verifie': 'bg-success', 'rejete': 'bg-danger' };
    return classes[status] || 'bg-secondary';
};
const getKycLabel = (status) => {
    const labels = { 'non_verifie': t('kyc.non_verifie'), 'en_attente': t('kyc.en_attente'), 'verifie': t('kyc.verifie'), 'rejete': t('kyc.rejete') };
    return labels[status] || status;
};
const getStatutBadgeClass = (status) => {
    const classes = { 'non_actif': 'bg-secondary', 'actif': 'bg-success', 'suspendu': 'bg-warning', 'bloque': 'bg-danger', 'supprime': 'bg-secondary' };
    return classes[status] || 'bg-secondary';
};
const getStatutLabel = (status) => {
    const labels = { 'non_actif': t('statuts.inactif'), 'actif': t('statuts.actif'), 'suspendu': t('statuts.suspendu'), 'bloque': t('statuts.bloque'), 'supprime': t('statuts.supprime') };
    return labels[status] || status;
};
</script>
<template>
    <Head :title="t('modules.personnel.service_client.show')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-eye"></i></span>
                                <h5 class="title mb-0">{{ t('modules.personnel.service_client.show') }}</h5>
                            </div>
                            <div class="d-flex flex-grow-1 align-items-center">
                                <!-- Boutons KYC et Statut au centre -->
                                <div class="d-flex align-items-center gap-2 mx-auto" @click.stop>
                                    <button v-if="canValidateKyc()" type="button" class="btn btn-success btn-sm" @click="openKycValidateModal">
                                        <i class="fa fa-check me-1"></i> <i class="fa fa-save"></i> {{ t('actions.validate') }} KYC
                                    </button>
                                    <button v-if="canValidateKyc()" type="button" class="btn btn-warning btn-sm" @click="openKycRejectModal">
                                        <i class="fa fa-times me-1"></i> {{ t('actions.reject') }} KYC
                                    </button>
                                </div>
                                <!-- KYC et Statut à droite -->
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-1">
                                        <small class="text-muted">KYC:</small>
                                        <span :class="['badge', getKycBadgeClass(serviceClient?.kyc_status)]">{{ getKycLabel(serviceClient?.kyc_status) }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <small class="text-muted">{{ t('common.status') }}:</small>
                                        <span :class="['badge', getStatutBadgeClass(serviceClient?.statut)]">{{ getStatutLabel(serviceClient?.statut) }}</span>
                                    </div>
                                </div>
                                <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                            </div>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <div class="col-xl-12 col-lg-6 mb-20">
                                <AlertMessage />
                                <PersonnelForm :user="serviceClient" :payss="payss" :type-pieces="typePieces" :show-pays-field="true" :is-read-only="true" />
                                <div class="row mt-4">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('service_client.index')" class="btn btn-danger"><i class="fa fa-arrow-left"></i> {{ t('actions.back') }}</Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de validation KYC -->
        <ConfirmModal :show="showKycValidateModal" :title="t('actions.validate') + ' KYC'" :message="t('messages.confirm.validate.message')" @close="showKycValidateModal = false" @confirm="validerKyc" />
        <!-- Modal de rejet KYC -->
        <RejectModal :show="showKycRejectModal" :title="t('actions.reject') + ' KYC'" :message="t('messages.confirm.reject.message')" @close="showKycRejectModal = false" @confirm="rejeterKyc" />
        <!-- Modal de validation Statut -->
        <ConfirmModal :show="showStatutValidateModal" :title="t('actions.activate')" :message="t('messages.confirm.activate.message')" @close="showStatutValidateModal = false" @confirm="validerStatut" />
        <!-- Modal de rejet Statut -->
        <RejectModal :show="showStatutRejectModal" :title="t('actions.reject')" :message="t('messages.confirm.reject.message')" @close="showStatutRejectModal = false" @confirm="rejeterStatut" />
        <!-- Loader pleine page -->
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
