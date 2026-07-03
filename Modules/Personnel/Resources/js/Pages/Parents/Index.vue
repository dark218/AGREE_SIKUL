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
import ApprenantsBadges from '@/Components/Common/ApprenantsBadges.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    parents: Object,
    ecoles: Array,
    filters: Object,
});

const deleteMode = ref(false);
const searchFilters = ref({
    ecole_id: props.filters?.ecole_id || '',
    etat: props.filters?.etat || '',
});

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
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
    router.get(route('parents.index'), searchFilters.value, {
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
    router.get(route('parents.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('parents.destroy', itemToDelete.value.id), {
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
    <Head :title="t('modules.personnel.parents.index') || 'Parents'" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.personnel.parents.index') || 'Parents' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('parents.create')" class="btn btn-primary">
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
                        <SearchableSelect
                            v-model="searchFilters.ecole_id"
                            :options="ecoles"
                            optionValue="id"
                            optionLabel="nom"
                            :placeholder="t('fields.ecole') || 'École'"
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
                            :placeholder="t('common.status') || 'État'"
                            class="form-control-sm"
                            style="height: 32px; width: 100%;"
                        />
                    </div>
                    <div style="display: flex; gap: 4px;">
                        <button type="submit" class="btn btn-primary btn-sm" style="height: 32px; padding: 0 10px;">
                            <i class="fa fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" @click="resetFilters" style="height: 32px; padding: 0 10px;">
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
                                        <th>Apprenants</th>
                                        <th>Père</th>
                                        <th>Mère</th>
                                        <th>{{ t('fields.ecole') || 'École' }}</th>
                                        <th>{{ t('common.status') || 'État' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="parents?.data && parents?.data.length > 0">
                                        <tr v-for="parent in parents?.data" :key="parent.id">
                                            <td>
                                                <ApprenantsBadges
                                                    :apprenants="parent.apprenants || (parent.apprenant ? [parent.apprenant] : [])"
                                                    mode="inline"
                                                />
                                            </td>
                                            <td>{{ parent.pere_nom }} {{ parent.pere_prenoms }}</td>
                                            <td>{{ parent.mere_nom }} {{ parent.mere_prenoms }}</td>
                                            <td>{{ parent.ecole?.nom || '-' }}</td>
                                            <td><span class="badge" :class="parent.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + parent.etat) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('parents.show', parent.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('parents.edit', parent.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(parent)" class="btn btn-danger" :title="t('actions.delete')">
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
                            <template v-if="parents?.data && parents?.data.length > 0">
                                <div v-for="parent in parents?.data" :key="'m-' + parent.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Apprenant</span>
                                            <span class="mobile-card-value">{{ parent.apprenant?.nom }} {{ parent.apprenant?.prenoms }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Père</span>
                                            <span class="mobile-card-value">{{ parent.pere_nom }} {{ parent.pere_prenoms }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Mère</span>
                                            <span class="mobile-card-value">{{ parent.mere_nom }} {{ parent.mere_prenoms }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.ecole') || 'École' }}</span>
                                            <span class="mobile-card-value">{{ parent.ecole?.nom || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.status') || 'État' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="parent.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + parent.etat) }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('parents.show', parent.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('parents.edit', parent.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(parent)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="parents" />
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
