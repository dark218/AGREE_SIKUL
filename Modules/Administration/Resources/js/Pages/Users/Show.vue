<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import UserForm from './UserForm.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
defineOptions({
    layout: DashboardLayout
});
const { t } = useI18n();
const props = defineProps({
    title: String,
    user: Object,
    roles: Array,
    pays: Array,
    showPaysField: Boolean,
    typePieces: Array,
    kycStatuts: Array,
    userStatuts: [Array, Object],
    payss: {
        type: Array,
        default: () => []
    },
});
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
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
</script>
<template>
    <Head :title="t('modules.administration.users.show')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <!-- Header -->
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-eye"></i>
                                </span>
                                <h5 class="title mb-0">
                                    {{ t('modules.administration.users.show') }}
                                </h5>
                            </div>
                            <!-- KYC et Statut à droite -->
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-1">
                                    <small class="text-muted">KYC:</small>
                                    <span :class="['badge', getKycBadgeClass(user.kyc_status)]">
                                        {{ getKycLabel(user.kyc_status) }}
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <small class="text-muted">{{ t('common.status') }}:</small>
                                    <span :class="['badge', getStatutBadgeClass(user.statut)]">
                                        {{ getStatutLabel(user.statut) }}
                                    </span>
                                </div>
                                <!-- Bouton de réduction -->
                                <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Body -->
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <div class="col-xl-12 col-lg-6 mb-20">
                                <div class="">
                                    <!-- Alert Messages -->
                                    <AlertMessage />
                                    <div class="">
                                        <!-- Info box -->
                                       
                                        <!-- Formulaire en lecture seule -->
                                        <UserForm
                                            :user="user"
                                            :roles="roles"
                                            :payss="pays"
                                            :show-pays-field="showPaysField"
                                            :type-pieces="typePieces"
                                            :kyc-statuts="kycStatuts"
                                            :is-read-only="true"
                                        />
                                        <!-- Boutons d'action -->
                                        <div class="row mt-4">
                                            <div class="col">
                                                <div class="text-end">
                                                    <Link 
                                                        :href="route('administration.users.index')" 
                                                        class="btn btn-danger"
                                                    >
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
                </div>
            </div>
        </div>
    </div>
</template>
