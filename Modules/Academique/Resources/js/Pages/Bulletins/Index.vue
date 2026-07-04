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
    bulletins: Object,
    filters: Object,
});
const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);
// Filtres de recherche
const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
});
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher', icon: 'fa-search', width: '220px' },
    { key: 'statut', type: 'select', placeholder: 'Statut', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
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
    router.get(route('academique.bulletins.index'), searchFilters.value, {
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
  router.get(route('academique.bulletins.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('academique.bulletins.destroy', itemToDelete.value.id), { method: 'delete', preserveScroll: true,
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
        router.visit(route('academique.bulletins.statut', itemToDelete.value.id), { method: 'put',
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

// Fonction pour télécharger le PDF
const downloadPdf = (url, event) => {
    event.preventDefault();

    // Créer un élément <a> temporaire
    const link = document.createElement('a');
    link.href = url;
    link.download = true;
    link.style.display = 'none';

    // Ajouter à la page et cliquer
    document.body.appendChild(link);
    link.click();

    // Nettoyer
    document.body.removeChild(link);
};
const page = usePage();
// Make bulletins accessible in template from props or page
const bulletins = props.bulletins || page.props.bulletins;
const goToCreate = () => {
    console.log('=== DEBUG: goToCreate called ===');
    console.log('Router object:', router);
    console.log('Route function:', typeof route);
    try {
        const createRoute = route('academique.bulletins.create');
        console.log('Create route resolved to:', createRoute);
        console.log('Attempting router.visit()...');
        const result = router.visit(createRoute);
        console.log('router.visit() returned:', result);
        console.log('Navigation initiated successfully');
    } catch (error) {
        console.error('ERROR during navigation:', error);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        alert('❌ ERREUR: ' + error.message + '\n\nVérifiez la console pour plus de détails.');
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
                    <div class="dashboard-btn" v-if="can('bulletins-create')">
                        <button @click="goToCreate" class="btn btn-primary" style="padding: 10px 20px; font-size: 14px; font-weight: bold;">
                            <i class="fa fa-plus"></i> Ajouter Bulletin
                        </button>
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
                                        <th>{{ t('common.apprenant') || 'Apprenant' }}</th>
                                        <th>{{ t('common.classe') || 'Classe' }}</th>
                                        <th>{{ t('common.annee_scolaire') || 'Année Scolaire' }}</th>
                                        <th>{{ t('fields.moyenne_generale') || 'Moyenne Générale' }}</th>
                                        <th>{{ t('fields.rang') || 'Rang' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="bulletins?.data && bulletins?.data.length > 0">
                                        <tr v-for="bulletin in bulletins?.data" :key="bulletin.id">
                                            <td>{{ bulletin.apprenant_display || '-' }}</td>
                                            <td>{{ bulletin.classe?.nom || '' }}</td>
                                            <td>{{ bulletin.anneeScolaire?.libelle || bulletin.annee_scolaire_id || '-' }}</td>
                                            <td>{{ bulletin.moyenne_generale ? Number(bulletin.moyenne_generale).toFixed(2) : '-' }}</td>
                                            <td>{{ bulletin.rang || '' }}</td>
                                            <td><span class="badge" :class="bulletin.decision_conseil === 'admis' ? 'bg-success' : bulletin.decision_conseil === 'ajourne' ? 'bg-danger' : 'bg-warning'">{{ t('common.' + (bulletin.decision_conseil || 'pending')) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.bulletins.show', bulletin.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('academique.bulletins.edit', bulletin.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <a :href="route('academique.bulletins.pdf', bulletin.id)" class="btn btn-success" title="Bulletin PDF" target="_blank">
                                                        <span class="fa fa-file-pdf"></span>
                                                    </a>
                                                    <a :href="route('academique.bulletins.pdf-seq', bulletin.id)" class="btn btn-info" title="Bulletin avec Séquences" target="_blank">
                                                        <span class="fa fa-file-alt"></span>
                                                    </a>
                                                    <button @click="confirmDelete(bulletin)" class="btn btn-danger" :title="t('actions.delete')">
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
                            <template v-if="bulletins?.data && bulletins?.data.length > 0">
                                <div v-for="bulletin in bulletins?.data" :key="'m-' + bulletin.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.apprenant') || 'Apprenant' }}</span>
                                            <span class="mobile-card-value">{{ bulletin.apprenant_display || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.classe') || 'Classe' }}</span>
                                            <span class="mobile-card-value">{{ bulletin.classe?.nom || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.annee_scolaire') || 'Année Scolaire' }}</span>
                                            <span class="mobile-card-value">{{ bulletin.anneeScolaire?.libelle || bulletin.annee_scolaire_id || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.moyenne_generale') || 'Moyenne Générale' }}</span>
                                            <span class="mobile-card-value">{{ bulletin.moyenne_generale ? Number(bulletin.moyenne_generale).toFixed(2) : '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.rang') || 'Rang' }}</span>
                                            <span class="mobile-card-value">{{ bulletin.rang || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.decision_conseil') || 'Décision' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="bulletin.decision_conseil === 'admis' ? 'bg-success' : bulletin.decision_conseil === 'ajourne' ? 'bg-danger' : 'bg-warning'">{{ t('common.' + (bulletin.decision_conseil || 'pending')) }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('academique.bulletins.show', bulletin.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('academique.bulletins.edit', bulletin.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <a :href="route('academique.bulletins.pdf', bulletin.id)" class="btn btn-success" @click="downloadPdf(route('academique.bulletins.pdf', bulletin.id), $event)"><span class="fa fa-download"></span></a>
                                        <button @click="confirmDelete(bulletin)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>
                        <!-- Pagination -->
                        <Pagination :data="bulletins" />
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
