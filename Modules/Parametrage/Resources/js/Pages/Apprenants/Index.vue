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
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();
const props = defineProps({
    apprenants: Object,
    filters: Object,
});
const deleteMode = ref(false);
const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
});
const statusOptions = [
    { id: 'actif', libelle: t('common.active') || 'Actif' },
    { id: 'inactif', libelle: t('common.inactive') || 'Inactif' },
    { id: 'suspendu', libelle: t('common.suspended') || 'Suspendu' },
    { id: 'exclus', libelle: t('common.excluded') || 'Exclus' },
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
const showToggleModal = ref(false);
const itemToToggle = ref(null);
const search = () => {
    router.get(route('parametrage.apprenants.index'), searchFilters.value, {
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
  router.get(route('parametrage.apprenants.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('parametrage.apprenants.destroy', itemToDelete.value?.id), { method: 'delete', preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
            },
            onFinish: () => hideLoader(), });
    }
};
const closeModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
    deleteMode.value = false;
};
const confirmToggleStatut = (item) => {
    itemToToggle.value = item;
    showToggleModal.value = true;
};
const toggleStatut = () => {
    if (itemToToggle.value) {
        // Show appropriate loader based on current status
        if (itemToToggle.value.statut === 'actif') {
            showDeactivateLoader();
        } else {
            showActivateLoader();
        }
        router.visit(route('parametrage.apprenants.statut', itemToToggle.value?.id), { method: 'put', preserveScroll: true,
            onSuccess: () => { showToggleModal.value = false; itemToToggle.value = null; },
            onFinish: () => hideLoader(), });
    }
};
const closeToggleModal = () => {
    showToggleModal.value = false;
    itemToToggle.value = null;
};
const page = usePage();
const apprenants = props.apprenants || page.props.apprenants;
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
    <Head :title="page.props.title || 'Apprenants'" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title || 'Apprenants' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('apprenants-create')">
                        <Link :href="route('parametrage.apprenants.create')" class="btn btn-primary">
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
                        <input
                            type="text"
                            v-model="searchFilters.search"
                            class="form-control form-control-sm"
                            :placeholder="t('fields.search')"
                            style="height: 32px; font-size: 13px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <select
                            v-model="searchFilters.statut"
                            class="form-control form-control-sm"
                            style="height: 32px; width: 100%;"
                        >
                            <option value="">{{ t('fields.statut') || 'Statut' }}</option>
                            <option value="actif">{{ t('common.active') || 'Actif' }}</option>
                            <option value="inactif">{{ t('common.inactive') || 'Inactif' }}</option>
                            <option value="suspendu">{{ t('common.suspended') || 'Suspendu' }}</option>
                            <option value="exclus">{{ t('common.excluded') || 'Exclus' }}</option>
                        </select>
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
                                        <th>{{ t('fields.matricule') || 'Matricule' }}</th>
                                        <th>{{ t('fields.nom') || 'Nom' }}</th>
                                        <th>{{ t('fields.classe') || 'Classe' }}</th>
                                        <th>{{ t('fields.email') || 'Email' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="apprenants?.data && apprenants?.data.length > 0">
                                        <tr v-for="apprenant in apprenants?.data" :key="apprenant.id">
                                            <td>{{ apprenant.matricule || '' }}</td>
                                            <td>{{ apprenant.nom || '' }}</td>
                                            <td>{{ apprenant.classe?.nom || '' }}</td>
                                            <td>{{ apprenant.email || '' }}</td>
                                            <td><span class="badge" :class="apprenant.statut === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + apprenant.statut) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('parametrage.apprenants.show', apprenant?.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('parametrage.apprenants.edit', apprenant?.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmToggleStatut(apprenant)" class="btn btn-info" :title="apprenant.statut === 'actif' ? t('actions.deactivate') : t('actions.activate')">
                                                        <span class="fa fa-toggle-on"></span>
                                                    </button>
                                                    <button @click="confirmDelete(apprenant)" class="btn btn-danger" :title="t('actions.delete')">
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
                        <!-- Pagination -->
                        <Pagination :data="apprenants" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation suppression -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="t('messages.confirm.delete.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            @close="closeModal"
            @confirm="deleteItem"
            :confirm-text="t('actions.delete')"
            :confirm-class="'btn-danger'"
        />
        <!-- Modal de confirmation toggle statut -->
        <ConfirmModal
            :show="showToggleModal"
            :title="itemToToggle?.statut === 'actif' ? t('messages.confirm.deactivate.title') : t('messages.confirm.activate.title')"
            :message="itemToToggle?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')"
            :sub-message="itemToToggle?.statut === 'actif' ? t('messages.confirm.deactivate.warning') : t('messages.confirm.activate.warning')"
            @close="closeToggleModal"
            @confirm="toggleStatut"
            :confirm-text="itemToToggle?.statut === 'actif' ? t('actions.deactivate') : t('actions.activate')"
            :confirm-class="'btn-info'"
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
