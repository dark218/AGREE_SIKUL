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
import { useLoader } from '@/composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    fournitures: Object,
    categories: Array,
    statutOptions: Array,
    filters: Object,
});

const deleteMode = ref(false);
const searchFilters = ref({
    categorie_fourniture_id: props.filters?.categorie_fourniture_id || '',
    statut: props.filters?.statut || '',
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

const search = () => {
    router.get(route('fournitures.index'), searchFilters.value, {
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
    router.get(route('fournitures.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('fournitures.destroy', itemToDelete.value.id), {
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

const getStatutBadgeClass = (statut) => {
    switch (statut) {
        case 'disponible':
            return 'bg-success';
        case 'rupture':
            return 'bg-danger';
        case 'commandee':
            return 'bg-warning';
        default:
            return 'bg-secondary';
    }
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
    <Head :title="t('common.fourniture') || 'Fournitures'" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('common.fourniture') || 'Fournitures' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('fournitures.create')" class="btn btn-primary">
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
                    <div style="width: 200px;">
                        <input
                            v-model="searchFilters.search"
                            type="text"
                            class="form-control-sm"
                            :placeholder="t('fields.search') || 'Rechercher...'"
                            style="height: 32px; width: 100%;"
                            @input="performSearch"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.categorie_fourniture_id"
                            :options="categories"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.categorie') || 'Catégorie'"
                            class="form-control-sm"
                            style="height: 32px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.statut"
                            :options="statutOptions"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.statut') || 'Statut'"
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
                                        <th>{{ t('common.libelle') || 'Libellé' }}</th>
                                        <th>{{ t('fields.code') || 'Code' }}</th>
                                        <th>{{ t('fields.quantite') || 'Quantité' }}</th>
                                        <th>{{ t('fields.prix_unitaire') || 'Prix/Unité' }}</th>
                                        <th>{{ t('fields.statut') || 'Statut' }}</th>
                                        <th>{{ t('common.categorie') || 'Catégorie' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="fournitures?.data && fournitures?.data.length > 0">
                                        <tr v-for="fourniture in fournitures?.data" :key="fourniture.id">
                                            <td>{{ fourniture.libelle }}</td>
                                            <td>{{ fourniture.code || '-' }}</td>
                                            <td><span class="badge bg-info">{{ fourniture.quantite }}</span></td>
                                            <td>{{ fourniture.prix_unitaire?.toFixed(2) || '-' }} €</td>
                                            <td><span class="badge" :class="getStatutBadgeClass(fourniture.statut)">{{ fourniture.statut }}</span></td>
                                            <td>{{ fourniture.categorie || '-' }}</td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('fournitures.show', fourniture.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('fournitures.edit', fourniture.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(fourniture)" class="btn btn-danger" :title="t('actions.delete')">
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
                            <template v-if="fournitures?.data && fournitures?.data.length > 0">
                                <div v-for="fourniture in fournitures?.data" :key="'m-' + fourniture.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.libelle') || 'Libellé' }}</span>
                                            <span class="mobile-card-value">{{ fourniture.libelle }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.code') || 'Code' }}</span>
                                            <span class="mobile-card-value">{{ fourniture.code || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.quantite') || 'Quantité' }}</span>
                                            <span class="mobile-card-value"><span class="badge bg-info">{{ fourniture.quantite }}</span></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.prix_unitaire') || 'Prix/Unité' }}</span>
                                            <span class="mobile-card-value">{{ fourniture.prix_unitaire?.toFixed(2) || '-' }} €</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.statut') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="getStatutBadgeClass(fourniture.statut)">{{ fourniture.statut }}</span></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.categorie') || 'Catégorie' }}</span>
                                            <span class="mobile-card-value">{{ fourniture.categorie || '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('fournitures.show', fourniture.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('fournitures.edit', fourniture.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(fourniture)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="fournitures" />
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
