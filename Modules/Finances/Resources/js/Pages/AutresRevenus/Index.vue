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
const page = usePage();

const { isLoading, loaderMessage, loaderSubMessage, loaderVariant,
        showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();

const props = defineProps({
    autresRevenus: Object,
    filters: Object,
});

const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

let searchTimeout;
const searchFilters = ref({
    ecole_id: props.filters?.ecole_id || '',
    annee_scolaire_id: props.filters?.annee_scolaire_id || '',
    niveau_id: props.filters?.niveau_id || '',
    etat: props.filters?.etat || '',
});

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const filterFields = computed(() => [
    { key: 'ecole_id', type: 'select', placeholder: 'École', options: page.props.ecoles || [], optionValue: 'id', optionLabel: 'nom', width: '190px' },
    { key: 'annee_scolaire_id', type: 'select', placeholder: 'Année scolaire', options: page.props.anneesScolaires || [], optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'niveau_id', type: 'select', placeholder: 'Niveau', options: page.props.niveauxEtudes || [], optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'etat', type: 'select', placeholder: 'Statut', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
]);

const closeModal = () => {
    showDeleteModal.value = false;
    setTimeout(() => {
        deleteMode.value = false;
        deactivateMode.value = false;
        activateMode.value = false;
        itemToDelete.value = null;
    }, 300);
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
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

const deleteItem = () => {
    showDeleteLoader();
    router.delete(route('finances.autres-revenus.destroy', itemToDelete.value.id), {
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
                closeModal();
            }, 500);
        },
        onError: () => {
            hideLoader();
        }
    });
};

const toggleStatus = () => {
    if (deactivateMode.value || activateMode.value) {
        showDeactivateLoader();
    }
    router.put(route('finances.autres-revenus.statut', itemToDelete.value.id), {}, {
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
                closeModal();
            }, 500);
        },
        onError: () => {
            hideLoader();
        }
    });
};

const search = () => {
    router.get(route('finances.autres-revenus.index'), searchFilters.value, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    searchFilters.value = {
        ecole_id: '',
        annee_scolaire_id: '',
        niveau_id: '',
        etat: '',
    };
    search();
};

watch(
    () => searchFilters.value,
    () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { search(); }, 500);
    },
    { deep: true }
);
</script>

<template>
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('common.autres_revenus') || 'Autres revenus' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('finances.autres-revenus.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>

            <AlertMessage />

            <div class="row m-0">
                <!-- Inline Filters -->
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters"></FilterBar>

                <!-- Table -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <!-- Desktop Table -->
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.ecole') }}</th>
                                        <th>{{ t('fields.niveau_etude') }}</th>
                                        <th>{{ t('fields.annee_scolaire') }}</th>
                                        <th>{{ t('fields.uniforme') }}</th>
                                        <th>{{ t('fields.tenue_mercredi') }}</th>
                                        <th>{{ t('fields.tenue_sport') }}</th>
                                        <th>{{ t('fields.status') }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="autresRevenus?.data && autresRevenus?.data.length > 0">
                                        <tr v-for="ar in autresRevenus?.data" :key="ar.id">
                                            <td>{{ ar.ecole?.nom || '-' }}</td>
                                            <td>{{ ar.niveauEtude?.libelle || '-' }}</td>
                                            <td>{{ ar.anneeScolaire?.libelle || '-' }}</td>
                                            <td>{{ ar.uniforme || '-' }}</td>
                                            <td>{{ ar.tenue_mercredi || '-' }}</td>
                                            <td>{{ ar.tenue_sport || '-' }}</td>
                                            <td>
                                                <span class="badge" :class="ar.etat === 'actif' ? 'bg-success' : 'bg-danger'">
                                                    {{ ar.etat === 'actif' ? t('common.active') : t('common.inactive') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <Link :href="route('finances.autres-revenus.show', ar.id)" class="btn btn-sm btn-secondary">
                                                        <i class="fa fa-eye"></i>
                                                    </Link>
                                                    <Link :href="route('finances.autres-revenus.edit', ar.id)" class="btn btn-sm btn-primary">
                                                        <i class="fa fa-edit"></i>
                                                    </Link>
                                                    <button @click="confirmDelete(ar)" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                    <button v-if="ar.etat === 'actif'" @click="confirmDeactivate(ar)" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-ban"></i>
                                                    </button>
                                                    <button v-else @click="confirmActivate(ar)" class="btn btn-sm btn-success">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="8" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card List -->
                        <div class="mobile-card-list">
                            <div v-for="ar in autresRevenus?.data" :key="ar.id" class="mobile-card">
                                <div class="mobile-card-body">
                                    <div class="mobile-card-row">
                                        <span class="mobile-card-label">{{ t('fields.ecole') }}</span>
                                        <span class="mobile-card-value">{{ ar.ecole?.nom || '-' }}</span>
                                    </div>
                                    <div class="mobile-card-row">
                                        <span class="mobile-card-label">{{ t('fields.niveau_etude') }}</span>
                                        <span class="mobile-card-value">{{ ar.niveauEtude?.libelle || '-' }}</span>
                                    </div>
                                    <div class="mobile-card-row">
                                        <span class="mobile-card-label">{{ t('fields.annee_scolaire') }}</span>
                                        <span class="mobile-card-value">{{ ar.anneeScolaire?.libelle || '-' }}</span>
                                    </div>
                                    <div class="mobile-card-row">
                                        <span class="mobile-card-label">{{ t('fields.uniforme') }}</span>
                                        <span class="mobile-card-value">{{ ar.uniforme || '-' }}</span>
                                    </div>
                                    <div class="mobile-card-row">
                                        <span class="mobile-card-label">{{ t('fields.status') }}</span>
                                        <span class="mobile-card-value">
                                            <span class="badge" :class="ar.etat === 'actif' ? 'bg-success' : 'bg-danger'">
                                                {{ ar.etat === 'actif' ? t('common.active') : t('common.inactive') }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                                <div class="mobile-card-actions">
                                    <Link :href="route('finances.autres-revenus.show', ar.id)" class="btn btn-sm btn-secondary">
                                        <i class="fa fa-eye"></i>
                                    </Link>
                                    <Link :href="route('finances.autres-revenus.edit', ar.id)" class="btn btn-sm btn-primary">
                                        <i class="fa fa-edit"></i>
                                    </Link>
                                    <button @click="confirmDelete(ar)" class="btn btn-sm btn-danger">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                    <button v-if="ar.etat === 'actif'" @click="confirmDeactivate(ar)" class="btn btn-sm btn-danger">
                                        <i class="fa fa-ban"></i>
                                    </button>
                                    <button v-else @click="confirmActivate(ar)" class="btn btn-sm btn-success">
                                        <i class="fa fa-check"></i>
                                    </button>
                                </div>
                            </div>
                            <div v-if="(!autresRevenus?.data || autresRevenus?.data.length === 0)" class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <Pagination :data="autresRevenus" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Modal -->
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

        <!-- Full Page Loader -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
