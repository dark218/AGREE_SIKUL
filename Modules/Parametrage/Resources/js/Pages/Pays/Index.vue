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
import { useLoader } from '@/Composables/useLoader';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    pays: Object,
    filters: Object,
});
const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);
// Filtres de recherche
const searchFilters = ref({
    code: props.filters?.code || '',
    libelle: props.filters?.libelle || '',
    etat: props.filters?.etat || '',
    });
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const filterFields = [
    { key: 'code', type: 'text', placeholder: 'Code', icon: 'fa-search', width: '220px' },
    { key: 'libelle', type: 'text', placeholder: 'Libellé', width: '220px' },
    { key: 'etat', type: 'select', placeholder: 'Statut', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
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
// Modal de suppression
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const search = () => {
    router.get(route('parametrage.pays.index'), searchFilters.value, {
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
  router.get(route('parametrage.pays.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('parametrage.pays.destroy', itemToDelete.value?.id), { method: 'delete', preserveScroll: true,
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
        } else {
            showDeleteLoader();
        }
        router.visit(route('parametrage.pays.statut', itemToDelete.value?.id), { method: 'put',
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
// Make pays accessible in template from props or page
const pays = props.pays || page.props.pays;
// Real-time search with debounce
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
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('pays-create')">
                        <Link :href="route('parametrage.pays.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <!-- Alert Message -->
            <AlertMessage />
            <div class="row m-0">
                <!-- Filtres de recherche -->
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters">
                </FilterBar>
                <!-- Tableau -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.code') || 'Code' }}</th>
                                        <th>{{ t('fields.code_3_chars') || 'Code 3 Chars' }}</th>
                                        <th>{{ t('fields.code_2_chars') || 'Code 2 Chars' }}</th>
                                        <th>{{ t('fields.label') || 'Libellé' }}</th>
                                        <th>{{ t('fields.capitale') || 'Capitale' }}</th>
                                        <th>{{ t('fields.continent') || 'Continent' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="pays?.data && pays?.data.length > 0">
                                        <tr v-for="pays in pays?.data" :key="pays.id">
                                            <td>{{ pays.code || '' }}</td>
                                            <td>{{ pays.code_3_chars || '' }}</td>
                                            <td>{{ pays.code_2_chars || '' }}</td>
                                            <td>{{ pays.libelle || '' }}</td>
                                            <td>{{ pays.capitale || '' }}</td>
                                            <td>{{ pays.continent || '' }}</td>
                                            <td><span class="badge" :class="pays.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + pays.etat) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('parametrage.pays.show', pays?.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('parametrage.pays.edit', pays?.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(pays)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="pays.etat === 'actif' || pays.statut === 'actif'" @click="confirmDeactivate(pays)" class="btn btn-danger" :title="t('actions.deactivate')">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button v-else @click="confirmActivate(pays)" class="btn btn-success" :title="t('actions.activate')">
                                                        <span class="fa fa-check"></span>
                                                    </button>
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
                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="pays?.data && pays?.data.length > 0">
                                <div v-for="pays in pays?.data" :key="'m-' + pays.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.code') || 'Code' }}</span>
                                            <span class="mobile-card-value">{{ pays.code || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.code_3_chars') || 'Code 3 Chars' }}</span>
                                            <span class="mobile-card-value">{{ pays.code_3_chars || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.code_2_chars') || 'Code 2 Chars' }}</span>
                                            <span class="mobile-card-value">{{ pays.code_2_chars || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.label') || 'Libellé' }}</span>
                                            <span class="mobile-card-value">{{ pays.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.capitale') || 'Capitale' }}</span>
                                            <span class="mobile-card-value">{{ pays.capitale || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.continent') || 'Continent' }}</span>
                                            <span class="mobile-card-value">{{ pays.continent || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="pays.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + pays.etat) }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('parametrage.pays.show', pays?.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('parametrage.pays.edit', pays?.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(pays)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="pays.etat === 'actif' || pays.statut === 'actif'" @click="confirmDeactivate(pays)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(pays)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>
                        <!-- Pagination -->
                        <Pagination :data="pays" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="deleteMode ? t('messages.confirm.delete.title') : (deactivateMode ? t('messages.confirm.deactivate.title') : t('messages.confirm.activate.title'))"
            :message="deleteMode ? t('messages.confirm.delete.message') : (deactivateMode ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message'))"
            :sub-message="deleteMode ? t('messages.confirm.delete.warning') : ''"
            @close="closeModal"
            @confirm="deleteMode ? deleteItem() : toggleStatus()"
            :confirm-text="deleteMode ? t('actions.delete') : (deactivateMode ? t('actions.deactivate') : t('actions.activate'))"
            :confirm-class="deleteMode ? 'btn-danger' : (deactivateMode ? 'btn-danger' : 'btn-success')"
        />
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
