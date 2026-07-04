<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();
const props = defineProps({ matieres: Object, filters: Object });
const searchFilters = ref({ search: props.filters?.search || '', statut: props.filters?.statut || '' });
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'non_actif', libelle: 'Inactif' },
];
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher', icon: 'fa-search', width: '220px' },
    { key: 'statut', type: 'select', placeholder: 'Statut', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];
let searchTimeout;
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const search = () => {
    router.get(route('parametrage.matieres.index'), searchFilters.value, { preserveState: true, preserveScroll: true });
};
const resetFilters = () => {
  searchFilters.value = { search: '', statut: '' };
  router.get(route('parametrage.matieres.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('parametrage.matieres.destroy', itemToDelete.value?.id), { method: 'delete', preserveScroll: true,
            onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; },
            onFinish: () => hideLoader() });
    }
};
const closeModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
};
const page = usePage();
const matieres = props.matieres || page.props.matieres;
watch(
  () => searchFilters.value,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => { search(); }, 500);
  },
  { deep: true }
);
</script>
<template>
    <Head :title="page.props.title || 'Matières'" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title || 'Matières' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('parametrage.matieres.create')" class="btn btn-primary"><i class="fa fa-plus"></i> {{ t('actions.add') }}</Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0">
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters">
                </FilterBar>
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.code') || 'Code' }}</th>
                                        <th>{{ t('fields.libelle') || 'Libellé' }}</th>
                                        <th>{{ t('fields.coefficient') || 'Coefficient' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="matieres?.data && matieres?.data.length > 0">
                                        <tr v-for="matiere in matieres?.data" :key="matiere.id">
                                            <td>{{ matiere.code || '' }}</td>
                                            <td>{{ matiere.libelle || '' }}</td>
                                            <td>{{ matiere.coefficient || '1' }}</td>
                                            <td><span class="badge" :class="matiere.statut === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + matiere.statut) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('parametrage.matieres.show', matiere?.id)" class="btn btn-secondary" :title="t('actions.view')"><span class="fa fa-eye"></span></Link>
                                                    <Link :href="route('parametrage.matieres.edit', matiere?.id)" class="btn btn-primary" :title="t('actions.edit')"><span class="fa fa-edit"></span></Link>
                                                    <button @click="confirmDelete(matiere)" class="btn btn-danger" :title="t('actions.delete')"><span class="fa fa-trash"></span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="5" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :data="matieres" />
                    </div>
                </div>
            </div>
        </div>
        <ConfirmModal :show="showDeleteModal" :title="t('messages.confirm.delete.title')" :message="t('messages.confirm.delete.message')" :sub-message="t('messages.confirm.delete.warning')" @close="closeModal" @confirm="deleteItem" :confirm-text="t('actions.delete')" :confirm-class="'btn-danger'" />
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
