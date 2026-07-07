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
import FilterBar from '@/Components/Common/FilterBar.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { can } = usePermissions();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    title: String,
    personnelsAdministratifs: Object,
    filters: Object,
});

const showDeleteModal = ref(false);
const selectedItem = ref(null);

// Filtres de recherche
const searchFilters = ref({
    search: props.filters?.search || '',
});

const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher...', icon: 'fa-search', width: '220px' },
];

// Debounce timer
let searchTimeout;

const search = () => {
    router.get(route('personnels_administratifs.index'), searchFilters.value, {
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
    router.get(route('personnels_administratifs.index'));
};

const confirmDelete = (item) => {
    selectedItem.value = item;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (selectedItem.value) {
        showDeleteLoader();
        router.visit(route('personnels_administratifs.destroy', selectedItem.value.id), {
            method: 'delete',
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                selectedItem.value = null;
            },
            onFinish: () => hideLoader(),
        });
    }
};

const toggleStatus = (item) => {
    if (item) {
        showDeleteLoader();
        router.visit(route('personnels_administratifs.statut', item.id), {
            method: 'put',
            preserveScroll: true,
            onSuccess: () => {
                selectedItem.value = null;
            },
            onFinish: () => hideLoader(),
        });
    }
};

const closeModal = () => {
    showDeleteModal.value = false;
    selectedItem.value = null;
};

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
                    <div class="dashboard-btn" v-if="can('personnels-administratifs-create')">
                        <Link :href="route('personnels_administratifs.create')" class="btn btn-primary">
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
                                        <th>{{ t('common.nom') || 'Nom' }}</th>
                                        <th>{{ t('common.matricule') || 'Matricule' }}</th>
                                        <th>{{ t('common.poste') || 'Poste' }}</th>
                                        <th>{{ t('common.departement') || 'Département' }}</th>
                                        <th>{{ t('common.statut') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="personnelsAdministratifs?.data && personnelsAdministratifs?.data.length > 0">
                                        <tr v-for="item in personnelsAdministratifs?.data" :key="item.id">
                                            <td>
                                                <strong>{{ item.user?.prenoms }} {{ item.user?.nom }}</strong>
                                            </td>
                                            <td>{{ item.matricule || '-' }}</td>
                                            <td>{{ item.poste || '-' }}</td>
                                            <td>{{ item.departement || '-' }}</td>
                                            <td>
                                                <span class="badge" :class="item.deleted_at ? 'bg-secondary' : 'bg-success'">
                                                    {{ item.deleted_at ? t('common.inactif') || 'Inactif' : t('common.actif') || 'Actif' }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('personnels_administratifs.show', item.id)" class="btn btn-secondary btn-sm" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('personnels_administratifs.edit', item.id)" class="btn btn-primary btn-sm" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="toggleStatus(item)" class="btn btn-sm" :class="item.deleted_at ? 'btn-success' : 'btn-warning'" :title="item.deleted_at ? t('actions.activate') : t('actions.deactivate')">
                                                        <span :class="item.deleted_at ? 'fa fa-check' : 'fa fa-ban'"></span>
                                                    </button>
                                                    <button @click="confirmDelete(item)" class="btn btn-danger btn-sm" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
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

                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="personnelsAdministratifs?.data && personnelsAdministratifs?.data.length > 0">
                                <div v-for="item in personnelsAdministratifs?.data" :key="'m-' + item.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.nom') || 'Nom' }}</span>
                                            <span class="mobile-card-value"><strong>{{ item.user?.prenoms }} {{ item.user?.nom }}</strong></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.matricule') || 'Matricule' }}</span>
                                            <span class="mobile-card-value">{{ item.matricule || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.poste') || 'Poste' }}</span>
                                            <span class="mobile-card-value">{{ item.poste || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.departement') || 'Département' }}</span>
                                            <span class="mobile-card-value">{{ item.departement || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.statut') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="item.deleted_at ? 'bg-secondary' : 'bg-success'">{{ item.deleted_at ? t('common.inactif') || 'Inactif' : t('common.actif') || 'Actif' }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('personnels_administratifs.show', item.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('personnels_administratifs.edit', item.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="toggleStatus(item)" class="btn" :class="item.deleted_at ? 'btn-success' : 'btn-warning'"><span :class="item.deleted_at ? 'fa fa-check' : 'fa fa-ban'"></span></button>
                                        <button @click="confirmDelete(item)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="personnelsAdministratifs" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="t('messages.confirm.delete.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            @close="closeModal"
            @confirm="deleteItem"
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
.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.btn-group-sm .btn {
    padding: 0.375rem 0.5rem;
    font-size: 0.85rem;
}

.card {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.badge {
    font-weight: 600;
    padding: 0.35em 0.65em;
}
</style>
