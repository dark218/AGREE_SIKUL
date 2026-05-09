<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant } = useLoader();
const props = defineProps({
    wallet: Object,
    statuts: Object,
});
const isCollapsed = ref(false);
const isMouvementsCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const toggleMouvementsCollapse = () => {
    isMouvementsCollapsed.value = !isMouvementsCollapsed.value;
};
function getStatutBadgeClass(status) {
    const classes = {
        'actif': 'bg-success',
        'suspendu': 'bg-warning',
        'ferme': 'bg-danger'
};
return classes[status] || 'bg-secondary';
}
function getTypeMouvementBadgeClass(type) {
    const classes = {
        'credit': 'bg-success',
        'debit': 'bg-danger',
        'blocage': 'bg-warning',
        'deblocage': 'bg-info',
        'commission': 'bg-secondary',
        'remboursement': 'bg-primary',
        'ajustement': 'bg-dark'
};
return classes[type] || 'bg-secondary';
}
function formatMontant(montant, isPositive = true) {
    const prefix = isPositive ? '+' : '-';
    return `${prefix} ${montant}`;
}
const totalEntrees = computed(() => {
    if (!props.wallet?.statistiques?.total_entrees) return '0';
    return props.wallet.statistiques.total_entrees;
});
const totalSorties = computed(() => {
    if (!props.wallet?.statistiques?.total_sorties) return '0';
    return props.wallet.statistiques.total_sorties;
});</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <!-- Section Informations du Wallet -->
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.wallet.show') }}</h5>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-1">
                                    <small class="text-muted">{{ t('common.status') }}:</small>
                                    <span :class="['badge', getStatutBadgeClass(wallet?.statut)]">
                                        {{ wallet?.statut_label }}
                                    </span>
                                </div>
                                <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                            </div>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <!-- Informations du propriétaire -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="fa fa-user me-2"></i>{{ t('modules.wallet.sections.owner') }}</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('modules.wallet.fields.ownerType') }}</div>
                                                <div class="col-8 fw-bold text-capitalize">{{ wallet?.owner?.type }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.name') }}</div>
                                                <div class="col-8">{{ wallet?.owner?.nom }} {{ wallet?.owner?.prenoms }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.email') }}</div>
                                                <div class="col-8">{{ wallet?.owner?.email || '-' }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.phone') }}</div>
                                                <div class="col-8">{{ wallet?.owner?.login || wallet?.owner?.telephone || '-' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="fa fa-wallet me-2"></i>{{ t('modules.wallet.sections.walletInfo') }}</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('modules.wallet.fields.reference') }}</div>
                                                <div class="col-8 fw-bold">{{ wallet?.reference || '-' }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.countryCurrency') }}</div>
                                                <div class="col-8">{{ wallet?.pays_devise?.pays }} - {{ wallet?.pays_devise?.devise }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.createdAt') }}</div>
                                                <div class="col-8">{{ wallet?.created_at }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Soldes -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h6 class="text-white-50">{{ t('modules.wallet.fields.solde') }}</h6>
                                            <h3 class="mb-0">{{ wallet?.solde }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-dark">
                                        <div class="card-body text-center">
                                            <h6 class="text-dark-50">{{ t('modules.wallet.fields.soldeBloque') }}</h6>
                                            <h3 class="mb-0">{{ wallet?.solde_bloque }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <h6 class="text-white-50">{{ t('modules.wallet.fields.soldeCommission') }}</h6>
                                            <h3 class="mb-0">{{ wallet?.solde_commission || '0' }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-secondary text-white">
                                        <div class="card-body text-center">
                                            <h6 class="text-white-50">{{ t('modules.wallet.fields.soldeAttente') }}</h6>
                                            <h3 class="mb-0">{{ wallet?.solde_attente || '0' }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Statistiques -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card border-success">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-muted mb-1">{{ t('modules.wallet.stats.totalEntrees') }}</h6>
                                                    <h4 class="text-success mb-0">{{ totalEntrees }}</h4>
                                                </div>
                                                <i class="fa fa-arrow-down fa-2x text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-danger">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-muted mb-1">{{ t('modules.wallet.stats.totalSorties') }}</h6>
                                                    <h4 class="text-danger mb-0">{{ totalSorties }}</h4>
                                                </div>
                                                <i class="fa fa-arrow-up fa-2x text-danger"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-primary">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-muted mb-1">{{ t('modules.wallet.stats.nombreOperations') }}</h6>
                                                    <h4 class="text-primary mb-0">{{ wallet?.statistiques?.nombre_operations || 0 }}</h4>
                                                </div>
                                                <i class="fa fa-exchange-alt fa-2x text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('wallet.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Section Mouvements -->
                <div class="dash-payment-item-wrapper mt-4" v-if="wallet?.mouvements && wallet.mouvements.length > 0">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleMouvementsCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.wallet.sections.mouvements') }} ({{ wallet.mouvements.length }})</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isMouvementsCollapsed }" @click.stop="toggleMouvementsCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isMouvementsCollapsed }">
                            <div class="table-responsive">
                                <table class="custom-table">
                                    <thead>
                                        <tr>
                                            <th>{{ t('modules.wallet.mouvement.date') }}</th>
                                            <th>{{ t('modules.wallet.mouvement.reference') }}</th>
                                            <th>{{ t('modules.wallet.mouvement.type') }}</th>
                                            <th>{{ t('modules.wallet.mouvement.montant') }}</th>
                                            <th>{{ t('modules.wallet.mouvement.soldeAvant') }}</th>
                                            <th>{{ t('modules.wallet.mouvement.soldeApres') }}</th>
                                            <th>{{ t('modules.wallet.mouvement.description') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="mouvement in wallet.mouvements" :key="mouvement.id">
                                            <td>{{ mouvement.created_at }}</td>
                                            <td>
                                                <code>{{ mouvement.reference }}</code>
                                                <div v-if="mouvement.source_reference" class="small text-muted">
                                                    Source: {{ mouvement.source_reference }}
                                                </div>
                                            </td>
                                            <td>
                                                <span :class="['badge', getTypeMouvementBadgeClass(mouvement.type_mouvement)]">
                                                    {{ mouvement.type_mouvement_label }}
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold" :class="{
                                                'text-success': ['credit', 'deblocage'].includes(mouvement.type_mouvement),
                                                'text-danger': ['debit', 'blocage', 'commission'].includes(mouvement.type_mouvement)
                                            }">
                                                {{ mouvement.montant }}
                                            </td>
                                            <td class="text-end">{{ mouvement.solde_avant }}</td>
                                            <td class="text-end fw-bold">{{ mouvement.solde_apres }}</td>
                                            <td>{{ mouvement.description || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
