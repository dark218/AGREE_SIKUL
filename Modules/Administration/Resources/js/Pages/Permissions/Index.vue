<script setup>
import { ref } from 'vue';
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
    permissions: Object,
});
// Modal de suppression
const showDeleteModal = ref(false);
const permissionToDelete = ref(null);
// Confirmation de suppression
function confirmDelete(permission) {
    permissionToDelete.value = permission;
    showDeleteModal.value = true;
}
// Supprimer la permission
function deletePermission() {
    if (permissionToDelete.value) {
        showDeleteLoader();
        router.put(route('administration.permissions.statut', permissionToDelete.value.id), {}, {
            onSuccess: () => {
                showDeleteModal.value = false;
                permissionToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            }
        });
    }
}
function closeDeleteModal() {
    showDeleteModal.value = false;
    permissionToDelete.value = null;
}
const page = usePage();
const { can } = usePermissions();
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div v-if="can('permission-create')" class="dashboard-btn">
                        <Link :href="route('administration.permissions.create')" class="btn btn-primary">{{ t("common.add") }}</Link>
                    </div>
                </div>
            </div>
            <!-- Alert Messages -->
            <AlertMessage />
            <hr class="divider">
            <!-- Tableau -->
            <div class="card-body">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Fonctionnalité</th>
                                    <th>{{ t("common.name") }}</th>
                                    <th>Guard</th>
                                    <th>{{ t("common.actions") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="permissions.data && permissions.data.length > 0" v-for="permission in permissions.data" :key="permission.id">
                                    <td>{{ permission.feature || '-' }}</td>
                                    <td>{{ permission.name }}</td>
                                    <td>{{ permission.guard_name }}</td>
                                    <td class="fit">
                                        <div class="action-buttons">
                                            <Link :href="route('administration.permissions.show', permission.id)" class="btn btn-secondary btn-sm" :title="t('common.show')">
                                                <i class="fa fa-eye"></i>
                                            </Link>
                                            <Link :href="route('administration.permissions.edit', permission.id)" class="btn btn-primary btn-sm" :title="t('common.edit')">
                                                <i class="fa fa-edit"></i>
                                            </Link>
                                            <button v-if="can('permission-statut')" @click="confirmDelete(permission)" class="btn btn-danger btn-sm" :title="t('common.delete')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else>
                                    <td colspan="4" class="text-center">{{ t("common.noData") }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Pagination -->
                        <Pagination :data="permissions" :preserve-scroll="true" :preserve-state="true" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Confirmation"
            message="Êtes-vous sûr de vouloir supprimer cette permission ?"
            sub-message="Cette action peut être annulée."
            confirm-text="t('actions.delete')"
            confirm-class="btn-danger"
            @confirm="deletePermission"
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
