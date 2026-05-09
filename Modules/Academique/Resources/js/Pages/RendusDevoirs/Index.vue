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
    title: String,
    rendusDevoirs: Object,
    filters: Object,
});
const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);
// Filtres de recherche
const searchFilters = ref({
    search: props.filters?.search || '',
});
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
    router.get(route('academique.rendus_devoirs.index'), searchFilters.value, {
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
  router.get(route('academique.rendus_devoirs.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('academique.rendus_devoirs.destroy', itemToDelete.value.id), { method: 'delete', preserveScroll: true,
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
        router.visit(route('academique.rendus_devoirs.statut', itemToDelete.value.id), { method: 'put',
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
// Make rendusDevoirs accessible in template from props or page
const rendusDevoirs = props.rendusDevoirs || page.props.rendusDevoirs;
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
                    <div class="dashboard-btn" v-if="can('rendus_devoirs-create')">
                        <Link :href="route('academique.rendus_devoirs.create')" class="btn btn-primary">
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
                            :placeholder="t('common.search') || 'Recherche'"
                            style="height: 32px; font-size: 13px; width: 100%;"
                        />
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
                                        <th>{{ t('common.apprenant') || 'Apprenant' }}</th>
                                        <th>{{ t('common.devoir') || 'Devoir' }}</th>
                                        <th>{{ t('fields.date_rendu') || 'Date Remise' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="rendusDevoirs?.data && rendusDevoirs?.data.length > 0">
                                        <tr v-for="item in rendusDevoirs?.data" :key="item.id">
                                            <td>{{ item.apprenant?.user?.prenoms }} {{ item.apprenant?.user?.nom }}</td>
                                            <td>{{ item.devoir?.titre }}</td>
                                            <td>{{ item.date_rendu }}</td>
                                            <td><span class="badge" :class="!item.deleted_at ? 'bg-success' : 'bg-danger'">{{ item.deleted_at ? t('common.inactif') : t('common.actif') }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.rendus_devoirs.show', item.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('academique.rendus_devoirs.edit', item.id)" class="btn btn-primary" :title="t('actions.edit')">
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
                                        <td colspan="5" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="rendusDevoirs?.data && rendusDevoirs?.data.length > 0">
                                <div v-for="item in rendusDevoirs?.data" :key="'m-' + item.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.apprenant') || 'Apprenant' }}</span>
                                            <span class="mobile-card-value">{{ item.apprenant?.user?.prenoms }} {{ item.apprenant?.user?.nom }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.devoir') || 'Devoir' }}</span>
                                            <span class="mobile-card-value">{{ item.devoir?.titre }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.date_rendu') || 'Date Remise' }}</span>
                                            <span class="mobile-card-value">{{ item.date_rendu }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="!item.deleted_at ? 'bg-success' : 'bg-danger'">{{ item.deleted_at ? t('common.inactif') : t('common.actif') }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('academique.rendus_devoirs.show', item.id)" class="btn btn-secondary btn-sm" :title="t('actions.view')"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('academique.rendus_devoirs.edit', item.id)" class="btn btn-primary btn-sm" :title="t('actions.edit')"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(item)" class="btn btn-danger btn-sm" :title="t('actions.delete')"><span class="fa fa-trash"></span></button>
                                        <button v-if="!item.deleted_at" @click="confirmDeactivate(item)" class="btn btn-danger btn-sm" :title="t('actions.deactivate')"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(item)" class="btn btn-success btn-sm" :title="t('actions.activate')"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <!-- Pagination -->
                        <Pagination :data="rendusDevoirs" :preserve-scroll="true" />
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
