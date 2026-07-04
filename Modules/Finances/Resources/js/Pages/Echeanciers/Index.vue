<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    echeanciers: Object,
    filters: Object,
});
const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
});
const statutOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher…', icon: 'fa-search', width: '220px' },
    { key: 'statut', type: 'select', placeholder: 'Tous les statuts', options: statutOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];
// Debounce timer for real-time search
let searchTimeout;
// Real-time search with debounce
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
function search() {
    router.get(route('finances.echeancier.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
}
const resetFilters = () => {
    Object.keys(searchFilters.value).forEach((k) => { searchFilters.value[k] = ''; });
    router.get(route('finances.echeancier.index'));
};
function confirmDelete(item) {
    itemToDelete.value = item;
    showDeleteModal.value = true;
}
function deleteEcheancier() {
    showStoreLoader();
    router.put(route('finances.echeancier.statut', itemToDelete.value.id), {}, {
        onSuccess: () => {
            showDeleteModal.value = false;
            hideLoader();
        },
        onError: () => hideLoader(),
    });
}
function getStatutLabel(statut) {
    const labels = {
        actif: 'Actif',
        inactif: 'Inactif',
        suspendu: 'Suspendu',
        exclu: 'Exclu',
    };
    return labels[statut] || statut;
}
function getStatutBadgeClass(statut) {
    const classes = {
        actif: 'badge-success',
        inactif: 'badge-secondary',
        suspendu: 'badge-warning',
        exclu: 'badge-danger',
    };
    return classes[statut] || 'badge-secondary';
}
// Real-time search with debounce
watch(
  () => searchFilters.value,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      search();
    }, 500); // 500ms debounce
  },
  { deep: true }
);
</script>
<template>
    <Head :title="t('common.echeancier')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.echeancier') }}</h4>
            <div v-if="can('echeancier-create')" class="dashboard-btn">
                <Link :href="route('finances.echeancier.create')" class="btn btn-primary">
                    <i class="fa fa-plus"></i> {{ t('common.add') }}
                </Link>
            </div>
        </div>
        <AlertMessage />
        <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters"></FilterBar>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>{{ t('common.nom') }}</th>
                        <th>{{ t('common.statut') }}</th>
                        <th class="fit">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="echeancier in echeanciers.data" :key="echeancier.id">
                        <td><strong>{{ echeancier.nom || echeancier.name || echeancier.titre }}</strong></td>
                        <td>
                            <span :class="['badge', getStatutBadgeClass(echeancier.statut)]">
                                {{ getStatutLabel(echeancier.statut) }}
                            </span>
                        </td>
                        <td class="fit">
                            <div class="action-buttons">
                                <Link
                                    :href="route('finances.echeancier.show', echeancier.id)"
                                    class="btn btn-secondary btn-sm"
                                    title="Voir"
                                >
                                    <i class="fa fa-eye"></i>
                                </Link>
                                <Link
                                    v-if="can('echeancier-edit')"
                                    :href="route('finances.echeancier.edit', echeancier.id)"
                                    class="btn btn-primary btn-sm"
                                    title="Modifier"
                                >
                                    <i class="fa fa-edit"></i>
                                </Link>
                                <button
                                    v-if="can('echeancier-delete')"
                                    @click="confirmDelete(echeancier)"
                                    class="btn btn-danger btn-sm"
                                    title="Supprimer"
                                >
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="!echeanciers.data.length" class="alert alert-info text-center">
                {{ t('common.no_data') }}
            </div>
        </div>
        <Pagination :data="echeanciers" :preserve-scroll="true" />
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('common.confirm_delete')"
            :message="echeancierToDelete?.etat === 'actif' || echeancierToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')"
            @confirm="deleteEcheancier"
            @cancel="showDeleteModal = false"
        />
        <FullPageLoader :show="isLoading" :message="loaderMessage" />
    </div>
</template>
<style scoped>
.filter-form {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}
.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}
.action-buttons .btn {
    padding: 5px 8px;
    font-size: 12px;
}
</style>
