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

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    equipements: Object,
    categories: Array,
    ecoles: Array,
    filters: Object,
});

const deleteMode = ref(false);
const searchFilters = ref({
    categorie_id: props.filters?.categorie_id || '',
    etat: props.filters?.etat || '',
});

const etatOptions = [
    { id: 'excellent', libelle: 'Excellent' },
    { id: 'bon', libelle: 'Bon' },
    { id: 'moyen', libelle: 'Moyen' },
    { id: 'mauvais', libelle: 'Mauvais' },
    { id: 'non_fonctionnel', libelle: 'Non fonctionnel' },
];

const filterFields = computed(() => [
    { key: 'categorie_id', type: 'select', placeholder: 'Catégorie', options: props.categories, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'etat', type: 'select', placeholder: 'État', options: etatOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
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
    router.get(route('equipements.index'), searchFilters.value, {
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
    router.get(route('equipements.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('equipements.destroy', itemToDelete.value.id), {
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
    <Head :title="t('common.equipement') || 'Équipement'" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('common.equipement') || 'Équipement' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('equipements.create')" class="btn btn-primary">
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
                                        <th>{{ t('fields.reference') || 'Référence' }}</th>
                                        <th>{{ t('fields.etat') || 'État' }}</th>
                                        <th>{{ t('fields.localisation') || 'Localisation' }}</th>
                                        <th>{{ t('common.categorie') || 'Catégorie' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="equipements?.data && equipements?.data.length > 0">
                                        <tr v-for="equipement in equipements?.data" :key="equipement.id">
                                            <td>{{ equipement.nom }}</td>
                                            <td>{{ equipement.reference || '-' }}</td>
                                            <td><span class="badge" :class="equipement.etat === 'excellent' || equipement.etat === 'bon' ? 'bg-success' : equipement.etat === 'moyen' ? 'bg-warning' : 'bg-danger'">{{ equipement.etat }}</span></td>
                                            <td>{{ equipement.localisation || '-' }}</td>
                                            <td>{{ equipement.categorie || '-' }}</td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('equipements.show', equipement.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('equipements.edit', equipement.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(equipement)" class="btn btn-danger" :title="t('actions.delete')">
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
                            <template v-if="equipements?.data && equipements?.data.length > 0">
                                <div v-for="equipement in equipements?.data" :key="'m-' + equipement.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.nom') || 'Nom' }}</span>
                                            <span class="mobile-card-value">{{ equipement.nom }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.reference') || 'Référence' }}</span>
                                            <span class="mobile-card-value">{{ equipement.reference || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.etat') || 'État' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="equipement.etat === 'excellent' || equipement.etat === 'bon' ? 'bg-success' : equipement.etat === 'moyen' ? 'bg-warning' : 'bg-danger'">{{ equipement.etat }}</span></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.localisation') || 'Localisation' }}</span>
                                            <span class="mobile-card-value">{{ equipement.localisation || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.categorie') || 'Catégorie' }}</span>
                                            <span class="mobile-card-value">{{ equipement.categorie || '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('equipements.show', equipement.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('equipements.edit', equipement.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(equipement)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="equipements" />
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
