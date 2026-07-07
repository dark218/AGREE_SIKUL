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
    typesFrais: Object,
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
    { key: 'statut', type: 'select', placeholder: 'Statut', options: statutOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
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
    router.get(route('finances.types-frais.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
}
function resetFilters() {
    searchFilters.value = { search: '', statut: '' };
    router.get(route('finances.types-frais.index'));
}
function confirmDelete(item) {
    itemToDelete.value = item;
    showDeleteModal.value = true;
}
function deleteTypeFrais() {
    showStoreLoader();
    router.put(route('finances.types-frais.statut', itemToDelete.value.id), {}, {
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
    <Head :title="t('common.type-frais')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.type-frais') }}</h4>
            <div v-if="can('type-frais-create')" class="dashboard-btn">
                <Link :href="route('finances.types-frais.create')" class="btn btn-primary">
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
                    <tr v-for="typeFrais in typesFrais.data" :key="typeFrais.id">
                        <td><strong>{{ typeFrais.nom || typeFrais.name || typeFrais.titre }}</strong></td>
                        <td>
                            <span :class="['badge', getStatutBadgeClass(typeFrais.statut)]">
                                {{ getStatutLabel(typeFrais.statut) }}
                            </span>
                        </td>
                        <td class="fit">
                            <div class="action-buttons">
                                <Link
                                    :href="route('finances.types-frais.show', typeFrais.id)"
                                    class="btn btn-secondary btn-sm"
                                    title="Voir"
                                >
                                    <i class="fa fa-eye"></i>
                                </Link>
                                <Link
                                    v-if="can('type-frais-edit')"
                                    :href="route('finances.types-frais.edit', typeFrais.id)"
                                    class="btn btn-primary btn-sm"
                                    title="Modifier"
                                >
                                    <i class="fa fa-edit"></i>
                                </Link>
                                <button
                                    v-if="can('type-frais-delete')"
                                    @click="confirmDelete(typeFrais)"
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
            <div v-if="!typesFrais.data.length" class="alert alert-info text-center">
                {{ t('common.no_data') }}
            </div>
        </div>
        <Pagination :data="typesFrais" :preserve-scroll="true" />
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('common.confirm_delete')"
            :message="typeFraisToDelete?.etat === 'actif' || typeFraisToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')"
            @confirm="deleteTypeFrais"
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
