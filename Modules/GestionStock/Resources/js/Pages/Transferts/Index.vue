<script>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PosLayout from '@/Layouts/PosLayout.vue';
// Layout dynamique basé sur useDashboardLayout
export default {
    layout: (h, page) => {
        const Layout = page.props.useDashboardLayout ? DashboardLayout : PosLayout;
        return h(Layout, () => page);
    }
};
</script>
<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Pagination from '@/Components/Common/Pagination.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import { usePermissions } from '@/Composables/usePermissions';
const { t } = useI18n();
const page = usePage();
const { can } = usePermissions();
// Déterminer le layout selon le rôle
const userRoles = computed(() => page.props.auth?.user?.roles || []);
const props = defineProps({
    transferts: Object,
    pays: Array,
    paysCurrent: [String, Number],
    filters: Object,
    pointsVenteSource: Array,
    pointsVenteDestination: Array,
    statuts: Array,
    useDashboardLayout: Boolean,
});
const searchFilters = ref({
    pays_id: props.filters?.pays_id || props.paysCurrent || '',
    reference: props.filters?.reference || '',
    statut: props.filters?.statut || '',
    date_debut: props.filters?.date_debut || '',
    date_fin: props.filters?.date_fin || '',
    emplacement_source_id: props.filters?.emplacement_source_id || '',
    emplacement_destination_id: props.filters?.emplacement_destination_id || '',
});
const search = () => {
    router.get(route('transfert-stock.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};
const resetFilters = () => {
    searchFilters.value = {
        pays_id: props.paysCurrent || '',
        reference: '',
        statut: '',
        date_debut: '',
        date_fin: '',
        emplacement_source_id: '',
        emplacement_destination_id: '',
    };
    search();
};
const getStatutLabel = (statut) => {
    return t(`modules.business.transfertStock.statuts.${statut}`) || statut;
};
const getStatutBadgeClass = (statut) => {
    const classes = {
        'en_cours': 'bg-warning text-dark',
        'partiel': 'bg-info text-white',
        'confirme': 'bg-success text-white',
        'annule': 'bg-danger text-white',
        'receptionne': 'bg-success text-white',
};
    return classes[statut] || 'bg-secondary';
};
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
// Formater les pays pour le select
const paysOptions = computed(() => {
    return (props.pays || []).map(p => ({
        value: p.id,
        label: p.libelle
    }));
});
// Vérifier si l'utilisateur est superadmin
const isSuperAdmin = computed(() => {
    return userRoles.value.some(role => role.name === 'Super Admin');
});
// Vérifier si l'utilisateur est superadmin ou marchand
const isSuperAdminOrMarchand = computed(() => {
    return userRoles.value.some(role => ['Super Admin', 'Marchand'].includes(role.name));
});
// ID de l'utilisateur courant
const currentUserId = computed(() => page.props.auth?.user?.id);
// Vérifier si un transfert est modifiable par l'utilisateur courant
const canEditTransfert = (transfert) => {
    if (transfert.statut !== 'en_cours') return false;
    if (isSuperAdminOrMarchand.value) return true;
    // Vérifier si l'utilisateur est l'initiateur (via la relation demande_par -> user)
    return transfert.demande_par?.user?.id === currentUserId.value;
};
// Vérifier si l'utilisateur est manager (pour afficher le bouton retour)
const isManager = computed(() => {
    return !props.useDashboardLayout;
});
</script>
<template>
    <Head :title="t('modules.business.transfertStock.title')" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper d-flex justify-content-between align-items-center mb-3">
                <!-- Bouton retour pour les managers (a href pour forcer le rechargement complet) -->
                <div v-if="isManager">
                    <a :href="route('session-caisse-manager.index')" class="btn btn-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                    </a>
                </div>
                <div v-else></div>
                <div v-if="can('transfert-stock-create')">
                    <Link :href="route('transfert-stock.create')" class="btn btn-primary">
                        <i class="fa fa-plus me-1"></i>
                        {{ t('modules.business.transfertStock.create') }}
                    </Link>
                </div>
            </div>
            <!-- Filtres -->
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <!-- Pays (superadmin uniquement) -->
                    <div class="col-md-2 p-1" v-if="isSuperAdmin">
                        <StylishSelect
                            v-model="searchFilters.pays_id"
                            :options="paysOptions"
                            option-value="value"
                            option-label="label"
                            placeholder="Pays"
                            @update:modelValue="search"
                        />
                    </div>
                    <div class="col-md-2 p-1">
                        <input
                            type="text"
                            v-model="searchFilters.reference"
                            class="form-control"
                            :placeholder="t('modules.business.transfertStock.fields.reference')"
                        />
                    </div>
                    <div class="col-md-2 p-1">
                        <StylishSelect
                            v-model="searchFilters.statut"
                            :options="statuts"
                            option-value="value"
                            option-label="label"
                            :placeholder="t('modules.business.transfertStock.fields.statut')"
                        />
                    </div>
                    <div class="col-md-2 p-1" v-if="pointsVenteSource && pointsVenteSource.length > 0">
                        <StylishSelect
                            v-model="searchFilters.emplacement_source_id"
                            :options="pointsVenteSource"
                            option-value="value"
                            option-label="label"
                            :placeholder="t('modules.business.transfertStock.labels.de')"
                        />
                    </div>
                    <div class="col-md-2 p-1" v-if="pointsVenteDestination && pointsVenteDestination.length > 0">
                        <StylishSelect
                            v-model="searchFilters.emplacement_destination_id"
                            :options="pointsVenteDestination"
                            option-value="value"
                            option-label="label"
                            :placeholder="t('modules.business.transfertStock.labels.vers')"
                        />
                    </div>
                    <div class="col-md-2 p-1 d-flex gap-1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-secondary" @click="resetFilters">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>{{ t('modules.business.transfertStock.fields.reference') }}</th>
                                    <th>{{ t('modules.business.transfertStock.fields.dateDemande') }}</th>
                                    <th>{{ t('modules.business.transfertStock.fields.dateValidation') }}</th>
                                    <th>{{ t('modules.business.transfertStock.labels.de') }}</th>
                                    <th>{{ t('modules.business.transfertStock.labels.vers') }}</th>
                                    <th>{{ t('modules.business.transfertStock.fields.demandePar') }}</th>
                                    <th>{{ t('modules.business.transfertStock.fields.statut') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="transferts?.data && transferts.data.length > 0">
                                    <tr v-for="transfert in transferts.data" :key="transfert.id">
                                        <td>
                                            <span class="fw-bold">{{ transfert.reference }}</span>
                                        </td>
                                        <td>{{ formatDate(transfert.date_demande) }}</td>
                                        <td>{{ formatDate(transfert.date_confirmation) }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <i class="fa fa-store me-1"></i>
                                                {{ transfert.emplacement_source?.nom || '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <i class="fa fa-building me-1"></i>
                                                {{ transfert.emplacement_destination?.nom || '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ transfert.demande_par?.user?.nom }} {{ transfert.demande_par?.user?.prenoms }}
                                        </td>
                                        <td>
                                            <span :class="['badge', getStatutBadgeClass(transfert.statut)]">
                                                {{ getStatutLabel(transfert.statut) }}
                                            </span>
                                        </td>
                                        <td class="fit">
                                            <div class="action-buttons">
                                                <Link
                                                    :href="route('transfert-stock.show', transfert.id)"
                                                    class="btn btn-secondary btn-sm"
                                                    :title="t('actions.view')"
                                                >
                                                    <i class="fa fa-eye"></i>
                                                </Link>
                                                <Link
                                                    v-if="(can('transfert-stock-edit') || isSuperAdminOrMarchand) && canEditTransfert(transfert)"
                                                    :href="route('transfert-stock.edit', transfert.id)"
                                                    class="btn btn-primary btn-sm"
                                                    :title="t('actions.edit')"
                                                >
                                                    <i class="fa fa-edit"></i>
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="8" class="text-center">{{ t('common.emptyList') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="transferts" />
                </div>
            </div>
        </div>
    </div>
</template>
