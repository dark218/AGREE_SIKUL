<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
import { useLoader } from '@/Composables/useLoader';
import ApprenantsBadges from '@/Components/Common/ApprenantsBadges.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    tuteurs: Object,
    filters: Object,
});
const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);
// Filtres de recherche
const searchFilters = ref({
    search: props.filters?.search || '',
});
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Recherche', icon: 'fa-search', width: '220px' },
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
    router.get(route('tuteurs.index'), searchFilters.value, {
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
  router.get(route('tuteurs.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('tuteurs.destroy', itemToDelete.value.id), { method: 'delete', preserveScroll: true,
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
        router.visit(route('tuteurs.statut', itemToDelete.value.id), { method: 'put',
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
// Make tuteurs accessible in template from props or page
const tuteurs = props.tuteurs || page.props.tuteurs;
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('tuteurs-create')">
                        <Link :href="route('tuteurs.create')" class="btn btn-primary">
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
                                        <th>{{ t('common.nom') || 'Nom' }}</th>
                                        <th>Apprenants</th>
                                        <th>{{ t('fields.relation') || 'Relation' }}</th>
                                        <th>{{ t('common.profession') || 'Profession' }}</th>
                                        <th>{{ t('common.employeur') || 'Employeur' }}</th>
                                        <th>{{ t('common.numero_urgence') || 'Numéro Urgence' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="tuteurs?.data && tuteurs?.data.length > 0">
                                        <tr v-for="item in tuteurs?.data" :key="item.id">
                                            <td><strong>{{ item.nom || '-' }}</strong></td>
                                            <td>
                                                <ApprenantsBadges
                                                    :apprenants="item.apprenants || (item.apprenant ? [item.apprenant] : [])"
                                                    mode="inline"
                                                />
                                            </td>
                                            <td>{{ item.relation || '-' }}</td>
                                            <td>{{ item.profession || '-' }}</td>
                                            <td>{{ item.employeur || '-' }}</td>
                                            <td>{{ item.numero_urgence || '-' }}</td>
                                            <td><span class="badge" :class="!item.deleted_at ? 'bg-success' : 'bg-danger'">{{ item.deleted_at ? t('common.inactif') : t('common.actif') }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('tuteurs.show', item.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('tuteurs.edit', item.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(item)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="!item.deleted_at" @click="confirmDeactivate(item)" class="btn btn-danger" :title="t('actions.deactivate')">
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
                                        <td colspan="7" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="tuteurs?.data && tuteurs?.data.length > 0">
                                <div v-for="item in tuteurs?.data" :key="'m-' + item.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.nom') || 'Nom' }}</span>
                                            <span class="mobile-card-value">{{ item.nom || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.relation') || 'Relation' }}</span>
                                            <span class="mobile-card-value">{{ item.relation || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.profession') || 'Profession' }}</span>
                                            <span class="mobile-card-value">{{ item.profession || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.employeur') || 'Employeur' }}</span>
                                            <span class="mobile-card-value">{{ item.employeur || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.numero_urgence') || 'Numéro Urgence' }}</span>
                                            <span class="mobile-card-value">{{ item.numero_urgence || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="!item.deleted_at ? 'bg-success' : 'bg-danger'">{{ item.deleted_at ? t('common.inactif') : t('common.actif') }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('tuteurs.show', item.id)" class="btn btn-secondary btn-sm" :title="t('actions.view')"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('tuteurs.edit', item.id)" class="btn btn-primary btn-sm" :title="t('actions.edit')"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(item)" class="btn btn-danger btn-sm" :title="t('actions.delete')"><span class="fa fa-trash"></span></button>
                                        <button v-if="!item.deleted_at" @click="confirmDeactivate(item)" class="btn btn-danger btn-sm" :title="t('actions.deactivate')"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(item)" class="btn btn-success btn-sm" :title="t('actions.activate')"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <!-- Pagination -->
                        <Pagination :data="tuteurs" :preserve-scroll="true" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="deleteMode ? t('common.confirm_delete') : (deactivateMode ? t('common.confirm_deactivate') : t('common.confirm_activate'))"
            :message="deleteMode ? t('messages.confirm.delete.message') : (deactivateMode ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message'))"
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
