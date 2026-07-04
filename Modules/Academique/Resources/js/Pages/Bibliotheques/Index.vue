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
    bibliotheques: Object,
    filters: Object,
});

const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);

const searchFilters = ref({
    search: props.filters?.search || '',
    type_manuel: props.filters?.type_manuel || '',
    sujet: props.filters?.sujet || '',
    etat: props.filters?.etat || '',
});

const statusOptions = [
    { id: 'actif', libelle: t('common.actif') },
    { id: 'inactif', libelle: t('common.inactif') },
];

const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher', icon: 'fa-search', width: '220px' },
    { key: 'sujet', type: 'text', placeholder: 'Sujet', width: '220px' },
    { key: 'type_manuel', type: 'text', placeholder: 'Type de manuel', width: '220px' },
    { key: 'etat', type: 'select', placeholder: 'Statut', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
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
    router.get(route('academique.bibliotheques.index'), searchFilters.value, {
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
    router.get(route('academique.bibliotheques.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('academique.bibliotheques.destroy', itemToDelete.value.id), {
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
        router.visit(route('academique.bibliotheques.statut', itemToDelete.value.id), {
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
    <Head :title="t('modules.academique.bibliotheques.index')" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.academique.bibliotheques.index') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('academique.bibliotheques.create')" class="btn btn-primary">
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
                                        <th>{{ t('fields.titre_manuel') }}</th>
                                        <th>{{ t('fields.sujet') }}</th>
                                        <th>{{ t('fields.type_manuel') }}</th>
                                        <th>{{ t('fields.auteurs') }}</th>
                                        <th>{{ t('fields.quantite') }}</th>
                                        <th>{{ t('fields.disponibles') }}</th>
                                        <th>{{ t('fields.etat') }}</th>
                                        <th>{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in props.bibliotheques.data" :key="item.id">
                                        <td>{{ item.titre_manuel || '-' }}</td>
                                        <td>{{ item.sujet || '-' }}</td>
                                        <td>{{ item.type_manuel || '-' }}</td>
                                        <td>{{ item.auteurs || '-' }}</td>
                                        <td>{{ item.quantite || 0 }}</td>
                                        <td>{{ item.disponibles || 0 }}</td>
                                        <td>
                                            <span v-if="item.etat === 'actif'" class="badge bg-success">{{ t('common.actif') }}</span>
                                            <span v-else class="badge bg-danger">{{ t('common.inactif') }}</span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                <Link :href="route('academique.bibliotheques.show', item.id)" class="btn btn-sm btn-secondary">
                                                    <i class="fa fa-eye"></i>
                                                </Link>
                                                <Link v-if="can('bibliotheques-edit')" :href="route('academique.bibliotheques.edit', item.id)" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i>
                                                </Link>
                                                <button v-if="can('bibliotheques-delete')" @click="confirmDelete(item)" class="btn btn-sm btn-danger">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                                <button v-if="item.etat === 'actif' && can('bibliotheques-delete')" @click="confirmDeactivate(item)" class="btn btn-sm btn-warning">
                                                    <i class="fa fa-ban"></i>
                                                </button>
                                                <button v-if="item.etat === 'inactif' && can('bibliotheques-delete')" @click="confirmActivate(item)" class="btn btn-sm btn-success">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <Pagination :data="props.bibliotheques" />
                </div>
            </div>
        </div>
    </div>

    <!-- ConfirmModal -->
    <ConfirmModal
        v-if="showDeleteModal"
        :title="deleteMode ? t('actions.delete') : (deactivateMode ? t('actions.deactivate') : t('actions.activate'))"
        :message="deleteMode ? t('messages.confirm_delete') : (deactivateMode ? t('messages.confirm_deactivate') : t('messages.confirm_activate'))"
        @confirm="deleteMode ? deleteItem() : toggleStatus()"
        @cancel="closeModal"
    />

    <!-- Full Page Loader -->
    <FullPageLoader v-if="isLoading" :message="loaderMessage" :submessage="loaderSubMessage" :variant="loaderVariant" />
</template>
