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
import { useLoader } from '@/composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({
    layout: DashboardLayout
});
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    users: Object,
    roles: Array,
    pays: Array,
    kyc_statuss: Array,
    statuts: Array,
    filters: Object,
});
const page = usePage();
// kyc_statuss est déjà un array avec {value, label} depuis le backend
const kycStatusOptions = computed(() => {
    return (props.kyc_statuss || []).map(item => ({
        id: item.value,
        label: item.label
    }));
});
// statuts est déjà un array avec {value, label} depuis le backend
const statutOptions = computed(() => {
    return (props.statuts || []).map(item => ({
        id: item.value,
        label: item.label
    }));
});
// État des filtres
const searchFilters = ref({
    login: props.filters?.login || '',
    email: props.filters?.email || '',
    role_id: props.filters?.role_id || '',
    kyc_status: props.filters?.kyc_status || '',
    statut: props.filters?.statut || '',
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
const userToDelete = ref(null);
// Recherche
function search() {
    router.get(route('administration.users.index'), {
        login: searchFilters.value.login || undefined,
        email: searchFilters.value.email || undefined,
        role_id: searchFilters.value.role_id || undefined,
        kyc_status: searchFilters.value.kyc_status || undefined,
        statut: searchFilters.value.statut || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}
// Réinitialiser les filtres
function resetFilters() {
    searchFilters.value = { login: '', email: '', role_id: '', kyc_status: '', statut: '' };
    router.get(route('administration.users.index'));
}
// Confirmation de suppression
function confirmDelete(user) {
    userToDelete.value = user;
    showDeleteModal.value = true;
}
// Toggler le statut (activate/deactivate)
function deleteUser() {
    if (userToDelete.value) {
        showDeleteLoader();
        router.put(route('administration.users.statut', userToDelete.value.id), {}, {
            onSuccess: () => {
                showDeleteModal.value = false;
                userToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            }
    });
    }
}
// Hard delete l'utilisateur
function hardDeleteUser(user) {
    if (confirm(t('messages.confirm.delete.warning'))) {
        showDeleteLoader();
        router.delete(route('administration.users.destroy', user.id), {
            onSuccess: () => {
                userToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            }
        });
    }
}
function closeDeleteModal() {
    showDeleteModal.value = false;
    userToDelete.value = null;
}
// Obtenir la classe du badge KYC
function getKycBadgeClass(status) {
    const classes = {
        'non_verifie': 'bg-warning',
        'en_attente': 'bg-info',
        'verifie': 'bg-success',
        'rejete': 'bg-danger',
    };
    return classes[status] || 'bg-secondary';
}
// Obtenir le libellé du KYC
function getKycLabel(status) {
    const labels = {
        'non_verifie': t('kyc.non_verifie'),
        'en_attente': t('kyc.en_attente'),
        'verifie': t('kyc.verifie'),
        'rejete': t('kyc.rejete'),
    };
    return labels[status] || status;
}
// Obtenir la classe du badge Statut
function getStatutBadgeClass(status) {
    const classes = {
        'inactif': 'bg-secondary',
        'actif': 'bg-success',
        'suspendu': 'bg-warning',
        'bloque': 'bg-danger',
        'supprime': 'bg-secondary',
    };
    return classes[status] || 'bg-secondary';
}
// Obtenir le libellé du Statut
function getStatutLabel(status) {
    const labels = {
        'inactif': t('statuts.inactif'),
        'actif': t('statuts.actif'),
        'suspendu': t('statuts.suspendu'),
        'bloque': t('statuts.bloque'),
        'supprime': t('statuts.supprime'),
    };
    return labels[status] || status;
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
                    <div class="dashboard-btn">
                        <Link :href="route('administration.users.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <!-- Alert Messages -->
            <AlertMessage />
            <!-- Filtres de recherche -->
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <div class="col-2 p-1">
                        <input
                            v-model="searchFilters.login"
                            type="text"
                            class="form-control search-slt"
                            :placeholder="t('fields.login')"
                        >
                    </div>
                    <div class="col-2 p-1">
                        <input
                            v-model="searchFilters.email"
                            type="text"
                            class="form-control search-slt"
                            :placeholder="t('fields.email')"
                        >
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect
                            v-model="searchFilters.role_id"
                            :options="roles"
                            option-value="id"
                            option-label="label"
                            :placeholder="t('fields.role')"
                        />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect
                            v-model="searchFilters.kyc_status"
                            :options="kycStatusOptions"
                            option-value="id"
                            option-label="label"
                            placeholder="KYC"
                            :searchable="false"
                        />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect
                            v-model="searchFilters.statut"
                            :options="statutOptions"
                            option-value="id"
                            option-label="label"
                            :placeholder="t('common.status')"
                            :searchable="false"
                        />
                    </div>
                    <div class="col-2 p-1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            <!-- Tableau des utilisateurs -->
            <div class="card-body">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>{{ t('fields.login') }}</th>
                                    <th>{{ t('fields.email') }}</th>
                                    <th>{{ t('common.status') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="users.data && users.data.length > 0" v-for="user in users.data" :key="user.id">
                                    <td>{{ user.login }}</td>
                                    <td>{{ user.email }}</td>
                                    <td>
                                        <span :class="['badge text-dark rounded-pill', getStatutBadgeClass(user.statut)]">
                                            {{ getStatutLabel(user.statut) }}
                                        </span>
                                    </td>
                                    <td class="fit">
                                        <div class="action-buttons">
                                            <!-- Voir -->
                                            <Link
                                                :href="route('administration.users.show', user.uuid)"
                                                class="btn btn-secondary btn-sm"
                                                :title="t('actions.view')"
                                            >
                                                <i class="fa fa-eye"></i>
                                            </Link>
                                            <!-- Modifier -->
                                            <Link
                                                :href="route('administration.users.edit', user.uuid)"
                                                class="btn btn-primary btn-sm"
                                                :title="t('actions.edit')"
                                            >
                                                <i class="fa fa-edit"></i>
                                            </Link>
                                            <!-- Déactiver/Activer -->
                                            <button
                                                v-if="user.statut !== 'actif'"
                                                @click="confirmDelete(user)"
                                                class="btn btn-success btn-sm"
                                                :title="t('actions.activate')"
                                            >
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button
                                                v-else
                                                @click="confirmDelete(user)"
                                                class="btn btn-warning btn-sm"
                                                :title="t('actions.deactivate')"
                                            >
                                                <i class="fa fa-ban"></i>
                                            </button>
                                            <!-- Supprimer -->
                                            <button
                                                @click="hardDeleteUser(user)"
                                                class="btn btn-danger btn-sm"
                                                :title="t('actions.delete')"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else>
                                    <td colspan="4" class="text-center">
                                        {{ t('common.emptyList') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Pagination -->
                         <Pagination :data="users" :preserve-scroll="true" :preserve-state="true" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="userToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            :confirm-text="t('actions.delete')"
            confirm-class="btn-danger"
            @confirm="deleteUser"
            @cancel="closeDeleteModal"
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
