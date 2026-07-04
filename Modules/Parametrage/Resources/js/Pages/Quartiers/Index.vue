<script setup>
import { ref, watch, computed } from 'vue';
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
    quartiers: Object,
    filters: Object,
    communes: {
        type: Array,
        default: () => [],
    },
    departements: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Array,
        default: () => [],
    },
    pays: {
        type: Array,
        default: () => [],
    },
});

// Make prop accessible in template
const quartiers = props.quartiers || page.props.quartiers;

const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);

// Filtres de recherche
const searchFilters = ref({
    code: props.filters?.code || '',
    libelle: props.filters?.libelle || '',
    commune_id: props.filters?.commune_id || '',
    departement_id: props.filters?.departement_id || '',
    region_id: props.filters?.region_id || '',
    pays_id: props.filters?.pays_id || '',
    etat: props.filters?.etat || '',
});

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const filterFields = computed(() => [
    { key: 'code', type: 'text', placeholder: 'Code', icon: 'fa-search', width: '220px' },
    { key: 'libelle', type: 'text', placeholder: 'Libellé', width: '220px' },
    { key: 'commune_id', type: 'select', placeholder: 'Toutes les communes', options: props.communes, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'departement_id', type: 'select', placeholder: 'Tous les départements', options: props.departements, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'region_id', type: 'select', placeholder: 'Toutes les régions', options: props.regions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'etat', type: 'select', placeholder: 'Statut', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
]);

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
    router.get(route('parametrage.quartiers.index'), searchFilters.value, {
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
  router.get(route('parametrage.quartiers.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('parametrage.quartiers.destroy', itemToDelete.value?.id), { method: 'delete', preserveScroll: true,
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
        router.visit(route('parametrage.quartiers.statut', itemToDelete.value?.id), { method: 'put',
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
                    <div class="dashboard-btn" v-if="can('quartiers-create')">
                        <Link :href="route('parametrage.quartiers.create')" class="btn btn-primary">
                            {{ t('actions.add') }}
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
                                        <th>{{ t('fields.commune') || 'Commune' }}</th>
                                        <th>{{ t('fields.departement') || 'Département' }}</th>
                                        <th>{{ t('fields.region') || 'Région' }}</th>
                                        <th>{{ t('fields.pays') || 'Pays' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="quartiers?.data && quartiers?.data.length > 0">
                                        <tr v-for="quartier in quartiers?.data" :key="quartier.id">
                                            <td>{{ quartier.code || '' }}</td>
                                            <td>{{ quartier.libelle || '' }}</td>
                                            <td>{{ quartier.commune?.libelle || '-' }}</td>
                                            <td>{{ quartier.departement?.libelle || '-' }}</td>
                                            <td>{{ quartier.region?.libelle || '-' }}</td>
                                            <td>{{ quartier.pays?.libelle || '-' }}</td>
                                            <td><span class="badge" :class="quartier.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + quartier.etat) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <!-- Button 1: Voir (View) -->
                                                    <Link
                                                        :href="route('parametrage.quartiers.show', quartier?.id)"
                                                        class="btn btn-secondary"
                                                        :title="t('actions.view')"
                                                    >
                                                        <span class="fa fa-eye"></span>
                                                    </Link>

                                                    <!-- Button 2: Modifier (Edit) -->
                                                    <Link
                                                        v-if="can('quartiers-edit')"
                                                        :href="route('parametrage.quartiers.edit', quartier?.id)"
                                                        class="btn btn-primary"
                                                        :title="t('actions.edit')"
                                                    >
                                                        <span class="fa fa-edit"></span>
                                                    </Link>

                                                    <!-- Button 3: Supprimer (Delete) -->
                                                    <button
                                                        @click="confirmDelete(quartier)"
                                                        class="btn btn-danger"
                                                        :title="t('actions.delete')"
                                                    >
                                                        <span class="fa fa-trash"></span>
                                                    </button>

                                                    <!-- Button 4: Activer/Désactiver (Conditional) -->
                                                    <button
                                                        v-if="quartier.etat === 'actif' || quartier.statut === 'actif'"
                                                        @click="confirmDeactivate(quartier)"
                                                        class="btn btn-danger"
                                                        :title="t('actions.deactivate')"
                                                    >
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button
                                                        v-else
                                                        @click="confirmActivate(quartier)"
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
                                        <td colspan="8" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="quartiers?.data && quartiers?.data.length > 0">
                                <div v-for="quartier in quartiers?.data" :key="'m-' + quartier.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.code') || 'Code' }}</span>
                                            <span class="mobile-card-value">{{ quartier.code || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.label') || 'Libellé' }}</span>
                                            <span class="mobile-card-value">{{ quartier.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.commune') || 'Commune' }}</span>
                                            <span class="mobile-card-value">{{ quartier.commune?.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.departement') || 'Département' }}</span>
                                            <span class="mobile-card-value">{{ quartier.departement?.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.region') || 'Région' }}</span>
                                            <span class="mobile-card-value">{{ quartier.region?.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.pays') || 'Pays' }}</span>
                                            <span class="mobile-card-value">{{ quartier.pays?.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="quartier.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + quartier.etat) }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('parametrage.quartiers.show', quartier?.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link v-if="can('quartiers-edit')" :href="route('parametrage.quartiers.edit', quartier?.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(quartier)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="quartier.etat === 'actif' || quartier.statut === 'actif'" @click="confirmDeactivate(quartier)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(quartier)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="quartiers" />
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
