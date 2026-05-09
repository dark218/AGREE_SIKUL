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
const { isLoading, loaderMessage, showDeleteLoader, hideLoader } = useLoader();
const props = defineProps({
    cours: Object,
    filters: Object,
});
const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
});
const statusOptions = [
    { id: 'actif', libelle: t('common.active') || 'Actif' },
    { id: 'non_actif', libelle: t('common.inactive') || 'Inactif' },
];
let searchTimeout;
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const search = () => {
    router.get(route('parametrage.cours.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};
const resetFilters = () => {
    searchFilters.value = { search: '', statut: '' };
    router.get(route('parametrage.cours.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('parametrage.cours.destroy', itemToDelete.value?.id), {
            method: 'delete',
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
            },
            onFinish: () => hideLoader(),
        });
    }
};
const page = usePage();

watch(
    () => searchFilters.value,
    () => performSearch(),
    { deep: true }
);
</script>
<template>
    <Head :title="t('entities.cours') || 'Cours'" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('entities.cours') || 'Cours' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <Link :href="route('parametrage.cours.create')" class="btn btn-primary" v-if="can('cours-create')">
                        {{ t('actions.add') || 'Ajouter' }}
                    </Link>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0">
                <div style="width: 150px;">
                    <input
                        type="text"
                        v-model="searchFilters.search"
                        class="form-control form-control-sm"
                        :placeholder="t('fields.search') || 'Rechercher'"
                        style="height: 32px; font-size: 13px;"
                    />
                </div>
                <div style="width: 150px;">
                    <select v-model="searchFilters.statut" class="form-control form-control-sm" style="height: 32px;">
                        <option value="">{{ t('fields.statut') || 'Statut' }}</option>
                        <option value="actif">{{ t('common.active') || 'Actif' }}</option>
                        <option value="non_actif">{{ t('common.inactive') || 'Inactif' }}</option>
                    </select>
                </div>
                <div style="display: flex; gap: 5px;">
                    <button @click="search" class="btn btn-sm btn-dark">
                        <i class="fa fa-search"></i>
                    </button>
                    <button @click="resetFilters" class="btn btn-sm btn-dark">
                        <i class="fa fa-refresh"></i>
                    </button>
                </div>
            </div>
            <table class="table table-sm" v-if="page.props.cours?.data?.length">
                <thead>
                    <tr>
                        <th>{{ t('fields.code') || 'Code' }}</th>
                        <th>{{ t('fields.titre') || 'Titre' }}</th>
                        <th>{{ t('fields.matiere') || 'Matière' }}</th>
                        <th>{{ t('fields.classe') || 'Classe' }}</th>
                        <th>{{ t('fields.statut') || 'Statut' }}</th>
                        <th>{{ t('actions.actions') || 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in page.props.cours.data" :key="item.id">
                        <td>{{ item.code }}</td>
                        <td>{{ item.titre }}</td>
                        <td>{{ item.matiere?.titre }}</td>
                        <td>{{ item.classe?.nom }}</td>
                        <td>
                            <span :class="'badge badge-' + (item.statut === 'actif' ? 'success' : 'danger')">
                                {{ t('common.' + item.statut) || item.statut }}
                            </span>
                        </td>
                        <td>
                            <Link :href="route('parametrage.cours.show', item?.id)" class="btn btn-xs btn-info">
                                {{ t('actions.view') || 'Voir' }}
                            </Link>
                            <Link :href="route('parametrage.cours.edit', item?.id)" class="btn btn-xs btn-warning" v-if="can('cours-update')">
                                {{ t('actions.edit') || 'Éditer' }}
                            </Link>
                            <button @click="confirmDelete(item)" class="btn btn-xs btn-danger" v-if="can('cours-delete')">
                                {{ t('actions.delete') || 'Supprimer' }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="alert alert-info">
                {{ t('messages.no_data') || 'Aucune donnée' }}
            </div>
            <Pagination :paginator="page.props.cours" />
        </div>
        <ConfirmModal
            :show="showDeleteModal"
            @update:show="showDeleteModal = $event"
            :title="t('messages.confirm.delete.title') || 'Confirmer la suppression'"
            :message="t('messages.confirm.delete.message') || 'Êtes-vous sûr de vouloir supprimer cet élément?'"
            @confirm="deleteItem"
        />
        <FullPageLoader :show="isLoading" :message="loaderMessage" />
    </div>
</template>
