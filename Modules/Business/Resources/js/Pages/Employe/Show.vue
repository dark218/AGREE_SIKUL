<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import RejectModal from '@/Components/Common/RejectModal.vue';
import EmployeForm from './EmployeForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showValidateLoader, showRejectLoader, hideLoader } = useLoader();
const props = defineProps({
    employe: Object,
    typePieces: Array,
    kycStatuts: Object,
    userStatuts: Object,
});
const { can } = usePermissions();
const showKycValidateModal = ref(false);
const showKycRejectModal = ref(false);
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const openKycValidateModal = () => {
    showKycValidateModal.value = true;
};
const openKycRejectModal = () => {
    showKycRejectModal.value = true;
};
const validerKyc = () => {
    showValidateLoader();
    router.post(route('employe.kyc.validation', [props.employe.id, 'valider']), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showKycValidateModal.value = false;
        },
        onFinish: () => {
            hideLoader();
        },
    });
};
const rejeterKyc = (motif) => {
    showRejectLoader();
    router.post(route('employe.kyc.validation', [props.employe.id, 'rejeter']), { motif }, {
        preserveScroll: true,
        onSuccess: () => {
            showKycRejectModal.value = false;
        },
        onFinish: () => {
            hideLoader();
        },
    });
};
const canValidateKyc = () => {
    const status = props.employe.user?.kyc_status;
    return status === 'non_verifie' || status === 'en_attente' || status === 'rejete';
};
const canRejectKyc = () => {
    const status = props.employe.user?.kyc_status;
    return status === 'non_verifie' || status === 'en_attente' || status === 'rejete';
};
const form = {
    marchand_id: props.employe.marchand_id || '',
    points_vente_id: props.employe.points_vente_id || '',
    type_employe: props.employe.type_employe || '',
    date_embauche: props.employe.date_embauche || '',
    pays_id: props.employe.user?.pays_id || '',
    nom: props.employe.user?.nom || '',
    prenoms: props.employe.user?.prenoms || '',
    email: props.employe.user?.email || '',
    tel: props.employe.user?.login || '',
    type_piece: props.employe.user?.type_piece || '',
    numero_piece: props.employe.user?.numero_piece || '',
    date_delivrance: props.employe.user?.date_delivrance || '',
    date_naissance: props.employe.user?.date_naissance || '',
    lieu_naissance: props.employe.user?.lieu_naissance || '',
    lieu_delivrance: props.employe.user?.lieu_delivrance || '',
    errors: {},
};
const marchands = [{ id: props.employe.marchand_id, label: props.employe.marchand }];
const pointsVente = [{ id: props.employe.points_vente_id, label: props.employe.point_vente }];
const typeEmployes = [
    { value: 'caissier', label: t('modules.business.employes.types.caissier') },
    { value: 'manager', label: t('modules.business.employes.types.manager') },
];
const existingFiles = {
    photoprofile: props.employe.user?.photoprofile,
    piecerecto: props.employe.user?.piecerecto,
    pieceverso: props.employe.user?.pieceverso,
};
const getKycBadgeClass = (status) => {
    const classes = {
        'non_verifie': 'bg-warning',
        'en_attente': 'bg-info',
        'verifie': 'bg-success',
        'rejete': 'bg-danger',
    };
    return classes[status] || 'bg-secondary';
};
const getKycLabel = (status) => {
    const labels = {
        'non_verifie': t('kyc.non_verifie'),
        'en_attente': t('kyc.en_attente'),
        'verifie': t('kyc.verifie'),
        'rejete': t('kyc.rejete'),
    };
    return labels[status] || status;
};
const getStatutBadgeClass = (status) => {
    const classes = {
        'non_actif': 'bg-secondary',
        'actif': 'bg-success',
        'suspendu': 'bg-warning',
        'bloque': 'bg-danger',
        'supprime': 'bg-secondary',
    };
    return classes[status] || 'bg-secondary';
};
const getStatutLabel = (status) => {
    const labels = {
        'non_actif': t('statuts.inactif'),
        'actif': t('statuts.actif'),
        'suspendu': t('statuts.suspendu'),
        'bloque': t('statuts.bloque'),
        'supprime': t('statuts.supprime'),
    };
    return labels[status] || status;
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
                                <h5 class="title mb-0">{{ t('modules.business.employes.show') }}</h5>
                            </div>
                            <div class="d-flex flex-grow-1 align-items-center">
                                <!-- Boutons KYC au centre -->
                                <div class="d-flex align-items-center gap-2 mx-auto" @click.stop>
                                    <button
                                        v-if="canValidateKyc()"
                                        type="button"
                                        class="btn btn-success btn-sm"
                                        @click="openKycValidateModal"
                                    >
                                        <i class="fa fa-check me-1"></i> <i class="fa fa-save"></i> {{ t('actions.validate') }} KYC
                                    </button>
                                    <button
                                        v-if="canRejectKyc()"
                                        type="button"
                                        class="btn btn-warning btn-sm"
                                        @click="openKycRejectModal"
                                    >
                                        <i class="fa fa-times me-1"></i> {{ t('actions.reject') }} KYC
                                    </button>
                                </div>
                                <!-- KYC et Statut à droite -->
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-1">
                                        <small class="text-muted">KYC:</small>
                                        <span :class="['badge', getKycBadgeClass(employe.user?.kyc_status)]">
                                            {{ getKycLabel(employe.user?.kyc_status) }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <small class="text-muted">{{ t('common.status') }}:</small>
                                        <span :class="['badge', getStatutBadgeClass(employe.statut)]">
                                            {{ getStatutLabel(employe.statut) }}
                                        </span>
                                    </div>
                                </div>
                                <!-- Bouton de réduction -->
                                <button type="button" class="collapse-toggle ms-3" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                            </div>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <div class="mb-3 p-3 bg-light rounded">
                                <strong>{{ t('fields.employeeCode') }}:</strong> {{ employe.code_employe }}
                            </div>
                            <EmployeForm
                                :form="form"
                                :typePieces="typePieces"
                                :typeEmployes="typeEmployes"
                                :marchands="marchands"
                                :pointsVente="pointsVente"
                                :existingFiles="existingFiles"
                                mode="show"
                            />
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('employe.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de validation KYC -->
        <ConfirmModal
            :show="showKycValidateModal"
            :title="t('actions.validate')"
            :message="t('messages.confirm.validate.message')"
            @close="showKycValidateModal = false"
            @confirm="validerKyc"
        />
        <!-- Modal de rejet KYC -->
        <RejectModal
            :show="showKycRejectModal"
            :title="t('actions.reject')"
            :message="t('messages.confirm.reject.message')"
            @close="showKycRejectModal = false"
            @confirm="rejeterKyc"
        />
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
