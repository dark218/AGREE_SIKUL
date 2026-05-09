<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import RejectModal from '@/Components/Common/RejectModal.vue';
import MarchandForm from './MarchandForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showValidateLoader, showRejectLoader, hideLoader } = useLoader();
const props = defineProps({
    marchand: Object,
    payss: Array,
    typePieces: Array,
    kycStatuts: Object,
    userStatuts: Object,
});
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
    router.post(route('marchand.kyc.validation', [props.marchand.id, 'valider']), {}, {
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
    router.post(route('marchand.kyc.validation', [props.marchand.id, 'rejeter']), { motif }, {
        preserveScroll: true,
        onSuccess: () => {
            showKycRejectModal.value = false;
        },
        onFinish: () => {
            hideLoader();
        },
    });
};
const { can } = usePermissions();
const canValidateKyc = () => {
    const status = props.marchand.proprietaire?.kyc_status;
    return status === props.kycStatuts?.non_verifie || status === props.kycStatuts?.en_attente || status === props.kycStatuts?.rejete;
};
const canRejectKyc = () => {
    const status = props.marchand.proprietaire?.kyc_status;
    return status === props.kycStatuts?.non_verifie || status === props.kycStatuts?.en_attente || status === props.kycStatuts?.rejete;
};
const form = {
    pays_id: props.marchand.proprietaire?.pays_id || '',
    raison_sociale: props.marchand.raison_sociale || '',
    identifiant_fiscal: props.marchand.identifiant_fiscal || '',
    type: props.marchand.type || '',
    nom: props.marchand.proprietaire?.nom || '',
    prenoms: props.marchand.proprietaire?.prenoms || '',
    email: props.marchand.proprietaire?.email || '',
    tel: props.marchand.proprietaire?.login || '',
    type_piece: props.marchand.proprietaire?.type_piece || '',
    numero_piece: props.marchand.proprietaire?.numero_piece || '',
    date_delivrance: props.marchand.proprietaire?.date_delivrance || '',
    date_naissance: props.marchand.proprietaire?.date_naissance || '',
    lieu_naissance: props.marchand.proprietaire?.lieu_naissance || '',
    lieu_delivrance: props.marchand.proprietaire?.lieu_delivrance || '',
    errors: {},
};
const existingFiles = {
    photoprofile: props.marchand.proprietaire?.photoprofile,
    piecerecto: props.marchand.proprietaire?.piecerecto,
    pieceverso: props.marchand.proprietaire?.pieceverso,
    dfe: props.marchand.dfe,
    rccm: props.marchand.rccm,
};
const getKycBadgeClass = (status) => {
    const kyc = props.kycStatuts || {};
    const classes = {
        [kyc.non_verifie]: 'bg-warning',
        [kyc.en_attente]: 'bg-info',
        [kyc.verifie]: 'bg-success',
        [kyc.rejete]: 'bg-danger',
    };
    return classes[status] || 'bg-secondary';
};
const getKycLabel = (status) => {
    const kyc = props.kycStatuts || {};
    const labels = {
        [kyc.non_verifie]: t('common.notVerified'),
        [kyc.en_attente]: t('common.pending'),
        [kyc.verifie]: t('common.verified'),
        [kyc.rejete]: t('common.rejected'),
    };
    return labels[status] || status;
};
const getStatutBadgeClass = (status) => {
    const statuts = props.userStatuts || {};
    const classes = {
        [statuts.non_actif]: 'bg-secondary',
        [statuts.actif]: 'bg-success',
        [statuts.suspendu]: 'bg-warning',
        [statuts.bloque]: 'bg-danger',
        [statuts.supprime]: 'bg-secondary',
    };
    return classes[status] || 'bg-secondary';
};
const getStatutLabel = (status) => {
    const statuts = props.userStatuts || {};
    const labels = {
        [statuts.non_actif]: t('statuts.inactif'),
        [statuts.actif]: t('statuts.actif'),
        [statuts.suspendu]: t('statuts.suspendu'),
        [statuts.bloque]: t('statuts.bloque'),
        [statuts.supprime]: t('statuts.supprime'),
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
                                <h5 class="title mb-0">{{ t('modules.business.marchands.show') }}</h5>
                            </div>
                            <div class="d-flex flex-grow-1 align-items-center">
                                <!-- Boutons KYC au centre -->
                                <div class="d-flex align-items-center gap-2 mx-auto" @click.stop>
                                    <button
                                        v-if="can('marchand-validate') && canValidateKyc()"
                                        type="button"
                                        class="btn btn-success btn-sm"
                                        @click="openKycValidateModal"
                                    >
                                        <i class="fa fa-check me-1"></i> <i class="fa fa-save"></i> {{ t('actions.validate') }} KYC
                                    </button>
                                    <button
                                        v-if="can('marchand-validate') && canRejectKyc()"
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
                                        <span :class="['badge', getKycBadgeClass(marchand.proprietaire?.kyc_status)]">
                                            {{ getKycLabel(marchand.proprietaire?.kyc_status) }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <small class="text-muted">{{ t('common.status') }}:</small>
                                        <span :class="['badge', getStatutBadgeClass(marchand.statut)]">
                                            {{ getStatutLabel(marchand.statut) }}
                                        </span>
                                    </div>
                                </div>
                                <!-- Bouton de réduction -->
                                <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                            </div>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <MarchandForm
                                :form="form"
                                :payss="payss"
                                :typePieces="typePieces"
                                :existingFiles="existingFiles"
                                mode="show"
                            />
                            <!-- Points de vente -->
                            <div class="mt-4" v-if="marchand.points_vente && marchand.points_vente.length > 0">
                                <h5>{{ t('modules.business.pointsvente.title') }}</h5>
                                <div class="table-responsive">
                                    <table class="custom-table">
                                        <thead>
                                            <tr>
                                                <th>{{ t('fields.zone') }}</th>
                                                <th>{{ t('fields.name') }}</th>
                                                <th>{{ t('fields.address') }}</th>
                                                <th>{{ t('fields.phone') }}</th>
                                                <th>{{ t('common.status') }}</th>
                                                <th class="fit">{{ t('common.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="pv in marchand.points_vente" :key="pv.id">
                                                <td>{{ pv.zone }}</td>
                                                <td>{{ pv.nom }}</td>
                                                <td>{{ pv.adresse }}</td>
                                                <td>{{ pv.telephone }}</td>
                                                <td>
                                                    <span :class="['badge', getStatutBadgeClass(pv.statut)]">
                                                        {{ getStatutLabel(pv.statut) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <Link
                                                        :href="route('pointvente.show', pv.id)"
                                                        class="btn btn-secondary btn-sm"
                                                    >
                                                        <i class="fa fa-eye"></i>
                                                    </Link>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('marchand.index')" class="btn btn-danger">
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
