<script setup>
import { ref, watch, computed } from 'vue';
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

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toISOString().split('T')[0];
};

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    transport: Object,
    ecoles: Array,
    anneesScolaires: Array,
    filters: Object,
});

const deleteMode = ref(false);
const searchFilters = ref({
    zone: props.filters?.zone || '',
    ligne: props.filters?.ligne || '',
    ecole_id: props.filters?.ecole_id || '',
    annee_scolaire_id: props.filters?.annee_scolaire_id || '',
    etat: props.filters?.etat || '',
});

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const filterFields = computed(() => [
    { key: 'annee_scolaire_id', type: 'select', placeholder: 'Année scolaire', options: props.anneesScolaires, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'etat', type: 'select', placeholder: 'État', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
]);

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
    router.get(route('services-transport.index'), searchFilters.value, {
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
    router.get(route('services-transport.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('services-transport.destroy', itemToDelete.value.id), {
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
    <Head :title="t('modules.services.services-transport.index') || 'Services de Transport'" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.services.services-transport.index') || 'Services de Transport' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('services-transport.create')" class="btn btn-primary">
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
                                        <th>{{ t('fields.zone') || 'Zone' }}</th>
                                        <th>{{ t('fields.ligne') || 'Ligne' }}</th>
                                        <th>{{ t('fields.point_depart') || 'Point de départ' }}</th>
                                        <th>{{ t('fields.ecole') || 'École' }}</th>
                                        <th>{{ t('fields.tarif_mensuel') || 'Tarif mensuel' }}</th>
                                        <th>{{ t('fields.tarif_trimestriel') || 'Tarif trimestriel' }}</th>
                                        <th>{{ t('fields.tarif_semestriel') || 'Tarif semestriel' }}</th>
                                        <th>{{ t('fields.tarif_annuel') || 'Tarif annuel' }}</th>
                                        <th>{{ t('fields.date_debut') || 'Date début' }}</th>
                                        <th>{{ t('fields.date_fin') || 'Date fin' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="transport?.data && transport?.data.length > 0">
                                        <tr v-for="service in transport?.data" :key="service.id">
                                            <td>{{ service.zone || '-' }}</td>
                                            <td>{{ service.ligne || '-' }}</td>
                                            <td>{{ service.point_depart || '-' }}</td>
                                            <td>{{ service.ecole?.nom || '-' }}</td>
                                            <td>{{ service.tarif_mensuel || '-' }}</td>
                                            <td>{{ service.tarif_trimestriel || '-' }}</td>
                                            <td>{{ service.tarif_semestriel || '-' }}</td>
                                            <td>{{ service.tarif_annuel || '-' }}</td>
                                            <td>{{ formatDate(service.date_debut) }}</td>
                                            <td>{{ formatDate(service.date_fin) }}</td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('services-transport.show', service.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('services-transport.edit', service.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(service)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="11" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="transport?.data && transport?.data.length > 0">
                                <div v-for="service in transport?.data" :key="'m-' + service.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.zone') || 'Zone' }}</span>
                                            <span class="mobile-card-value">{{ service.zone || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.ligne') || 'Ligne' }}</span>
                                            <span class="mobile-card-value">{{ service.ligne || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.point_depart') || 'Point de départ' }}</span>
                                            <span class="mobile-card-value">{{ service.point_depart || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.ecole') || 'École' }}</span>
                                            <span class="mobile-card-value">{{ service.ecole?.nom || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.tarif_mensuel') || 'Tarif mensuel' }}</span>
                                            <span class="mobile-card-value">{{ service.tarif_mensuel || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.date_debut') || 'Date début' }}</span>
                                            <span class="mobile-card-value">{{ formatDate(service.date_debut) }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.date_fin') || 'Date fin' }}</span>
                                            <span class="mobile-card-value">{{ formatDate(service.date_fin) }}</span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('services-transport.show', service.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('services-transport.edit', service.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(service)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="transport" />
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
