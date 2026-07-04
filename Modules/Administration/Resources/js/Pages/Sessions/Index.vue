<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({
    layout: DashboardLayout
});
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    sessions: Object,
    users: Array,
    filters: Object,
});
// Filtres de recherche
const searchFilters = ref({
    user_id: props.filters?.user_id || '',
});
const filterFields = computed(() => [
    { key: 'user_id', type: 'select', placeholder: 'Utilisateur', options: props.users, optionValue: 'id', optionLabel: 'name', width: '190px' },
]);
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
const showDeleteAllModal = ref(false);
const sessionToDelete = ref(null);
const userToDeleteSessions = ref(null);
// Recherche
function search() {
    router.get(route('administration.session.index'), {
        user_id: searchFilters.value.user_id || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}
// Réinitialiser les filtres
function resetFilters() {
    searchFilters.value = { user_id: '' };
    router.get(route('administration.session.index'));
}
// Confirmation de suppression d'une session
function confirmDelete(session) {
    sessionToDelete.value = session;
    showDeleteModal.value = true;
}
// Confirmation de suppression de toutes les sessions d'un utilisateur
function confirmDeleteAll() {
    if (!searchFilters.value.user_id) {
        return;
    }
    userToDeleteSessions.value = searchFilters.value.user_id;
    showDeleteAllModal.value = true;
}
// Supprimer une session
function deleteSession() {
    if (sessionToDelete.value) {
        showDeleteLoader();
        router.delete(route('administration.session.destroy', sessionToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                sessionToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            }
    });
    }
}
// Supprimer toutes les sessions d'un utilisateur
function deleteAllSessions() {
    if (userToDeleteSessions.value) {
        showDeleteLoader();
        router.delete(route('administration.session.destroyByUser', userToDeleteSessions.value), {
            onSuccess: () => {
                showDeleteAllModal.value = false;
                userToDeleteSessions.value = null;
            },
            onFinish: () => {
                hideLoader();
            }
    });
    }
}
function closeDeleteModal() {
    showDeleteModal.value = false;
    sessionToDelete.value = null;
}
function closeDeleteAllModal() {
    showDeleteAllModal.value = false;
    userToDeleteSessions.value = null;
}
// Obtenir l'icône du navigateur
function getBrowserIcon(browser) {
    const icons = {
        'Chrome': 'fab fa-chrome',
        'Firefox': 'fab fa-firefox',
        'Safari': 'fab fa-safari',
        'Edge': 'fab fa-edge',
        'Opera': 'fab fa-opera',
        'Internet Explorer': 'fab fa-internet-explorer',
    };
    return icons[browser] || 'fas fa-globe';
}
// Obtenir l'icône du système d'exploitation
function getOsIcon(os) {
    const icons = {
        'Windows': 'fab fa-windows',
        'macOS': 'fab fa-apple',
        'Linux': 'fab fa-linux',
        'Android': 'fab fa-android',
        'iOS': 'fab fa-apple',
    };
    return icons[os] || 'fas fa-desktop';
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
                    <button
                        v-if="can('session-delete') && searchFilters.user_id"
                        @click="confirmDeleteAll"
                        class="btn btn-danger"
                    >
                        <i class="fas fa-trash-alt me-1"></i>
                        {{ t('modules.administration.sessions.deleteAll') }}
                    </button>
                </div>
            </div>
            <!-- Alert Messages -->
            <AlertMessage />
            <!-- Filtres de recherche -->
            <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters"></FilterBar>
            <!-- Tableau des sessions -->
            <div class="card-body">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>{{ t('modules.administration.sessions.fields.user') }}</th>
                                    <th>{{ t('modules.administration.sessions.fields.ipAddress') }}</th>
                                    <th>{{ t('modules.administration.sessions.fields.browser') }}</th>
                                    <th>{{ t('modules.administration.sessions.fields.lastActivity') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="sessions.data && sessions.data.length > 0" v-for="session in sessions.data" :key="session.id">
                                    <td>
                                        <div class="user-info-cell">
                                            <strong>{{ session.user_name }}</strong>
                                            <small class="text-muted d-block">{{ session.user_login }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <code>{{ session.ip_address || '-' }}</code>
                                    </td>
                                    <td>
                                        <div class="browser-info">
                                            <i :class="getBrowserIcon(session.user_agent.browser)" class="me-1"></i>
                                            {{ session.user_agent.browser }}
                                            <span class="text-muted mx-1">|</span>
                                            <i :class="getOsIcon(session.user_agent.os)" class="me-1"></i>
                                            {{ session.user_agent.os }}
                                        </div>
                                    </td>
                                    <td>
                                        <span :class="session.is_current ? 'badge badge-success' : ''">
                                            {{ session.last_activity }}
                                            <span v-if="session.is_current" class="ms-1">({{ t('modules.administration.sessions.currentSession') }})</span>
                                        </span>
                                    </td>
                                    <td class="fit">
                                        <div class="action-buttons">
                                            <button
                                                v-if="can('session-delete') && !session.is_current"
                                                @click="confirmDelete(session)"
                                                class="btn btn-danger btn-sm"
                                                :title="t('actions.delete')"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <span v-if="session.is_current" class="badge badge-info">
                                                {{ t('modules.administration.sessions.active') }}
                                            </span>
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
                        <Pagination :data="sessions" :preserve-scroll="true" :preserve-state="true" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation suppression session -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="sessionToDelete?.etat === 'actif' || sessionToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            confirm-text="Supprimer"
            confirm-class="btn-danger"
            @confirm="deleteSession"
            @cancel="closeDeleteModal"
        />
        <!-- Modal de confirmation suppression toutes les sessions -->
        <ConfirmModal
            :show="showDeleteAllModal"
            :title="t('modules.administration.sessions.deleteAllTitle')"
            :message="t('modules.administration.sessions.confirmDeleteAll')"
            :sub-message="t('messages.confirm.delete.warning')"
            confirm-text="Supprimer tout"
            confirm-class="btn-danger"
            @confirm="deleteAllSessions"
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
<style scoped>
.user-info-cell {
    line-height: 1.4;
}
.browser-info {
    display: flex;
    align-items: center;
    font-size: 13px;
}
code {
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    color: #475569;
}
</style>
