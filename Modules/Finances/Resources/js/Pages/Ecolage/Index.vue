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

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();

const props = defineProps({
    ecolages: Object,
    anneesScolaires: Array,
    ecoles: Array,
    niveaux: Array,
    filters: Object,
});

const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);

const searchFilters = ref({
    annee_scolaire_id: props.filters?.annee_scolaire_id || '',
    ecole_id: props.filters?.ecole_id || '',
    niveau_id: props.filters?.niveau_id || '',
    etat: props.filters?.etat || '',
});

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const anneesOptions = computed(() =>
    props.anneesScolaires.map(a => ({ id: a.id, libelle: a.libelle }))
);

const niveauxOptions = computed(() =>
    props.niveaux.map(n => ({ id: n.id, libelle: n.nom || n.libelle }))
);

const ecolesOptions = computed(() =>
    props.ecoles.map(e => ({ id: e.id, libelle: e.nom }))
);

const filterFields = computed(() => [
    { key: 'annee_scolaire_id', type: 'select', placeholder: 'Année scolaire', options: anneesOptions.value, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'niveau_id', type: 'select', placeholder: 'Niveau', options: niveauxOptions.value, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'ecole_id', type: 'select', placeholder: 'École', options: ecolesOptions.value, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'etat', type: 'select', placeholder: 'Statut', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
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
    router.get(route('finances.ecolage.index'), searchFilters.value, {
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
    router.get(route('finances.ecolage.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('finances.ecolage.destroy', itemToDelete.value.id), {
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
        router.visit(route('finances.ecolage.statut', itemToDelete.value.id), {
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
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('finances.ecolage.create')" class="btn btn-primary">
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
                                        <th>{{ t('fields.academic_year') }}</th>
                                        <th>{{ t('fields.level') }}</th>
                                        <th>{{ t('fields.school') }}</th>
                                        <th>{{ t('fields.campus') }}</th>
                                        <th>{{ t('fields.file_fees') }}</th>
                                        <th>{{ t('fields.registration_fees') }}</th>
                                        <th>{{ t('fields.tuition_fees') }}</th>
                                        <th>{{ t('fields.status') }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="ecolages?.data && ecolages?.data.length > 0">
                                        <tr v-for="item in ecolages?.data" :key="item.id">
                                            <td>{{ item.annee_scolaire?.libelle || '-' }}</td>
                                            <td>{{ item.niveau?.nom || item.niveau?.libelle || '-' }}</td>
                                            <td>{{ item.ecole?.nom || '-' }}</td>
                                            <td>{{ item.campus?.nom || '-' }}</td>
                                            <td class="text-end">{{ item.frais_dossier ? item.frais_dossier.toLocaleString() : '-' }}</td>
                                            <td class="text-end">{{ item.frais_inscription ? item.frais_inscription.toLocaleString() : '-' }}</td>
                                            <td class="text-end">{{ item.frais_scolarite ? item.frais_scolarite.toLocaleString() : '-' }}</td>
                                            <td><span class="badge" :class="item.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + item.etat) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('finances.ecolage.show', item.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                                    <Link :href="route('finances.ecolage.edit', item.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                                    <button @click="confirmDelete(item)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                                    <button v-if="item.etat === 'actif'" @click="confirmDeactivate(item)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                                    <button v-else @click="confirmActivate(item)" class="btn btn-success"><span class="fa fa-check"></span></button>
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

                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="ecolages?.data && ecolages?.data.length > 0">
                                <div v-for="item in ecolages?.data" :key="'m-' + item.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.academic_year') }}</span>
                                            <span class="mobile-card-value">{{ item.annee_scolaire?.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.school') }}</span>
                                            <span class="mobile-card-value">{{ item.ecole?.nom || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.level') }}</span>
                                            <span class="mobile-card-value">{{ item.niveau?.nom || item.niveau?.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.file_fees') }}</span>
                                            <span class="mobile-card-value">{{ item.frais_dossier ? item.frais_dossier.toLocaleString() : '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="item.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + item.etat) }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('finances.ecolage.show', item.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('finances.ecolage.edit', item.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(item)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="item.etat === 'actif'" @click="confirmDeactivate(item)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(item)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="ecolages" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="deleteMode ? 'Supprimer' : (deactivateMode ? 'Désactiver' : 'Activer')"
            :message="deleteMode ? 'Êtes-vous sûr?' : (deactivateMode ? 'Êtes-vous sûr de désactiver?' : 'Êtes-vous sûr d\'activer?')"
            @close="showDeleteModal = false"
            @confirm="deleteMode ? deleteItem() : toggleStatus()"
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
