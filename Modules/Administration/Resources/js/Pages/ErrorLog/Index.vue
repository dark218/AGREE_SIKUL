<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({
    layout: DashboardLayout
});
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    errorlogs: Object,
    filters: Object,
});
const page = usePage();
// État des filtres
const searchFilters = ref({
    module: props.filters?.module || '',
    methode: props.filters?.methode || '',
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
const errorlogToDelete = ref(null);
// Modal de suppression totale
const showDeleteAllModal = ref(false);
// Recherche
function search() {
    router.get(route('administration.errorlog.index'), {
        module: searchFilters.value.module || undefined,
        methode: searchFilters.value.methode || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}
// Réinitialiser les filtres
function resetFilters() {
    searchFilters.value = { module: '', methode: '' };
    router.get(route('administration.errorlog.index'));
}
// Confirmation de suppression
function confirmDelete(errorlog) {
    errorlogToDelete.value = errorlog;
    showDeleteModal.value = true;
}
// Confirmation de suppression totale
function confirmDeleteAll() {
    showDeleteAllModal.value = true;
}
// Supprimer un errorlog
function deleteErrorLog() {
    if (errorlogToDelete.value) {
        showDeleteLoader();
        router.delete(route('administration.errorlog.destroy', errorlogToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                errorlogToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            }
        });
    }
}
// Supprimer tous les errorlogs
function deleteAllErrorLogs() {
    showDeleteLoader();
    router.delete(route('administration.errorlog.destroyAll'), {
        onSuccess: () => {
            showDeleteAllModal.value = false;
        },
        onFinish: () => {
            hideLoader();
        }
    });
}
function closeDeleteModal() {
    showDeleteModal.value = false;
    errorlogToDelete.value = null;
}
function closeDeleteAllModal() {
    showDeleteAllModal.value = false;
}
// Formater la date
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}
const { can } = usePermissions();
// Real-time search with debounce
watch(
  () => searchFilters.value,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      search();
    }, 500); // 500ms debounce
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
                    <div v-if="can('errorlog-delete') && errorlogs.data && errorlogs.data.length > 0" class="dashboard-btn">
                        <button @click="confirmDeleteAll" class="btn btn-danger">
                            <i class="fa fa-trash"></i> {{ t('actions.deleteAll') }}
                        </button>
                    </div>
                </div>
            </div>
            <!-- Alert Messages -->
            <AlertMessage />
            <!-- Filtres de recherche -->
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <div class="col-3 p-1">
                        <input
                            v-model="searchFilters.module"
                            type="text"
                            class="form-control search-slt"
                            :placeholder="t('fields.module')"
                        >
                    </div>
                    <div class="col-3 p-1">
                        <input
                            v-model="searchFilters.methode"
                            type="text"
                            class="form-control search-slt"
                            :placeholder="t('fields.methode')"
                        >
                    </div>
                    <div class="col-2 p-1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i>
                        </button>
                        <button type="button" @click="resetFilters" class="btn btn-secondary ms-2">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </form>
            </div>
            <!-- Tableau des error logs -->
            <div class="card-body">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>{{ t('fields.module') }}</th>
                                    <th>{{ t('fields.methode') }}</th>
                                    <th>{{ t('fields.message') }}</th>
                                    <th>{{ t('fields.date') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="errorlogs.data && errorlogs.data.length > 0" v-for="errorlog in errorlogs.data" :key="errorlog.id">
                                    <td>{{ errorlog.module }}</td>
                                    <td>{{ errorlog.methode }}</td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 300px;" :title="errorlog.message">
                                            {{ errorlog.message }}
                                        </span>
                                    </td>
                                    <td>{{ formatDate(errorlog.created_at) }}</td>
                                    <td class="fit">
                                        <div class="action-buttons">
                                            <!-- Voir -->
                                            <Link
                                                :href="route('administration.errorlog.show', errorlog.id)"
                                                class="btn btn-secondary btn-sm"
                                                :title="t('actions.view')"
                                            >
                                                <i class="fa fa-eye"></i>
                                            </Link>
                                            <!-- Supprimer -->
                                            <button
                                                v-if="can('errorlog-delete')"
                                                @click="confirmDelete(errorlog)"
                                                class="btn btn-danger btn-sm"
                                                :title="t('actions.delete')"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else>
                                    <td colspan="5" class="text-center">
                                        {{ t('common.emptyList') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Pagination -->
                         <Pagination :data="errorlogs" :preserve-scroll="true" :preserve-state="true" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation de suppression unique -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="errorlogToDelete?.etat === 'actif' || errorlogToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            confirm-text="Supprimer"
            confirm-class="btn-danger"
            @confirm="deleteErrorLog"
            @cancel="closeDeleteModal"
        />
        <!-- Modal de confirmation de suppression totale -->
        <ConfirmModal
            :show="showDeleteAllModal"
            :title="t('messages.confirm.deleteAll.title')"
            :message="t('messages.confirm.deleteAll.message')"
            :sub-message="t('messages.confirm.deleteAll.warning')"
            confirm-text="Tout supprimer"
            confirm-class="btn-danger"
            @confirm="deleteAllErrorLogs"
            @cancel="closeDeleteAllModal"
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
