<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
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
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    maintenances: Object,
    filters: Object,
});

const deleteMode = ref(false);
const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
});

const statutOptions = [
    { id: 'planifiee', libelle: 'Planifiée' },
    { id: 'en_cours', libelle: 'En cours' },
    { id: 'completee', libelle: 'Complétée' },
    { id: 'annulee', libelle: 'Annulée' },
];

const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher...', icon: 'fa-search', width: '220px' },
    { key: 'statut', type: 'select', placeholder: 'Statut', options: statutOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];

let searchTimeout;

const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};

const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const search = () => {
    router.get(route('maintenances-equipements.index'), searchFilters.value, {
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
    router.get(route('maintenances-equipements.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('maintenances-equipements.destroy', itemToDelete.value.id), {
            method: 'delete',
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
            },
            onFinish: () => hideLoader(),
        });
    }
};

const closeModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
    deleteMode.value = false;
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
    <Head :title="t('common.maintenance') || 'Maintenance Équipement'" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('common.maintenance') || 'Maintenance Équipement' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('maintenances-equipements.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Alert Message -->
            <AlertMessage />

            <div class="row m-0">
                <!-- Filtres de recherche -->
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters"></FilterBar>

                <!-- Tableau -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.titre') || 'Titre' }}</th>
                                        <th>{{ t('fields.date_debut') || 'Date Début' }}</th>
                                        <th>{{ t('fields.date_fin') || 'Date Fin' }}</th>
                                        <th>{{ t('fields.statut') || 'Statut' }}</th>
                                        <th>{{ t('common.equipement') || 'Équipement' }}</th>
                                        <th>{{ t('common.technicien') || 'Technicien' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="maintenances?.data && maintenances?.data.length > 0">
                                        <tr v-for="maintenance in maintenances?.data" :key="maintenance.id">
                                            <td>{{ maintenance.titre }}</td>
                                            <td>{{ maintenance.date_debut }}</td>
                                            <td>{{ maintenance.date_fin || '-' }}</td>
                                            <td>
                                                <span class="badge" :class="['planifiee'].includes(maintenance.statut) ? 'bg-info' : ['en_cours'].includes(maintenance.statut) ? 'bg-warning' : ['completee'].includes(maintenance.statut) ? 'bg-success' : 'bg-danger'">
                                                    {{ maintenance.statut }}
                                                </span>
                                            </td>
                                            <td>{{ maintenance.equipement || '-' }}</td>
                                            <td>{{ maintenance.technicien || '-' }}</td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('maintenances-equipements.show', maintenance.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('maintenances-equipements.edit', maintenance.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(maintenance)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
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
                            <template v-if="maintenances?.data && maintenances?.data.length > 0">
                                <div v-for="maintenance in maintenances?.data" :key="'m-' + maintenance.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.titre') || 'Titre' }}</span>
                                            <span class="mobile-card-value">{{ maintenance.titre }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.date_debut') || 'Date Début' }}</span>
                                            <span class="mobile-card-value">{{ maintenance.date_debut }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.date_fin') || 'Date Fin' }}</span>
                                            <span class="mobile-card-value">{{ maintenance.date_fin || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.statut') || 'Statut' }}</span>
                                            <span class="mobile-card-value">
                                                <span class="badge" :class="['planifiee'].includes(maintenance.statut) ? 'bg-info' : ['en_cours'].includes(maintenance.statut) ? 'bg-warning' : ['completee'].includes(maintenance.statut) ? 'bg-success' : 'bg-danger'">
                                                    {{ maintenance.statut }}
                                                </span>
                                            </span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.equipement') || 'Équipement' }}</span>
                                            <span class="mobile-card-value">{{ maintenance.equipement || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.technicien') || 'Technicien' }}</span>
                                            <span class="mobile-card-value">{{ maintenance.technicien || '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('maintenances-equipements.show', maintenance.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('maintenances-equipements.edit', maintenance.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(maintenance)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="maintenances" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="t('messages.confirm.delete.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            @close="closeModal"
            @confirm="deleteItem()"
            :confirm-text="t('actions.delete')"
            confirm-class="btn-danger"
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

<style scoped>
</style>
