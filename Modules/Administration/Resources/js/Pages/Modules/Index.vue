<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();
defineOptions({
    layout: DashboardLayout
});
const props = defineProps({
    title: String,
    modules: Object,
});
// Modal de suppression
const showDeleteModal = ref(false);
const moduleToDelete = ref(null);
// Confirmation de suppression
function confirmDelete(module) {
    moduleToDelete.value = module;
    showDeleteModal.value = true;
}
// Supprimer le module
function deleteModule() {
    if (moduleToDelete.value) {
        showDeleteLoader();
        router.put(route('administration.modules.statut', moduleToDelete.value.id), {}, {
            onSuccess: () => {
                showDeleteModal.value = false;
                moduleToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            }
        });
    }
}
function closeDeleteModal() {
    showDeleteModal.value = false;
    moduleToDelete.value = null;
}
const page = usePage();
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div v-if="can('module-create')" class="dashboard-btn">
                        <Link :href="route('administration.modules.create')" class="btn btn-primary">
                            {{ t('common.add') }}
                        </Link>
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
                                    <th>{{ t('common.name') }}</th>
                                    <th>{{ t('modules.administration.modules.fields.icon') }}</th>
                                    <th>{{ t('modules.administration.modules.fields.order') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="modules.data && modules.data.length > 0" v-for="module in modules.data" :key="module.id">
                                    <td>{{ module.libelle }}</td>
                                    <td>{{ module.icone }}</td>
                                    <td>{{ module.ordre }}</td>
                                    <td class="fit">
                                        <div class="action-buttons">
                                            <Link :href="route('administration.modules.show', module.id)" class="btn btn-secondary btn-sm" :title="t('common.show')">
                                                <i class="fa fa-eye"></i>
                                            </Link>
                                            <Link :href="route('administration.modules.edit', module.id)" class="btn btn-primary btn-sm" :title="t('common.edit')">
                                                <i class="fa fa-edit"></i>
                                            </Link>
                                            <button v-if="can('module-statut')" @click="confirmDelete(module)" class="btn btn-danger btn-sm" :title="t('common.delete')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else>
                                    <td colspan="4" class="text-center">{{ t('common.noData') }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Pagination -->
                        <Pagination :data="modules" :preserve-scroll="true" :preserve-state="true" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation -->
         <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="t('messages.confirm.delete.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            confirm-text="t('actions.delete')"
            confirm-class="btn-danger"
            @confirm="deleteModule"
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
