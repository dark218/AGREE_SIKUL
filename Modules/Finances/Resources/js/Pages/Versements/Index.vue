<script setup>
import { ref, watch } from 'vue';
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

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();

const props = defineProps({
    title: String,
    versements: Object,
    filters: Object,
    anneesScolaires: Array,
    ecoles: Array,
});

const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);

// Filtres de recherche
const searchFilters = ref({
    apprenant: props.filters?.apprenant || '',
    ecole_id: props.filters?.ecole_id || '',
    annee_scolaire_id: props.filters?.annee_scolaire_id || '',
    etat: props.filters?.etat || '',
});

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Debounce timer for real-time search
let searchTimeout;

// Real-time search with debounce
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};

// Modal de suppression
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const search = () => {
    router.get(route('finances.versements.index'), searchFilters.value, {
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
    router.get(route('finances.versements.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('finances.versements.destroy', itemToDelete.value.id), { method: 'delete', preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
            },
            onFinish: () => hideLoader(), });
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
        router.visit(route('finances.versements.statut', itemToDelete.value.id), { method: 'put',
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
const versements = props.versements || page.props.versements;

const can = (permission) => {
    const userPerms = page.props.auth?.permissions || page.props.userPermissions || [];
    return userPerms.some(p => p.name === permission) || false;
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

const anneesScolairesOptions = props.anneesScolaires.map(a => ({ id: a.id, libelle: a.libelle }));
const ecolesOptions = props.ecoles.map(e => ({ id: e.id, libelle: e.nom }));

const filterFields = [
    { key: 'apprenant', type: 'text', placeholder: 'Apprenant', icon: 'fa-search', width: '220px' },
    { key: 'ecole_id', type: 'select', placeholder: 'École', options: ecolesOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'annee_scolaire_id', type: 'select', placeholder: 'Année', options: anneesScolairesOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'etat', type: 'select', placeholder: 'État', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];
</script>

<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('versement-create')">
                        <Link :href="route('finances.versements.create')" class="btn btn-primary">
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

                <!-- Table section -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.apprenant') || 'Apprenant' }}</th>
                                        <th>{{ t('fields.level') || 'Niveau' }}</th>
                                        <th>{{ t('fields.class') || 'Classe' }}</th>
                                        <th>{{ t('fields.school') || 'École' }}</th>
                                        <th>{{ t('fields.academic_year') || 'Année' }}</th>
                                        <th>{{ t('fields.total_paye') || 'Total payé' }}</th>
                                        <th>{{ t('fields.restant_a_payer') || 'Restant' }}</th>
                                        <th>{{ t('fields.status') || 'État' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="versements?.data && versements?.data.length > 0">
                                        <tr v-for="item in versements?.data" :key="item.id">
                                            <td>{{ item.apprenant?.nom }} {{ item.apprenant?.prenoms }}</td>
                                            <td>{{ item.niveau?.nom }}</td>
                                            <td>{{ item.classe?.nom }}</td>
                                            <td>{{ item.ecole?.nom }}</td>
                                            <td>{{ item.annee_scolaire?.libelle }}</td>
                                            <td>{{ item.total_paye }}</td>
                                            <td>{{ item.restant_a_payer }}</td>
                                            <td>
                                                <span class="badge" :class="item.etat === 'actif' ? 'bg-success' : 'bg-danger'">
                                                    {{ t('common.' + item.etat) }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('finances.versements.show', item.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('finances.versements.edit', item.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(item)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="item.etat === 'actif'" @click="confirmDeactivate(item)" class="btn btn-danger" :title="t('actions.deactivate')">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button v-else @click="confirmActivate(item)" class="btn btn-success" :title="t('actions.activate')">
                                                        <span class="fa fa-check"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="9" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile cards -->
                        <div class="mobile-card-list">
                            <template v-if="versements?.data && versements?.data.length > 0">
                                <div v-for="item in versements?.data" :key="'m-' + item.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.apprenant') }}</span>
                                            <span class="mobile-card-value">{{ item.apprenant?.nom }} {{ item.apprenant?.prenoms }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.level') }}</span>
                                            <span class="mobile-card-value">{{ item.niveau?.nom }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.class') }}</span>
                                            <span class="mobile-card-value">{{ item.classe?.nom }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.school') }}</span>
                                            <span class="mobile-card-value">{{ item.ecole?.nom }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.academic_year') }}</span>
                                            <span class="mobile-card-value">{{ item.annee_scolaire?.libelle }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.total_paye') }}</span>
                                            <span class="mobile-card-value">{{ item.total_paye }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.restant_a_payer') }}</span>
                                            <span class="mobile-card-value">{{ item.restant_a_payer }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') }}</span>
                                            <span class="mobile-card-value">
                                                <span class="badge" :class="item.etat === 'actif' ? 'bg-success' : 'bg-danger'">
                                                    {{ t('common.' + item.etat) }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('finances.versements.show', item.id)" class="btn btn-secondary" :title="t('actions.view')">
                                            <span class="fa fa-eye"></span>
                                        </Link>
                                        <Link :href="route('finances.versements.edit', item.id)" class="btn btn-primary" :title="t('actions.edit')">
                                            <span class="fa fa-edit"></span>
                                        </Link>
                                        <button @click="confirmDelete(item)" class="btn btn-danger" :title="t('actions.delete')">
                                            <span class="fa fa-trash"></span>
                                        </button>
                                        <button v-if="item.etat === 'actif'" @click="confirmDeactivate(item)" class="btn btn-danger" :title="t('actions.deactivate')">
                                            <span class="fa fa-ban"></span>
                                        </button>
                                        <button v-else @click="confirmActivate(item)" class="btn btn-success" :title="t('actions.activate')">
                                            <span class="fa fa-check"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="versements" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Shared modal -->
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

        <!-- Loader -->
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
