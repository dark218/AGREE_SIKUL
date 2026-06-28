<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
defineOptions({ layout: DashboardLayout });
const page = usePage();
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    apprenants: Object,
    filters: Object,
});
const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);
const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
});
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
let searchTimeout;
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const search = () => {
    router.get(route('academique.apprenants.index'), searchFilters.value, {
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
  router.get(route('academique.apprenants.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('academique.apprenants.destroy', itemToDelete.value.id), {
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
        router.visit(route('academique.apprenants.statut', itemToDelete.value.id), {
            method: 'put',
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
                deactivateMode.value = false;
                activateMode.value = false;
            },
            onError: () => {},
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
                    <div class="dashboard-btn" v-if="can('apprenants-create')">
                        <Link :href="route('academique.apprenants.create')" class="btn btn-primary">
                            {{ t('actions.add') }}
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
                            :placeholder="t('fields.search') || 'Rechercher'"
                            style="height: 32px; font-size: 13px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.statut"
                            :options="statusOptions"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.status') || 'Statut'"
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
                        <a :href="route('academique.pdf.liste-apprenants')" class="btn btn-danger btn-sm" target="_blank" title="PDF Liste" style="height: 32px; padding: 0 10px; display: flex; align-items: center;"><i class="fa fa-file-pdf"></i> PDF</a>
                    </div>
                </form>
                <!-- Tableau -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('common.name') || 'Nom' }}</th>
                                        <th>{{ t('common.email') || 'Email' }}</th>
                                        <th>{{ t('common.phone') || 'Téléphone' }}</th>
                                        <th>{{ t('common.classe') || 'Classe' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="apprenants?.data && apprenants?.data.length > 0">
                                        <tr v-for="item in apprenants?.data" :key="item.id">
                                            <td>{{ item.prenoms || '' }} {{ item.nom || '' }}</td>
                                            <td>{{ item.email || '-' }}</td>
                                            <td>{{ item.telephone || '-' }}</td>
                                            <td>{{ item.classe?.nom || '-' }}</td>
                                            <td><span class="badge" :class="item.statut === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + item.statut) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.apprenants.show', item.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('academique.apprenants.edit', item.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <Link
                                                        :href="route('academique.inscriptions.create', { apprenant_id: item.id })"
                                                        class="btn btn-success"
                                                        title="Inscrire cet apprenant"
                                                    >
                                                        <span class="fa fa-user-check"></span>
                                                    </Link>
                                                    <Link
                                                        :href="route('academique.dossiers_apprenants.create', { apprenant_id: item.id })"
                                                        class="btn btn-info text-white"
                                                        title="Dossier de l'apprenant"
                                                    >
                                                        <span class="fa fa-folder-open"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(item)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="item.statut === 'actif'" @click="confirmDeactivate(item)" class="btn btn-danger" :title="t('actions.deactivate')">
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
                                        <td colspan="6" class="text-center">{{ t('common.no_data') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="apprenants?.data && apprenants?.data.length > 0">
                                <div v-for="item in apprenants?.data" :key="'m-' + item.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.nom') || 'Nom' }}</span>
                                            <span class="mobile-card-value">{{ item.prenoms || '' }} {{ item.nom || '' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.email') || 'Email' }}</span>
                                            <span class="mobile-card-value">{{ item.email || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.telephone') || 'Téléphone' }}</span>
                                            <span class="mobile-card-value">{{ item.telephone || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.classe') || 'Classe' }}</span>
                                            <span class="mobile-card-value">{{ item.classe?.nom || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="item.statut === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + item.statut) }}</span></span>
                                        </div>
                                        <div class="mobile-card-actions">
                                            <Link :href="route('academique.apprenants.show', item.id)" class="btn btn-secondary btn-sm" :title="t('actions.view')">
                                                <span class="fa fa-eye"></span>
                                            </Link>
                                            <Link :href="route('academique.apprenants.edit', item.id)" class="btn btn-primary btn-sm" :title="t('actions.edit')">
                                                <span class="fa fa-edit"></span>
                                            </Link>
                                            <Link
                                                :href="route('academique.inscriptions.create', { apprenant_id: item.id })"
                                                class="btn btn-success btn-sm"
                                                title="Inscrire"
                                            >
                                                <span class="fa fa-user-check"></span>
                                            </Link>
                                            <Link
                                                :href="route('academique.dossiers_apprenants.create', { apprenant_id: item.id })"
                                                class="btn btn-info text-white btn-sm"
                                                title="Dossier"
                                            >
                                                <span class="fa fa-folder-open"></span>
                                            </Link>
                                            <button @click="confirmDelete(item)" class="btn btn-danger btn-sm" :title="t('actions.delete')">
                                                <span class="fa fa-trash"></span>
                                            </button>
                                            <button v-if="item.statut === 'actif'" @click="confirmDeactivate(item)" class="btn btn-danger btn-sm" :title="t('actions.deactivate')">
                                                <span class="fa fa-ban"></span>
                                            </button>
                                            <button v-else @click="confirmActivate(item)" class="btn btn-success btn-sm" :title="t('actions.activate')">
                                                <span class="fa fa-check"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Pagination -->
            <Pagination :data="apprenants" :preserve-scroll="true" />
        </div>
        <!-- Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="deleteMode ? t('common.confirm_delete') : (deactivateMode ? t('common.confirm_deactivate') : t('common.confirm_activate'))"
            :message="deleteMode ? t('messages.confirm_delete') : (deactivateMode ? t('messages.confirm_deactivate') : t('messages.confirm_activate'))"
            @confirm="deleteMode ? deleteItem() : toggleStatus()"
            @update:show="closeModal"
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
