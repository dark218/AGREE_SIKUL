<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    categories: Object,
    filters: Object,
});

const deleteMode = ref(false);
const searchFilters = ref({
    search: props.filters?.search || '',
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
const showStatutModal = ref(false);
const itemToToggle = ref(null);

const search = () => {
    router.get(route('categories-fournitures.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    searchFilters.value = { search: '' };
    router.get(route('categories-fournitures.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('categories-fournitures.destroy', itemToDelete.value.id), {
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

const confirmStatut = (item) => {
    itemToToggle.value = item;
    showStatutModal.value = true;
};

const toggleStatut = () => {
    if (itemToToggle.value) {
        showDeleteLoader();
        router.visit(route('categories-fournitures.statut', itemToToggle.value.id), {
            method: 'put',
            preserveScroll: true,
            onSuccess: () => {
                showStatutModal.value = false;
                itemToToggle.value = null;
            },
            onFinish: () => hideLoader(),
        });
    }
};

const closeModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
    deleteMode.value = false;
    showStatutModal.value = false;
    itemToToggle.value = null;
};

watch(
    () => searchFilters.value,
    () => {
        performSearch();
    },
    { deep: true }
);
</script>

<template>
    <Head :title="t('common.categorie_fourniture') || 'Catégories Fournitures'" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('common.categorie_fourniture') || 'Catégories Fournitures' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('categories-fournitures.create')" class="btn btn-primary">
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
                    <div style="width: 250px;">
                        <input
                            v-model="searchFilters.search"
                            type="text"
                            class="form-control-sm"
                            :placeholder="t('fields.search') || 'Rechercher...'"
                            style="height: 32px; width: 100%;"
                            @input="performSearch"
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
                                        <th>{{ t('common.libelle') || 'Libellé' }}</th>
                                        <th>{{ t('fields.code') || 'Code' }}</th>
                                        <th>{{ t('fields.description') || 'Description' }}</th>
                                        <th>{{ t('fields.nombre_fournitures') || 'Fournitures' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="categories?.data && categories?.data.length > 0">
                                        <tr v-for="categorie in categories?.data" :key="categorie.id" :class="categorie.is_deleted ? 'table-row-deleted' : ''">
                                            <td><strong>{{ categorie.libelle }}</strong></td>
                                            <td>{{ categorie.code || '-' }}</td>
                                            <td>{{ categorie.description ? categorie.description.substring(0, 50) + '...' : '-' }}</td>
                                            <td><span class="badge bg-info">{{ categorie.nombre_fournitures }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('categories-fournitures.show', categorie.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('categories-fournitures.edit', categorie.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button
                                                        @click="confirmStatut(categorie)"
                                                        :class="['btn', categorie.statut === 'actif' ? 'btn-success' : 'btn-warning']"
                                                        :title="categorie.statut === 'actif' ? t('actions.deactivate') : t('actions.activate')"
                                                    >
                                                        <span :class="['fa', categorie.statut === 'actif' ? 'fa-check-circle' : 'fa-times-circle']"></span>
                                                    </button>
                                                    <button @click="confirmDelete(categorie)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
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
                            <template v-if="categories?.data && categories?.data.length > 0">
                                <div v-for="categorie in categories?.data" :key="'m-' + categorie.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.libelle') || 'Libellé' }}</span>
                                            <span class="mobile-card-value"><strong>{{ categorie.libelle }}</strong></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.code') || 'Code' }}</span>
                                            <span class="mobile-card-value">{{ categorie.code || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.nombre_fournitures') || 'Fournitures' }}</span>
                                            <span class="mobile-card-value"><span class="badge bg-info">{{ categorie.nombre_fournitures }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('categories-fournitures.show', categorie.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('categories-fournitures.edit', categorie.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button
                                            @click="confirmStatut(categorie)"
                                            :class="['btn', categorie.statut === 'actif' ? 'btn-success' : 'btn-warning']"
                                        >
                                            <span :class="['fa', categorie.statut === 'actif' ? 'fa-check-circle' : 'fa-times-circle']"></span>
                                        </button>
                                        <button @click="confirmDelete(categorie)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="categories" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation delete -->
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

        <!-- Modal de confirmation statut -->
        <ConfirmModal
            :show="showStatutModal"
            :title="t('messages.confirm.statut.title') || 'Changer le statut'"
            :message="itemToToggle ? (itemToToggle.statut === 'actif' ? t('messages.confirm.statut.deactivate') || 'Désactiver cette catégorie ?' : t('messages.confirm.statut.activate') || 'Activer cette catégorie ?') : ''"
            :sub-message="itemToToggle?.libelle || ''"
            @close="closeModal"
            @confirm="toggleStatut()"
            :confirm-text="itemToToggle?.statut === 'actif' ? t('actions.deactivate') || 'Désactiver' : t('actions.activate') || 'Activer'"
            :confirm-class="itemToToggle?.statut === 'actif' ? 'btn-warning' : 'btn-success'"
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
.table-row-deleted {
    opacity: 0.6;
}
</style>
