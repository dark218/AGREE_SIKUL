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
const { isLoading, loaderMessage, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    menus: Object,
    filters: Object,
});

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher…', icon: 'fa-search', width: '220px' },
    { key: 'statut', type: 'select', placeholder: 'État', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];

const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
});

let searchTimeout;

const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};

const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const deleteMode = ref(false);
const togglingStatus = ref(null);

const search = () => {
    router.get(route('menus.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const toggleStatus = (item) => {
    console.log('📍 toggleStatus called with item:', item);
    console.log('📍 item.id:', item.id);

    togglingStatus.value = item.id;
    const url = route('menus.statut', item.id);
    console.log('📍 Generated URL:', url);

    router.put(url, {}, {
        preserveScroll: true,
        onFinish: () => {
            togglingStatus.value = null;
        },
    });
};

const downloadPdf = (item) => {
    window.location.href = route('menus.pdf', item.id);
};

const resetFilters = () => {
    const emptyFilters = {};
    Object.keys(searchFilters.value).forEach(key => {
        emptyFilters[key] = '';
    });
    searchFilters.value = emptyFilters;
    router.get(route('menus.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('menus.destroy', itemToDelete.value.id), {
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
    <Head :title="t('common.menu')" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('common.menu') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('menus.create')" class="btn btn-primary">
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
                                        <th>{{ t('common.statut') || 'État' }}</th>
                                        <th class="fit">{{ t('common.actions') || 'Actions' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="menus?.data && menus?.data.length > 0">
                                        <tr v-for="menu in menus?.data" :key="menu.id">
                                            <td>{{ menu.week_name }}</td>
                                            <td>
                                                <span class="badge" :class="menu.statut === 'actif' ? 'badge-success' : 'badge-secondary'">
                                                    {{ menu.statut === 'actif' ? 'Actif' : 'Inactif' }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <button @click="downloadPdf(menu)" class="btn btn-info" :title="t('actions.download_pdf') || 'Télécharger PDF'">
                                                        <span class="fa fa-file-pdf"></span>
                                                    </button>
                                                    <button @click="toggleStatus(menu)" :disabled="togglingStatus === menu.id" class="btn" :class="menu.statut === 'actif' ? 'btn-warning' : 'btn-success'" :title="menu.statut === 'actif' ? t('actions.deactivate') : t('actions.activate')">
                                                        <span :class="menu.statut === 'actif' ? 'fa fa-ban' : 'fa fa-check'"></span>
                                                    </button>
                                                    <Link :href="route('menus.show', menu.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('menus.edit', menu.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(menu)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="3" class="text-center">{{ t('common.emptyList') || 'Aucune donnée' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="menus?.data && menus?.data.length > 0">
                                <div v-for="menu in menus?.data" :key="'m-' + menu.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.nom') || 'Nom' }}</span>
                                            <span class="mobile-card-value">{{ menu.week_name }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.statut') || 'État' }}</span>
                                            <span class="mobile-card-value">
                                                <span class="badge" :class="menu.statut === 'actif' ? 'badge-success' : 'badge-secondary'">
                                                    {{ menu.statut === 'actif' ? 'Actif' : 'Inactif' }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <button @click="downloadPdf(menu)" class="btn btn-info"><span class="fa fa-file-pdf"></span></button>
                                        <button @click="toggleStatus(menu)" :disabled="togglingStatus === menu.id" class="btn" :class="menu.statut === 'actif' ? 'btn-warning' : 'btn-success'"><span :class="menu.statut === 'actif' ? 'fa fa-ban' : 'fa fa-check'"></span></button>
                                        <Link :href="route('menus.show', menu.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('menus.edit', menu.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(menu)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') || 'Aucune donnée' }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="menus" />
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
        />
    </div>
</template>
<style scoped>
/* Styles inherited from modules.css */
</style>
