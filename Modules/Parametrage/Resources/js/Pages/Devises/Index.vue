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
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    devises: Object,
    filters: Object,
    pays: Array,
});
// Make prop accessible in template
const devises = props.devises || page.props.devises;
const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);
// Filtres de recherche
const searchFilters = ref({
    code: props.filters?.code || '',
    libelle: props.filters?.libelle || '',
    code_iso: props.filters?.code_iso || '',
    symbol: props.filters?.symbol || '',
    exchange_rate: props.filters?.exchange_rate || '',
    pays_id: props.filters?.pays_id || '',
    etat: props.filters?.etat || '',
    });
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
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
    router.get(route('parametrage.devises.index'), searchFilters.value, {
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
  router.get(route('parametrage.devises.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('parametrage.devises.destroy', itemToDelete.value?.id), { method: 'delete', preserveScroll: true,
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
        router.visit(route('parametrage.devises.statut', itemToDelete.value?.id), { method: 'put',
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
                    <div class="dashboard-btn" v-if="can('devise-create')">
                        <Link :href="route('parametrage.devises.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <!-- Alert Message -->
            <AlertMessage />
            <div class="row m-0">
                <!-- Filtres de recherche -->
                <form @submit.prevent="search" style="display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap;">
                    <div style="width: 150px;">
                        <input
                            type="text"
                            v-model="searchFilters.code"
                            class="form-control form-control-sm"
                            :placeholder="t('fields.code')"
                            style="height: 32px; font-size: 13px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <input
                            type="text"
                            v-model="searchFilters.libelle"
                            class="form-control form-control-sm"
                            :placeholder="t('fields.label')"
                            style="height: 32px; font-size: 13px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <input
                            type="text"
                            v-model="searchFilters.code_iso"
                            class="form-control form-control-sm"
                            :placeholder="t('fields.code_iso') || 'ISO'"
                            style="height: 32px; font-size: 13px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <input
                            type="text"
                            v-model="searchFilters.symbol"
                            class="form-control form-control-sm"
                            :placeholder="t('fields.symbol') || 'Symbole'"
                            style="height: 32px; font-size: 13px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.pays_id"
                            :options="props.pays"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.country') || 'Pays'"
                            class="form-control-sm"
                            style="height: 32px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.etat"
                            :options="statusOptions"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.status') || 'Statut'"
                            class="form-control-sm"
                            style="height: 32px; width: 100%;"
                        />
                    </div>
                    <div style="display: flex; gap: 4px;">
                        <button type="submit" class="btn btn-primary btn-sm" style="height: 32px; padding: 0 10px;">
                            <i class="fa fa-search"></i>
                        </button>
                        <button type="button" @click="resetFilters" class="btn btn-secondary btn-sm" style="height: 32px; padding: 0 10px;">
                            <i class="fa fa-redo"></i>
                        </button>
                    </div>
                </form>
                <!-- Tableau -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.code') || 'Code' }}</th>
                                        <th>{{ t('fields.code_iso') || 'ISO' }}</th>
                                        <th>{{ t('fields.symbol') || 'Symbole' }}</th>
                                        <th>{{ t('fields.label') || 'Libellé' }}</th>
                                        <th>{{ t('fields.exchange_rate') || 'Taux' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="devises?.data && devises?.data.length > 0">
                                        <tr v-for="devise in devises?.data" :key="devise.id">
                                            <td>{{ devise.code || '' }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ devise.code_iso || '-' }}</span>
                                                <span v-if="devise.is_default" class="badge bg-warning ms-1" title="Devise par défaut">★</span>
                                            </td>
                                            <td>{{ devise.symbol || '-' }}</td>
                                            <td>{{ devise.libelle || '' }}</td>
                                            <td>{{ devise.exchange_rate ? Number(devise.exchange_rate).toFixed(4) : '-' }}</td>
                                            <td><span class="badge" :class="devise.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ devise.etat === 'actif' ? t('common.actif') : t('common.inactif') }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <!-- Button 1: Voir (View) -->
                                                    <Link
                                                        :href="route('parametrage.devises.show', devise?.id)"
                                                        class="btn btn-secondary"
                                                        :title="t('actions.view')"
                                                    >
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <!-- Button 2: Modifier (Edit) -->
                                                    <Link
                                                        v-if="can('devise-edit')"
                                                        :href="route('parametrage.devises.edit', devise?.id)"
                                                        class="btn btn-primary"
                                                        :title="t('actions.edit')"
                                                    >
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <!-- Button 3: Supprimer (Delete) -->
                                                    <button
                                                        @click="confirmDelete(devise)"
                                                        class="btn btn-danger"
                                                        :title="t('actions.delete')"
                                                    >
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <!-- Button 4: Activer/Désactiver (Conditional) -->
                                                    <button
                                                        v-if="devise.etat === 'actif'"
                                                        @click="confirmDeactivate(devise)"
                                                        class="btn btn-danger"
                                                        :title="t('actions.deactivate')"
                                                    >
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button
                                                        v-else
                                                        @click="confirmActivate(devise)"
                                                        class="btn btn-success"
                                                        :title="t('actions.activate')"
                                                    >
                                                        <span class="fa fa-check"></span>
                                                    </button>
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
                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="devises?.data && devises?.data.length > 0">
                                <div v-for="devise in devises?.data" :key="'m-' + devise.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.code') || 'Code' }}</span>
                                            <span class="mobile-card-value">{{ devise.code || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.code_iso') || 'ISO' }}</span>
                                            <span class="mobile-card-value"><span class="badge bg-info">{{ devise.code_iso || '-' }}</span></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.symbol') || 'Symbole' }}</span>
                                            <span class="mobile-card-value">{{ devise.symbol || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.label') || 'Libellé' }}</span>
                                            <span class="mobile-card-value">{{ devise.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.exchange_rate') || 'Taux' }}</span>
                                            <span class="mobile-card-value">{{ devise.exchange_rate ? Number(devise.exchange_rate).toFixed(4) : '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="devise.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ devise.etat === 'actif' ? t('common.actif') : t('common.inactif') }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('parametrage.devises.show', devise?.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link v-if="can('devise-edit')" :href="route('parametrage.devises.edit', devise?.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(devise)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="devise.etat === 'actif'" @click="confirmDeactivate(devise)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(devise)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>
                        <!-- Pagination -->
                        <Pagination :data="devises" />
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
