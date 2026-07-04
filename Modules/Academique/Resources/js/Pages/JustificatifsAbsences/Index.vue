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
    justificatifs: Object,
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
    { id: 'valide', libelle: 'Validé' },
    { id: 'en_attente', libelle: 'En attente' },
];
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher', icon: 'fa-search', width: '220px' },
    { key: 'statut', type: 'select', placeholder: 'Statut', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];
// Debounce timer
let searchTimeout;
// Real-time search
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};
// Modal
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const search = () => {
    router.get(route('academique.justificatifs_absences.index'), searchFilters.value, {
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
  router.get(route('academique.justificatifs_absences.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('academique.justificatifs_absences.destroy', itemToDelete.value.id), { method: 'delete', preserveScroll: true,
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
        router.visit(route('academique.justificatifs_absences.statut', itemToDelete.value.id), { method: 'put',
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
const justificatifs = props.justificatifs || page.props.justificatifs;
const getApprenantName = (justificatif) => {
    return justificatif?.absence?.apprenant?.user
        ? justificatif.absence.apprenant.user.prenoms + ' ' + justificatif.absence.apprenant.user.nom
        : 'N/A';
};
const getStatutLabel = (justificatif) => {
    return justificatif?.valide_at ? 'Validé' : 'En attente';
};
const isValide = (justificatif) => {
    return justificatif?.valide_at !== null;
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
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('justificatifs-absences-create')">
                        <Link :href="route('academique.justificatifs_absences.create')" class="btn btn-primary">
                            {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <!-- Alert Message -->
            <AlertMessage />
            <div class="row m-0">
                <!-- Filtres -->
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters"></FilterBar>
                <!-- Table -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('common.apprenant') || 'Apprenant' }}</th>
                                        <th>{{ t('common.commentaire') || 'Commentaire' }}</th>
                                        <th>{{ t('fields.fichier') || 'Fichier' }}</th>
                                        <th>{{ t('fields.validated_by') || 'Validé par' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="justificatifs?.data && justificatifs?.data.length > 0">
                                        <tr v-for="justificatif in justificatifs?.data" :key="justificatif.id">
                                            <td>{{ getApprenantName(justificatif) || '' }}</td>
                                            <td>{{ justificatif.commentaire || '-' }}</td>
                                            <td>{{ justificatif.fichier?.libelle || '-' }}</td>
                                            <td>{{ justificatif.validePar?.prenoms + ' ' + justificatif.validePar?.nom || '-' }}</td>
                                            <td><span class="badge" :class="isValide(justificatif) ? 'bg-success' : 'bg-warning'">{{ getStatutLabel(justificatif) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.justificatifs_absences.show', justificatif.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('academique.justificatifs_absences.edit', justificatif.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(justificatif)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="isValide(justificatif)" @click="confirmDeactivate(justificatif)" class="btn btn-danger" :title="t('actions.deactivate')">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button v-else @click="confirmActivate(justificatif)" class="btn btn-success" :title="t('actions.activate')">
                                                        <span class="fa fa-check"></span>
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
                        <!-- Mobile View -->
                        <div class="mobile-card-list">
                            <template v-if="justificatifs?.data && justificatifs?.data.length > 0">
                                <div v-for="justificatif in justificatifs?.data" :key="'m-' + justificatif.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.apprenant') || 'Apprenant' }}</span>
                                            <span class="mobile-card-value">{{ getApprenantName(justificatif) || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.commentaire') || 'Commentaire' }}</span>
                                            <span class="mobile-card-value">{{ justificatif.commentaire || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.fichier') || 'Fichier' }}</span>
                                            <span class="mobile-card-value">{{ justificatif.fichier?.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.validated_by') || 'Validé par' }}</span>
                                            <span class="mobile-card-value">{{ justificatif.validePar?.prenoms + ' ' + justificatif.validePar?.nom || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="isValide(justificatif) ? 'bg-success' : 'bg-warning'">{{ getStatutLabel(justificatif) }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('academique.justificatifs_absences.show', justificatif.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('academique.justificatifs_absences.edit', justificatif.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(justificatif)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="isValide(justificatif)" @click="confirmDeactivate(justificatif)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(justificatif)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>
                        <!-- Pagination -->
                        <Pagination :data="justificatifs" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal -->
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
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
