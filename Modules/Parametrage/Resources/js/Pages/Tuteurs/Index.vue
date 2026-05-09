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
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();
const props = defineProps({ tuteurs: Object, filters: Object });
const searchFilters = ref({ search: props.filters?.search || '', statut: props.filters?.statut || '' });
let searchTimeout;
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const search = () => {
    router.get(route('parametrage.tuteurs.index'), searchFilters.value, { preserveState: true, preserveScroll: true });
};
const resetFilters = () => {
  searchFilters.value = { search: '', statut: '' };
  router.get(route('parametrage.tuteurs.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('parametrage.tuteurs.destroy', itemToDelete.value?.id), { method: 'delete', preserveScroll: true,
            onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; },
            onFinish: () => hideLoader() });
    }
};
const closeModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
};
const page = usePage();
const tuteurs = props.tuteurs || page.props.tuteurs;
watch(
  () => searchFilters.value,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => { search(); }, 500);
  },
  { deep: true }
);
</script>
<template>
    <Head :title="page.props.title || 'Tuteurs'" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title || 'Tuteurs' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('parametrage.tuteurs.create')" class="btn btn-primary"><i class="fa fa-plus"></i> {{ t('actions.add') }}</Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0">
                <form @submit.prevent="search" style="display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap;">
                    <div style="width: 150px;">
                        <input type="text" v-model="searchFilters.search" class="form-control form-control-sm" :placeholder="t('fields.search')" style="height: 32px; font-size: 13px; width: 100%;" />
                    </div>
                    <div style="width: 150px;">
                        <select v-model="searchFilters.statut" class="form-control form-control-sm" style="height: 32px; width: 100%;">
                            <option value="">{{ t('fields.statut') || 'Statut' }}</option>
                            <option value="actif">{{ t('common.active') || 'Actif' }}</option>
                            <option value="non_actif">{{ t('common.inactive') || 'Inactif' }}</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 4px;">
                        <button type="submit" class="btn btn-primary btn-sm" style="height: 32px; padding: 0 10px;"><i class="fa fa-search"></i></button>
                        <button type="button" class="btn btn-secondary btn-sm" @click="resetFilters" style="height: 32px; padding: 0 10px;"><i class="fa fa-redo"></i></button>
                    </div>
                </form>
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.nom') || 'Nom' }}</th>
                                        <th>{{ t('fields.prenoms') || 'Prénoms' }}</th>
                                        <th>{{ t('fields.email') || 'Email' }}</th>
                                        <th>{{ t('fields.telephone') || 'Téléphone' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="tuteurs?.data && tuteurs?.data.length > 0">
                                        <tr v-for="tuteur in tuteurs?.data" :key="tuteur.id">
                                            <td>{{ tuteur.nom || '' }}</td>
                                            <td>{{ tuteur.prenoms || '' }}</td>
                                            <td>{{ tuteur.email || '' }}</td>
                                            <td>{{ tuteur.telephone || '' }}</td>
                                            <td><span class="badge" :class="tuteur.statut === 'actif' ? 'bg-success' : 'bg-danger'">{{ t('common.' + tuteur.statut) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('parametrage.tuteurs.show', tuteur?.id)" class="btn btn-secondary" :title="t('actions.view')"><span class="fa fa-eye"></span></Link>
                                                    <Link :href="route('parametrage.tuteurs.edit', tuteur?.id)" class="btn btn-primary" :title="t('actions.edit')"><span class="fa fa-edit"></span></Link>
                                                    <button @click="confirmDelete(tuteur)" class="btn btn-danger" :title="t('actions.delete')"><span class="fa fa-trash"></span></button>
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
                        <Pagination :data="tuteurs" />
                    </div>
                </div>
            </div>
        </div>
        <ConfirmModal :show="showDeleteModal" :title="t('messages.confirm.delete.title')" :message="t('messages.confirm.delete.message')" :sub-message="t('messages.confirm.delete.warning')" @close="closeModal" @confirm="deleteItem" :confirm-text="t('actions.delete')" :confirm-class="'btn-danger'" />
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
