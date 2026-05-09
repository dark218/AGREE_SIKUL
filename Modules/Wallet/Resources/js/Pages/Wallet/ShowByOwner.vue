<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant } = useLoader();
const props = defineProps({
    id: Number,
    reference: String,
    statut: String,
    statut_label: String,
    created_at: String,
    updated_at: String,
    solde: String,
    solde_cents: Number,
    solde_bloque: String,
    solde_bloque_cents: Number,
    solde_commission: String,
    solde_commission_cents: Number,
    solde_attente: String,
    solde_attente_cents: Number,
    owner: Object,
    pays_devise: Object,
    mouvements: Array,
    statistiques: Object,
    meta: Object,
    owner_info: Object,
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
function getOwnerTypeIcon(type) {
    const icons = {
        'marchand': 'fa-store',
        'client': 'fa-user',
        'agent': 'fa-user-tie',
        'plateforme': 'fa-building'
};
return icons[type] || 'fa-user';
}
function getOwnerTypeLabel(type) {
    const labels = {
        'marchand': t('modules.wallet.ownerTypes.marchand'),
        'client': t('modules.wallet.ownerTypes.client'),
        'agent': t('modules.wallet.ownerTypes.agent'),
        'plateforme': t('modules.wallet.ownerTypes.plateforme')
};
return labels[type] || type;
}
function goBack() {
    window.history.back();
}
const totalEntrees = computed(() => {
    if (!props.statistiques?.total_entrees) return '0';
    return props.statistiques.total_entrees;
});
const totalSorties = computed(() => {
    if (!props.statistiques?.total_sorties) return '0';
    return props.statistiques.total_sorties;
});
const ownerTitle = computed(() => {
    if (props.owner_info?.type === 'marchand') {
        return props.owner_info?.raison_sociale || props.owner_info?.nom_complet;
    }
    return props.owner_info?.nom_complet || '-';
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
                                <span class="dash-payment-badge">
                                    <i :class="['fa', getOwnerTypeIcon(owner_info?.type)]"></i>
                                </span>
                                <h5 class="title mb-0">
                                    {{ t('modules.wallet.showByOwner') }} - {{ ownerTitle }}
                                </h5>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-1">
                                    <small class="text-muted">{{ t('common.status') }}:</small>
                                    <span :class="['badge', getStatutBadgeClass(statut)]">
                                        {{ statut_label }}
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
                                            <h6 class="mb-0">
                                                <i :class="['fa me-2', getOwnerTypeIcon(owner_info?.type)]"></i>
                                                {{ getOwnerTypeLabel(owner_info?.type) }}
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.name') }}</div>
                                                <div class="col-8 fw-bold">{{ owner_info?.nom_complet || '-' }}</div>
                                            </div>
                                            <div v-if="owner_info?.raison_sociale" class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.businessName') }}</div>
                                                <div class="col-8">{{ owner_info.raison_sociale }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.email') }}</div>
                                                <div class="col-8">{{ owner_info?.email || '-' }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.phone') }}</div>
                                                <div class="col-8">{{ owner_info?.telephone || '-' }}</div>
                                            </div>
                                            <div v-if="owner_info?.matricule" class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('modules.wallet.fields.matricule') }}</div>
                                                <div class="col-8">{{ owner_info.matricule }}</div>
                                            </div>
                                            <div v-if="owner_info?.identifiant_fiscal" class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.taxId') }}</div>
                                                <div class="col-8">{{ owner_info.identifiant_fiscal }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.createdAt') }}</div>
                                                <div class="col-8">{{ owner_info?.created_at || '-' }}</div>
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
                                                <div class="col-8 fw-bold"><code>{{ reference || '-' }}</code></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.countryCurrency') }}</div>
                                                <div class="col-8">{{ pays_devise?.pays }} - {{ pays_devise?.devise }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.createdAt') }}</div>
                                                <div class="col-8">{{ created_at }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('modules.wallet.stats.derniereOperation') }}</div>
                                                <div class="col-8">{{ statistiques?.derniere_operation || '-' }}</div>
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
                                            <h3 class="mb-0">{{ solde }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-dark">
                                        <div class="card-body text-center">
                                            <h6 class="text-dark-50">{{ t('modules.wallet.fields.soldeBloque') }}</h6>
                                            <h3 class="mb-0">{{ solde_bloque }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <h6 class="text-white-50">{{ t('modules.wallet.fields.soldeCommission') }}</h6>
                                            <h3 class="mb-0">{{ solde_commission || '0' }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-secondary text-white">
                                        <div class="card-body text-center">
                                            <h6 class="text-white-50">{{ t('modules.wallet.fields.soldeAttente') }}</h6>
                                            <h3 class="mb-0">{{ solde_attente || '0' }}</h3>
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
                                                    <h4 class="text-primary mb-0">{{ statistiques?.nombre_operations || 0 }}</h4>
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
                                        <button @click="goBack" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Section Mouvements -->
                <div class="dash-payment-item-wrapper mt-4" v-if="mouvements && mouvements.length > 0">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleMouvementsCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.wallet.sections.mouvements') }} ({{ mouvements.length }})</h5>
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
                                        <tr v-for="mouvement in mouvements" :key="mouvement.id">
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
                <!-- Message si pas de mouvements -->
                <div class="dash-payment-item-wrapper mt-4" v-else>
                    <div class="dash-payment-item active">
                        <div class="dash-payment-body text-center py-5">
                            <i class="fa fa-exchange-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ t('modules.wallet.noMouvements') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
