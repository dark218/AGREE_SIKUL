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
    periodesColaires: Object,
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
    router.get(route('parametrage.periodes_colaires.index'), searchFilters.value, {
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
  router.get(route('parametrage.periodes_colaires.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('parametrage.periodes_colaires.destroy', itemToDelete.value?.id), { method: 'delete', preserveScroll: true,
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
        router.visit(route('parametrage.periodes_colaires.statut', itemToDelete.value?.id), { method: 'put',
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
                    <div class="dashboard-btn" v-if="can('periodes_colaires-create')">
                        <Link :href="route('parametrage.periodes_colaires.create')" class="btn btn-primary">
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
                                        <th>{{ t('fields.type_periode') || 'Type' }}</th>
                                        <th>{{ t('common.annee_scolaire') || 'Année Scolaire' }}</th>
                                        <th>{{ t('common.ecole') || 'École' }}</th>
                                        <th>Dates</th>
                                        <th>{{ t('fields.numero_ordre') || 'Ordre' }}</th>
                                        <th>{{ t('fields.est_periode_evaluation') || 'Évaluation' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="periodesColaires?.data && periodesColaires?.data.length > 0">
                                        <tr v-for="periode in periodesColaires?.data" :key="periode.id">
                                            <td><small>{{ periode.code || '-' }}</small></td>
                                            <td>{{ periode.libelle || '-' }}</td>
                                            <td><small>{{ periode.type_periode || '-' }}</small></td>
                                            <td><small>{{ periode.annee_scolaire?.libelle || '-' }}</small></td>
                                            <td><small>{{ periode.ecole?.nom || '-' }}</small></td>
                                            <td>
                                                <small v-if="periode.date_debut">
                                                    {{ new Date(periode.date_debut).toLocaleDateString('fr-FR') }} - {{ new Date(periode.date_fin).toLocaleDateString('fr-FR') }}
                                                </small>
                                                <small v-else>-</small>
                                            </td>
                                            <td><small>{{ periode.numero_ordre || '-' }}</small></td>
                                            <td><small>{{ periode.est_periode_evaluation ? '✓' : '-' }}</small></td>
                                            <td><span class="badge" :class="periode.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ periode.etat === 'actif' ? 'Actif' : 'Inactif' }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('parametrage.periodes_colaires.show', periode?.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('parametrage.periodes_colaires.edit', periode?.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(periode)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="periode.etat === 'actif' || periode.statut === 'actif'" @click="confirmDeactivate(periode)" class="btn btn-danger" :title="t('actions.deactivate')">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button v-else @click="confirmActivate(periode)" class="btn btn-success" :title="t('actions.activate')">
                                                        <span class="fa fa-check"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="10" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="periodesColaires?.data && periodesColaires?.data.length > 0">
                                <div v-for="periode in periodesColaires?.data" :key="'m-' + periode.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.code') || 'Code' }}</span>
                                            <span class="mobile-card-value"><small>{{ periode.code || '-' }}</small></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.label') || 'Libellé' }}</span>
                                            <span class="mobile-card-value">{{ periode.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.type_periode') || 'Type' }}</span>
                                            <span class="mobile-card-value"><small>{{ periode.type_periode || '-' }}</small></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.annee_scolaire') || 'Année' }}</span>
                                            <span class="mobile-card-value"><small>{{ periode.annee_scolaire?.libelle || '-' }}</small></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.ecole') || 'École' }}</span>
                                            <span class="mobile-card-value"><small>{{ periode.ecole?.nom || '-' }}</small></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Dates</span>
                                            <span class="mobile-card-value">
                                                <small v-if="periode.date_debut">
                                                    {{ new Date(periode.date_debut).toLocaleDateString('fr-FR') }} - {{ new Date(periode.date_fin).toLocaleDateString('fr-FR') }}
                                                </small>
                                                <small v-else>-</small>
                                            </span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.numero_ordre') || 'Ordre' }}</span>
                                            <span class="mobile-card-value"><small>{{ periode.numero_ordre || '-' }}</small></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.est_periode_evaluation') || 'Évaluation' }}</span>
                                            <span class="mobile-card-value"><small>{{ periode.est_periode_evaluation ? '✓' : '-' }}</small></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="periode.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ periode.etat === 'actif' ? 'Actif' : 'Inactif' }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('parametrage.periodes_colaires.show', periode?.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('parametrage.periodes_colaires.edit', periode?.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(periode)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="periode.etat === 'actif' || periode.statut === 'actif'" @click="confirmDeactivate(periode)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(periode)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>
                        <!-- Pagination -->
                        <Pagination :data="periodesColaires" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation -->
        <ConfirmModal
            :show="showDeleteModal"
            @update:show="showDeleteModal = $event"
            :title="deleteMode ? t('messages.confirm.delete.title') : (deactivateMode ? 'Désactiver' : 'Activer')"
            :message="deleteMode ? 'Êtes-vous sûr de vouloir supprimer cet élément?' : (deactivateMode ? 'Êtes-vous sûr de vouloir désactiver cet élément?' : 'Êtes-vous sûr de vouloir activer cet élément?')"
            :sub-message="t('messages.confirm.delete.warning')"
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
