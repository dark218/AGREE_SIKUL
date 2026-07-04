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
    zones: Object,
    filters: Object,
});
// Make prop accessible in template
const zones = props.zones || page.props.zones;
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
    router.get(route('parametrage.zones.index'), searchFilters.value, {
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
  router.get(route('parametrage.zones.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('parametrage.zones.destroy', itemToDelete.value?.id), { method: 'delete', preserveScroll: true,
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
        router.visit(route('parametrage.zones.statut', itemToDelete.value?.id), { method: 'put',
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
const page = usePage();
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
                    <div class="dashboard-btn" v-if="can('zone-create')">
                        <Link :href="route('parametrage.zones.create')" class="btn btn-primary">
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
                                        <th>{{ t('fields.label') || 'Libellé' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="zones?.data && zones?.data.length > 0">
                                        <tr v-for="zone in zones?.data" :key="zone.id">
                                            <td>{{ zone.code || '' }}</td>
                                            <td>{{ zone.libelle || '' }}</td>
                                            <td><span class="badge" :class="zone.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + zone.etat) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('parametrage.zones.show', zone?.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                                    <Link :href="route('parametrage.zones.edit', zone?.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                                    <button @click="confirmDelete(zone)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                                    <button v-if="zone.etat === 'actif' || zone.statut === 'actif'" @click="confirmDeactivate(zone)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                                    <button v-else @click="confirmActivate(zone)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="4" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="zones?.data && zones?.data.length > 0">
                                <div v-for="zone in zones?.data" :key="'m-' + zone.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.code') || 'Code' }}</span>
                                            <span class="mobile-card-value">{{ zone.code || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.label') || 'Libellé' }}</span>
                                            <span class="mobile-card-value">{{ zone.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="zone.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + zone.etat) }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('parametrage.zones.show', zone?.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('parametrage.zones.edit', zone?.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(zone)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="zone.etat === 'actif' || zone.statut === 'actif'" @click="confirmDeactivate(zone)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(zone)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>
                        <!-- Pagination -->
                        <Pagination :data="zones" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="deleteMode ? (t('messages.confirm.delete.title') || 'Supprimer ?') : (deactivateMode ? (t('messages.confirm.deactivate.title') || 'Désactiver ?') : (t('messages.confirm.activate.title') || 'Activer ?'))"
            :message="deleteMode ? (t('messages.confirm.delete.message') || 'Êtes-vous sûr de vouloir supprimer cet élément ?') : (deactivateMode || activateMode) ? (itemToDelete?.etat === 'actif' ? t('messages.confirm.deactivate.message') || 'Êtes-vous sûr de vouloir désactiver cet élément ?' : t('messages.confirm.activate.message') || 'Êtes-vous sûr de vouloir activer cet élément ?') : ''"
            :sub-message="deleteMode ? (t('messages.confirm.delete.warning') || 'Cette action ne peut pas être annulée.') : (deactivateMode || activateMode) ? (itemToDelete?.etat === 'actif' ? t('messages.confirm.deactivate.warning') : t('messages.confirm.activate.warning')) : ''"
            @close="showDeleteModal = false"
            @confirm="deleteMode ? deleteItem() : toggleStatus()"
            :confirm-text="deleteMode ? 'Supprimer' : (deactivateMode ? 'Désactiver' : 'Activer')"
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
