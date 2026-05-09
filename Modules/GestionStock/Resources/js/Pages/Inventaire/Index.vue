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
    inventaires: Object,
    pays: Array,
    paysCurrent: [String, Number],
    filters: Object,
    pointsVenteOptions: Array,
    statuts: Array,
    useDashboardLayout: Boolean,
});
const searchFilters = ref({
    pays_id: props.filters?.pays_id || props.paysCurrent || '',
    statut: props.filters?.statut || '',
    date_debut: props.filters?.date_debut || '',
    date_fin: props.filters?.date_fin || '',
    emplacement_id: props.filters?.emplacement_id || '',
});
const search = () => {
    router.get(route('inventaire.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};
const resetFilters = () => {
    searchFilters.value = {
        pays_id: props.paysCurrent || '',
        statut: '',
        date_debut: '',
        date_fin: '',
        emplacement_id: '',
    };
    search();
};
const getStatutLabel = (statut) => {
    const labels = {
        'brouillon': t('modules.business.inventaire.statuts.brouillon'),
        'valide': t('modules.business.inventaire.statuts.valide'),
        'annule': t('modules.business.inventaire.statuts.annule')
    };
    return labels[statut] || statut;
};
const getStatutBadgeClass = (statut) => {
    const classes = {
        'brouillon': 'bg-warning text-dark',
        'valide': 'bg-success text-white',
        'annule': 'bg-danger text-white',
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
// Vérifier si l'utilisateur est manager (pour afficher le bouton retour)
const isManager = computed(() => {
    return !props.useDashboardLayout;
});
// Traduire les statuts pour le dropdown
const statutsOptions = computed(() => {
    return (props.statuts || []).map(s => ({
        value: s.value,
        label: t(`modules.business.inventaire.statuts.${s.value}`)
    }));
});
</script>
<template>
    <Head :title="t('modules.business.inventaire.title')" />
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
                <div v-if="can('inventaire-create')">
                    <Link :href="route('inventaire.create')" class="btn btn-primary">
                        <i class="fa fa-plus me-1"></i>
                        {{ t('modules.business.inventaire.create') }}
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
                     <div class="col-md-2 p-1" v-if="pointsVenteOptions && pointsVenteOptions.length > 0">
                        <StylishSelect
                            v-model="searchFilters.emplacement_id"
                            :options="pointsVenteOptions"
                            option-value="value"
                            option-label="label"
                            placeholder="Emplacement"
                        />
                    </div>
                    <div class="col-md-2 p-1">
                        <StylishSelect
                            v-model="searchFilters.statut"
                            :options="statutsOptions"
                            option-value="value"
                            option-label="label"
                            :placeholder="t('common.status')"
                        />
                    </div>
                    <div class="col-md-2 p-1">
                        <input
                            type="date"
                            v-model="searchFilters.date_debut"
                            class="form-control"
                            placeholder="Date début"
                        />
                    </div>
                    <div class="col-md-2 p-1">
                        <input
                            type="date"
                            v-model="searchFilters.date_fin"
                            class="form-control"
                            placeholder="Date fin"
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
                                    <th>{{ t('Date inventaire') }}</th>
                                    <th>{{ t('Emplacement') }}</th>
                                    <th>{{ t('Créé par') }}</th>
                                    <th>{{ t('Statut') }}</th>
                                    <th>{{ t('Date validation') }}</th>
                                    <th>{{ t('Validé par') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="inventaires?.data && inventaires.data.length > 0">
                                    <tr v-for="inventaire in inventaires.data" :key="inventaire.id">
                                        <td>{{ formatDate(inventaire.date_inventaire) }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <i class="fa fa-store me-1"></i>
                                                {{ inventaire.emplacement?.nom || '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ inventaire.cree_par?.user?.nom }} {{ inventaire.cree_par?.user?.prenoms }}
                                        </td>
                                        <td>
                                            <span :class="['badge', getStatutBadgeClass(inventaire.statut)]">
                                                {{ getStatutLabel(inventaire.statut) }}
                                            </span>
                                        </td>
                                        <td>{{ formatDate(inventaire.date_validation) }}</td>
                                        <td>
                                            {{ inventaire.valide_par?.user?.nom }} {{ inventaire.valide_par?.user?.prenoms }}
                                        </td>
                                        <td class="fit">
                                            <div class="action-buttons">
                                                <Link
                                                    :href="route('inventaire.show', inventaire.id)"
                                                    class="btn btn-secondary btn-sm"
                                                    :title="t('actions.view')"
                                                >
                                                    <i class="fa fa-eye"></i>
                                                </Link>
                                                <Link
                                                    v-if="can('inventaire-edit') && inventaire.statut === 'brouillon'"
                                                    :href="route('inventaire.edit', inventaire.id)"
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
                                    <td colspan="7" class="text-center">{{ t('common.emptyList') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="inventaires" />
                </div>
            </div>
        </div>
    </div>
</template>
