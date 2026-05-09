<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    matieres: Object,
    filters: Object,
});
const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);
const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
});
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
let searchTimeout;
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const search = () => {
    router.get(route('academique.matieres.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};
const resetFilters = () => {
  const emptyFilters = {};
  Object.keys(searchFilters.value).forEach(key => {
    emptyFilters[key] = '';
  });
  searchFilters.value = emptyFilters;
  router.get(route('academique.matieres.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('academique.matieres.destroy', itemToDelete.value.id), { method: 'delete', preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
            },
            onFinish: () => hideLoader(), });
    }
};
const confirmDeactivate = (item) => {
    itemToDelete.value = item;
    deactivateMode.value = true;
    deleteMode.value = false;
    showDeleteModal.value = true;
};
const confirmActivate = (item) => {
    itemToDelete.value = item;
    activateMode.value = true;
    deleteMode.value = false;
    showDeleteModal.value = true;
};
const toggleStatus = () => {
    if (itemToDelete.value) {
        if (deactivateMode.value) {
            showDeactivateLoader();
        } else if (activateMode.value) {
            showActivateLoader();
        }
        router.visit(route('academique.matieres.statut', itemToDelete.value.id), { method: 'put',
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
                deactivateMode.value = false;
                activateMode.value = false;
            },
            onFinish: () => hideLoader(),
        });
    }
};
const closeModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
    deleteMode.value = false;
    deactivateMode.value = false;
    activateMode.value = false;
};
const page = usePage();
const matieres = props.matieres || page.props.matieres;
const isActif = (matiere) => {
    return matiere?.statut === 'actif' && !matiere?.deleted_at;
};
watch(
  () => searchFilters.value,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      search();
    }, 500);
  },
  { deep: true }
);
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('matieres-create')">
                        <Link :href="route('academique.matieres.create')" class="btn btn-primary">
                            {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0">
                <form @submit.prevent="search" style="display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap;">
                    <div style="width: 200px;">
                        <input type="text" v-model="searchFilters.search" class="form-control form-control-sm" :placeholder="t('fields.search')" style="height: 32px; font-size: 13px; width: 100%;" />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect v-model="searchFilters.statut" :options="statusOptions" optionValue="id" optionLabel="libelle" :placeholder="t('fields.status')" class="form-control-sm" style="height: 32px; width: 100%;" />
                    </div>
                    <div style="display: flex; gap: 4px;">
                        <button type="submit" class="btn btn-primary btn-sm" style="height: 32px; padding: 0 10px;"><i class="fa fa-search"></i></button>
                        <button type="button" class="btn btn-secondary btn-sm" @click="resetFilters" style="height: 32px; padding: 0 10px;"><i class="fa fa-redo"></i></button>
                    </div>
                </form>
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.code') }}</th>
                                        <th>{{ t('fields.libelle') }}</th>
                                        <th>{{ t('fields.coefficient') }}</th>
                                        <th>{{ t('fields.ecole') }}</th>
                                        <th>{{ t('fields.status') }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="matieres?.data && matieres?.data.length > 0">
                                        <tr v-for="matiere in matieres?.data" :key="matiere.id">
                                            <td>{{ matiere.code || '' }}</td>
                                            <td>{{ matiere.libelle || '' }}</td>
                                            <td>{{ matiere.coefficient || '-' }}</td>
                                            <td>{{ matiere.ecole?.nom || '-' }}</td>
                                            <td><span class="badge" :class="isActif(matiere) ? 'bg-success' : 'bg-danger'">{{ isActif(matiere) ? 'Actif' : 'Inactif' }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.matieres.show', matiere.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                                    <Link :href="route('academique.matieres.edit', matiere.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                                    <button @click="confirmDelete(matiere)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                                    <button v-if="isActif(matiere)" @click="confirmDeactivate(matiere)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                                    <button v-else @click="confirmActivate(matiere)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="6" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mobile-card-list">
                            <template v-if="matieres?.data && matieres?.data.length > 0">
                                <div v-for="matiere in matieres?.data" :key="'m-' + matiere.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row"><span class="mobile-card-label">{{ t('fields.code') }}</span><span class="mobile-card-value">{{ matiere.code || '-' }}</span></div>
                                        <div class="mobile-card-row"><span class="mobile-card-label">{{ t('fields.libelle') }}</span><span class="mobile-card-value">{{ matiere.libelle || '-' }}</span></div>
                                        <div class="mobile-card-row"><span class="mobile-card-label">{{ t('fields.coefficient') }}</span><span class="mobile-card-value">{{ matiere.coefficient || '-' }}</span></div>
                                        <div class="mobile-card-row"><span class="mobile-card-label">{{ t('fields.ecole') }}</span><span class="mobile-card-value">{{ matiere.ecole?.nom || '-' }}</span></div>
                                        <div class="mobile-card-row"><span class="mobile-card-label">{{ t('fields.status') }}</span><span class="mobile-card-value"><span class="badge" :class="isActif(matiere) ? 'bg-success' : 'bg-danger'">{{ isActif(matiere) ? 'Actif' : 'Inactif' }}</span></span></div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('academique.matieres.show', matiere.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('academique.matieres.edit', matiere.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(matiere)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="isActif(matiere)" @click="confirmDeactivate(matiere)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(matiere)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>
                        <Pagination :data="matieres" />
                    </div>
                </div>
            </div>
        </div>
        <ConfirmModal :show="showDeleteModal" :title="deleteMode ? t('messages.confirm.delete.title') : (deactivateMode ? t('messages.confirm.deactivate.title') : t('messages.confirm.activate.title'))" :message="deleteMode ? t('messages.confirm.delete.message') : (deactivateMode ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message'))" :sub-message="deleteMode ? t('messages.confirm.delete.warning') : ''" @close="closeModal" @confirm="deleteMode ? deleteItem() : toggleStatus()" :confirm-text="deleteMode ? t('actions.delete') : (deactivateMode ? t('actions.deactivate') : t('actions.activate'))" :confirm-class="deleteMode ? 'btn-danger' : (deactivateMode ? 'btn-danger' : 'btn-success')" />
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
