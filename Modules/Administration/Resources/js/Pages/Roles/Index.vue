<script setup>
import { ref, watch } from 'vue';
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
    title: {
        type: String,
        default: 'Profil'
    },
    roles: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});
const page = usePage();
// État de recherche
const searchName = ref(props.filters.name || '');
// Modal de confirmation
const showDeleteModal = ref(false);
const roleToDelete = ref(null);
// Recherche
function search() {
    router.get(route('administration.roles.index'), {
        name: searchName.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}
// Réinitialiser la recherche
function resetSearch() {
    searchName.value = '';
    router.get(route('administration.roles.index'));
}
// Ouvrir le modal de suppression
function confirmDelete(role) {
    roleToDelete.value = role;
    showDeleteModal.value = true;
}
// Toggler le statut (activate/deactivate)
function deleteRole() {
    if (roleToDelete.value) {
        showDeleteLoader();
        router.put(route('administration.roles.statut', roleToDelete.value.id), {}, {
            onSuccess: () => {
                showDeleteModal.value = false;
                roleToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            }
        });
    }
}
// Hard delete le rôle
function hardDeleteRole(role) {
    if (confirm(t('messages.confirm.delete.warning'))) {
        showDeleteLoader();
        router.delete(route('administration.roles.destroy', role.id), {
            onSuccess: () => {
                roleToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            }
        });
    }
}
// Fermer le modal
function closeDeleteModal() {
    showDeleteModal.value = false;
    roleToDelete.value = null;
}
const { can } = usePermissions();
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <!-- Header -->
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('administration.roles.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <!-- Alert Messages -->
            <AlertMessage />
            <!-- Filtre de recherche -->
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <div class="col-6 p-1">
                        <input 
                            v-model="searchName"
                            type="text" 
                            class="form-control search-slt"
                            :placeholder="t('fields.name')"
                        >
                    </div>
                    <div class="col-2 pl-2" >
                        <button type="submit" class="btn btn-primary wrn-btn radius-0">
                            <i class="fa fa-search"></i>
                        </button>
                    <button type="button" @click="resetSearch" class="btn btn-secondary wrn-btn radius-0">
                        <i class="fa fa-redo"></i> <i class="fa fa-sync"></i> {{ t('actions.reset') }}
                    </button>
                    </div>
                </form>
            </div>
            <!-- Tableau des rôles -->
            <div class="card-body">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>{{ t('fields.name') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="roles.data && roles.data.length > 0" v-for="role in roles.data" :key="role.id">
                                    <td>{{ role.name }}</td>
                                    <td class="fit">
                                        <div class="action-buttons">
                                            <!-- Voir -->
                                            <Link 
                                                :href="route('administration.roles.show', role.id)" 
                                                class="btn btn-secondary btn-sm"
                                                :title="t('actions.view')"
                                            >
                                                <i class="fa fa-eye"></i>
                                            </Link>
                                            <!-- Modifier -->
                                            <Link
                                                :href="route('administration.roles.edit', role.id)"
                                                class="btn btn-primary btn-sm"
                                                :title="t('actions.edit')"
                                            >
                                                <i class="fa fa-edit"></i>
                                            </Link>
                                            <!-- Déactiver/Activer -->
                                            <button
                                                v-if="role.deleted_at"
                                                @click="confirmDelete(role)"
                                                class="btn btn-success btn-sm"
                                                :title="t('actions.activate')"
                                            >
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button
                                                v-else
                                                @click="confirmDelete(role)"
                                                class="btn btn-warning btn-sm"
                                                :title="t('actions.deactivate')"
                                            >
                                                <i class="fa fa-ban"></i>
                                            </button>
                                            <!-- Supprimer -->
                                            <button
                                                @click="hardDeleteRole(role)"
                                                class="btn btn-danger btn-sm"
                                                :title="t('actions.delete')"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else>
                                    <td colspan="2" class="text-center">
                                        {{ t('common.emptyList') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Pagination -->
                        <Pagination :data="roles" :preserve-scroll="true" :preserve-state="true" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation de suppression -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="roleToDelete?.deleted_at ? t('messages.confirm.activate.message') : t('messages.confirm.deactivate.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            :confirm-text="t('actions.delete')"
            confirm-class="btn-danger"
            @confirm="deleteRole"
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
