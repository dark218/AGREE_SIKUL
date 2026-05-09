<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    menus: Object,
    servicesCantines: Array,
    filters: Object,
});

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const searchFilters = ref({
    service_cantine_id: props.filters?.service_cantine_id || '',
    week_start_date: props.filters?.week_start_date || '',
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

const search = () => {
    router.get(route('menu-cantines.index'), searchFilters.value, {
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
    router.get(route('menu-cantines.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('menu-cantines.destroy', itemToDelete.value.id), {
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
    <Head :title="t('common.menus_cantine') || 'Menus de Cantine'" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('common.menus_cantine') || 'Menus de Cantine' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('menu-cantines.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Alert Message -->
            <AlertMessage />

            <div class="row m-0">
                <!-- Filters -->
                <form @submit.prevent="search" style="display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap; width: 100%; margin-bottom: 20px;">
                    <div style="width: 200px;">
                        <input
                            v-model="searchFilters.week_start_date"
                            type="date"
                            class="form-control"
                            @input="performSearch"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.service_cantine_id"
                            :options="servicesCantines"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('common.service_cantine') || 'Service'"
                            class="form-control-sm"
                            style="height: 45px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.statut"
                            :options="statusOptions"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('common.statut') || 'État'"
                            class="form-control-sm"
                            style="height: 45px; width: 100%;"
                        />
                    </div>
                    <div style="display: flex; gap: 4px;">
                        <button type="submit" class="btn btn-primary btn-sm" style="height: 45px; padding: 0 10px;">
                            <i class="fa fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" @click="resetFilters" style="height: 45px; padding: 0 10px;">
                            <i class="fa fa-redo"></i>
                        </button>
                    </div>
                </form>

                <!-- Table -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('common.week_name') || 'Semaine' }}</th>
                                        <th>{{ t('common.week_start_date') || 'Début' }}</th>
                                        <th>{{ t('common.service_cantine') || 'Service' }}</th>
                                        <th>{{ t('common.statut') || 'État' }}</th>
                                        <th class="fit">{{ t('common.actions') || 'Actions' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="menus?.data && menus?.data.length > 0">
                                        <tr v-for="menu in menus?.data" :key="menu.id">
                                            <td>{{ menu.week_name }}</td>
                                            <td>{{ menu.week_start_date }}</td>
                                            <td>{{ menu.service_cantine_nom }}</td>
                                            <td>
                                                <span class="badge" :class="menu.statut === 'actif' ? 'badge-success' : 'badge-secondary'">
                                                    {{ menu.statut === 'actif' ? 'Actif' : 'Inactif' }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('menu-cantines.show', menu.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('menu-cantines.edit', menu.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <Link :href="route('menu-cantines.pdf', menu.id)" class="btn btn-success" :title="t('actions.download')" target="_blank">
                                                        <span class="fa fa-file-pdf"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(menu)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="5" class="text-center">{{ t('common.emptyList') || 'Aucune donnée' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="menus" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="t('messages.confirm.delete.message')"
            @close="closeModal"
            @confirm="deleteItem()"
            :confirm-text="t('actions.delete')"
            confirm-class="btn-danger"
        />

        <!-- Loader -->
        <FullPageLoader :show="isLoading" :message="loaderMessage" />
    </div>
</template>

<style scoped>
.custom-table {
    width: 100%;
}

.custom-table th {
    background-color: #f1f5f9;
    border-bottom: 2px solid var(--color-primary);
    padding: 12px;
    font-weight: 600;
    color: #1e293b;
}

.custom-table td {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.badge-success {
    background-color: #22c55e;
}

.badge-secondary {
    background-color: #94a3b8;
}

.action-buttons {
    display: flex;
    gap: 6px;
}

.btn {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 4px;
}
</style>
