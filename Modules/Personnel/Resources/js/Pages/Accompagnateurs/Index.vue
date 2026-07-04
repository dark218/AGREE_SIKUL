<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
import ApprenantsBadges from '@/Components/Common/ApprenantsBadges.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();

const props = defineProps({
    accompagnateurs: Object,
    ecoles: Array,
    filters: Object,
});

const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);

const searchFilters = ref({
    search: props.filters?.search || '',
    ecole_id: props.filters?.ecole_id || '',
    etat: props.filters?.etat || '',
});

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const filterFields = computed(() => [
    { key: 'search', type: 'text', placeholder: 'Rechercher...', icon: 'fa-search', width: '220px' },
    { key: 'ecole_id', type: 'select', placeholder: 'École', options: props.ecoles, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'etat', type: 'select', placeholder: 'État', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
]);

let searchTimeout;

const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const search = () => {
    router.get(route('accompagnateurs.index'), searchFilters.value, {
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
    router.get(route('accompagnateurs.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('accompagnateurs.destroy', itemToDelete.value.id), {
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
        router.visit(route('accompagnateurs.statut', itemToDelete.value.id), {
            method: 'put',
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
    <Head :title="page.props.title || 'Accompagnateurs'" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title || 'Accompagnateurs' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('accompagnateurs.create')" class="btn btn-primary">
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
                                        <th>{{ t('fields.ecole') || 'École' }}</th>
                                        <th>{{ t('fields.accompagnant1') || 'Accompagnant 1' }}</th>
                                        <th>Apprenants</th>
                                        <th>{{ t('fields.status') || 'État' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="accompagnateurs?.data && accompagnateurs?.data.length > 0">
                                        <tr v-for="accompagnateur in accompagnateurs?.data" :key="accompagnateur.id">
                                            <td>{{ accompagnateur.ecole?.nom || '-' }}</td>
                                            <td>{{ accompagnateur.accompagnant1_nom || '-' }} {{ accompagnateur.accompagnant1_prenoms || '' }}</td>
                                            <td>
                                                <ApprenantsBadges
                                                    :apprenants="accompagnateur.apprenants || []"
                                                    mode="inline"
                                                />
                                            </td>
                                            <td><span class="badge" :class="accompagnateur.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + accompagnateur.etat) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('accompagnateurs.show', accompagnateur.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('accompagnateurs.edit', accompagnateur.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(accompagnateur)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="accompagnateur.etat === 'actif'" @click="confirmDeactivate(accompagnateur)" class="btn btn-danger" :title="t('actions.deactivate')">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button v-else @click="confirmActivate(accompagnateur)" class="btn btn-success" :title="t('actions.activate')">
                                                        <span class="fa fa-check"></span>
                                                    </button>
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

                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="accompagnateurs?.data && accompagnateurs?.data.length > 0">
                                <div v-for="accompagnateur in accompagnateurs?.data" :key="'m-' + accompagnateur.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.ecole') || 'École' }}</span>
                                            <span class="mobile-card-value">{{ accompagnateur.ecole?.nom || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.accompagnant1') || 'Accompagnant 1' }}</span>
                                            <span class="mobile-card-value">{{ accompagnateur.accompagnant1_nom || '-' }} {{ accompagnateur.accompagnant1_prenoms || '' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.accompagnant2') || 'Accompagnant 2' }}</span>
                                            <span class="mobile-card-value">{{ accompagnateur.accompagnant2_nom || '-' }} {{ accompagnateur.accompagnant2_prenoms || '' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'État' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="accompagnateur.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + accompagnateur.etat) }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('accompagnateurs.show', accompagnateur.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('accompagnateurs.edit', accompagnateur.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(accompagnateur)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="accompagnateur.etat === 'actif'" @click="confirmDeactivate(accompagnateur)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(accompagnateur)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="accompagnateurs" />
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
