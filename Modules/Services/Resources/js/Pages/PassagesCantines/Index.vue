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
import FilterBar from '@/Components/Common/FilterBar.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    passagesCantines: Object,
    filters: Object,
});
const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
});
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher…', icon: 'fa-search', width: '220px' },
    { key: 'statut', type: 'select', placeholder: 'Tous les statuts', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
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
    router.get(route('passage-cantine.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
}
const resetFilters = () => {
    Object.keys(searchFilters.value).forEach((k) => { searchFilters.value[k] = ''; });
    router.get(route('passage-cantine.index'));
};
function confirmDelete(item) {
    itemToDelete.value = item;
    showDeleteModal.value = true;
}
function deletePassageCantine() {
    showStoreLoader();
    router.put(route('passage-cantine.statut', itemToDelete.value.id), {}, {
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
    <Head :title="t('common.passage-cantine')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.passage-cantine') }}</h4>
            <div v-if="can('passage-cantine-create')" class="dashboard-btn">
                <Link :href="route('passage-cantine.create')" class="btn btn-primary">
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
                    <tr v-for="passageCantine in passagesCantines.data" :key="passageCantine.id">
                        <td><strong>{{ passageCantine.nom || passageCantine.name || passageCantine.titre }}</strong></td>
                        <td>
                            <span :class="['badge', getStatutBadgeClass(passageCantine.statut)]">
                                {{ getStatutLabel(passageCantine.statut) }}
                            </span>
                        </td>
                        <td class="fit">
                            <div class="action-buttons">
                                <Link
                                    :href="route('passage-cantine.show', passageCantine.id)"
                                    class="btn btn-secondary btn-sm"
                                    title="Voir"
                                >
                                    <i class="fa fa-eye"></i>
                                </Link>
                                <Link
                                    v-if="can('passage-cantine-edit')"
                                    :href="route('passage-cantine.edit', passageCantine.id)"
                                    class="btn btn-primary btn-sm"
                                    title="Modifier"
                                >
                                    <i class="fa fa-edit"></i>
                                </Link>
                                <button
                                    v-if="can('passage-cantine-delete')"
                                    @click="confirmDelete(passageCantine)"
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
            <div v-if="!passagesCantines.data.length" class="alert alert-info text-center">
                {{ t('common.no_data') }}
            </div>
        </div>
        <Pagination :data="passagesCantines" :preserve-scroll="true" />
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('common.confirm_delete')"
            :message="passageCantineToDelete?.etat === 'actif' || passageCantineToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')"
            @confirm="deletePassageCantine"
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
