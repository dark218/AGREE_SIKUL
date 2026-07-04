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
import { useLoader } from '@/Composables/useLoader';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    emploisTemps: Object,
    filters: Object,
});
const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);
// Filtres de recherche
const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
    week_number: props.filters?.week_number || '',
});
const statutOptions = [
    { id: 'brouillon', libelle: t('common.brouillon') || 'Brouillon' },
    { id: 'valide', libelle: t('common.valide') || 'Validé' },
    { id: 'publie', libelle: t('common.publie') || 'Publié' },
    { id: 'archive', libelle: t('common.archive') || 'Archivé' },
];
// Generate week options (current week and next 52 weeks)
const generateWeekOptions = () => {
    const options = [];
    const now = new Date();
    const currentYear = now.getFullYear();

    for (let year = currentYear - 1; year <= currentYear + 1; year++) {
        for (let week = 1; week <= 52; week++) {
            options.push({
                id: `${year}-W${String(week).padStart(2, '0')}`,
                libelle: `Semaine ${week} - ${year}`
            });
        }
    }
    return options;
};
const weekOptions = generateWeekOptions();
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher', icon: 'fa-search', width: '220px' },
    { key: 'statut', type: 'select', placeholder: 'Statut', options: statutOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'week_number', type: 'select', placeholder: 'Semaine', options: weekOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
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
    router.get(route('academique.emplois_du_temps.index'), searchFilters.value, {
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
  router.get(route('academique.emplois_du_temps.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('academique.emplois_du_temps.destroy', itemToDelete.value.id), { method: 'delete', preserveScroll: true,
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
        router.visit(route('academique.emplois_du_temps.activate', itemToDelete.value.id), { method: 'put',
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
const downloadPdf = (emploiTemps) => {
    const url = route('academique.emplois_du_temps.pdf', emploiTemps.id);
    window.location.href = url;
};
const closeModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
    deleteMode.value = false;
    deactivateMode.value = false;
    activateMode.value = false;
};
const page = usePage();
// Make emploisTemps accessible in template from props or page
const emploisTemps = props.emploisTemps || page.props.emploisTemps;
console.log('[Index] page.props.auth:', page.props.auth);
console.log('[Index] page.props.auth.user?.permissions:', page.props.auth?.user?.permissions);
// Helper to format date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return date.toLocaleString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch (e) {
        return dateString;
    }
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
                    <div class="dashboard-btn" v-if="can('emploi-temps-create')">
                        <Link :href="route('academique.emplois_du_temps.create')" class="btn btn-primary">
                            {{ t('actions.add') }}
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
                                        <th>{{ t('common.classe') || 'Classe' }}</th>
                                        <th>{{ t('common.annee_scolaire') || 'Année Scolaire' }}</th>
                                        <th>{{ t('fields.week_name') || 'Semaine' }}</th>
                                        <th>{{ t('fields.week_start_date') || 'Début Semaine' }}</th>
                                        <th>Total Cours</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="emploisTemps?.data && emploisTemps?.data.length > 0">
                                        <tr v-for="emploiTemps in emploisTemps?.data" :key="emploiTemps.id">
                                            <td>{{ emploiTemps.classe?.nom || '' }}</td>
                                            <td>{{ emploiTemps.anneeScolaire?.libelle || '-' }}</td>
                                            <td><strong>{{ emploiTemps.week_name || '-' }}</strong></td>
                                            <td>{{ emploiTemps.week_start_date ? new Date(emploiTemps.week_start_date).toLocaleDateString('fr-FR') : '-' }}</td>
                                            <td><span class="badge bg-info">{{ emploiTemps.total_courses || 0 }} cours</span></td>
                                            <td>
                                                <span class="badge" :class="{
                                                    'bg-secondary': emploiTemps.statut === 'brouillon',
                                                    'bg-info': emploiTemps.statut === 'valide',
                                                    'bg-success': emploiTemps.statut === 'publie',
                                                    'bg-dark': emploiTemps.statut === 'archive'
                                                }">
                                                    {{ t('common.' + emploiTemps.statut) || emploiTemps.statut }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.emplois_du_temps.show', emploiTemps.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('academique.emplois_du_temps.edit_week', emploiTemps.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="downloadPdf(emploiTemps)" class="btn btn-success" :title="t('actions.download')">
                                                        <span class="fa fa-file-pdf"></span>
                                                    </button>
                                                    <button v-if="!emploiTemps.deleted_at" @click="confirmDeactivate(emploiTemps)" class="btn btn-danger" :title="t('actions.deactivate')">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button v-if="emploiTemps.deleted_at" @click="confirmActivate(emploiTemps)" class="btn btn-success" :title="t('actions.activate')">
                                                        <span class="fa fa-check"></span>
                                                    </button>
                                                    <button @click="confirmDelete(emploiTemps)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
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
                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="emploisTemps?.data && emploisTemps?.data.length > 0">
                                <div v-for="emploiTemps in emploisTemps?.data" :key="'m-' + emploiTemps.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.classe') || 'Classe' }}</span>
                                            <span class="mobile-card-value">{{ emploiTemps.classe?.nom || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.annee_scolaire') || 'Année Scolaire' }}</span>
                                            <span class="mobile-card-value">{{ emploiTemps.anneeScolaire?.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.week_name') || 'Semaine' }}</span>
                                            <span class="mobile-card-value"><strong>{{ emploiTemps.week_name || '-' }}</strong></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.week_start_date') || 'Début Semaine' }}</span>
                                            <span class="mobile-card-value">{{ emploiTemps.week_start_date ? new Date(emploiTemps.week_start_date).toLocaleDateString('fr-FR') : '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Total Cours</span>
                                            <span class="mobile-card-value"><span class="badge bg-info">{{ emploiTemps.total_courses || 0 }} cours</span></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'Statut' }}</span>
                                            <span class="mobile-card-value">
                                                <span class="badge" :class="{
                                                    'bg-secondary': emploiTemps.statut === 'brouillon',
                                                    'bg-info': emploiTemps.statut === 'valide',
                                                    'bg-success': emploiTemps.statut === 'publie',
                                                    'bg-dark': emploiTemps.statut === 'archive'
                                                }">
                                                    {{ t('common.' + emploiTemps.statut) || emploiTemps.statut }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('academique.emplois_du_temps.show', emploiTemps.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('academique.emplois_du_temps.edit_week', emploiTemps.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="downloadPdf(emploiTemps)" class="btn btn-success"><span class="fa fa-file-pdf"></span></button>
                                        <button v-if="!emploiTemps.deleted_at" @click="confirmDeactivate(emploiTemps)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-if="emploiTemps.deleted_at" @click="confirmActivate(emploiTemps)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                        <button @click="confirmDelete(emploiTemps)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>
                        <!-- Pagination -->
                        <Pagination :data="emploisTemps" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation -->
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
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
