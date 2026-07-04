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
import FilterBar from '@/Components/Common/FilterBar.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, showDeleteLoader, hideLoader } = useLoader();
const props = defineProps({ evaluations: Object, filters: Object });
const searchFilters = ref({ search: props.filters?.search || '', type: props.filters?.type || '', statut: props.filters?.statut || '' });
const typeOptions = [
    { id: 'devoir', libelle: 'Devoir' },
    { id: 'controle', libelle: 'Contrôle' },
    { id: 'examen', libelle: 'Examen' },
];
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher…', icon: 'fa-search', width: '220px' },
    { key: 'type', type: 'select', placeholder: 'Type', options: typeOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];
let searchTimeout;
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const search = () => router.get(route('parametrage.evaluations.index'), searchFilters.value, { preserveState: true });
const resetFilters = () => { searchFilters.value = { search: '', type: '', statut: '' }; router.get(route('parametrage.evaluations.index')); };
const confirmDelete = (item) => { itemToDelete.value = item; showDeleteModal.value = true; };
const deleteItem = () => { if (itemToDelete.value) { showDeleteLoader(); router.visit(route('parametrage.evaluations.destroy', itemToDelete.value?.id), { method: 'delete', onSuccess: () => { showDeleteModal.value = false; }, onFinish: () => hideLoader() }); } };
const page = usePage();
watch(() => searchFilters.value, () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => search(), 300); }, { deep: true });
</script>
<template>
    <Head :title="t('entities.evaluations') || 'Evaluations'" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('entities.evaluations') }}</h4>
            <Link :href="route('parametrage.evaluations.create')" class="btn btn-primary"><i class="fa fa-plus"></i> {{ t('actions.add') }}</Link>
        </div>
        <AlertMessage />
        <FilterBar
            v-model="searchFilters"
            :fields="filterFields"
            @search="search"
            @reset="resetFilters"
        ></FilterBar>
        <table class="table table-sm" v-if="page.props.evaluations?.data?.length">
            <thead><tr><th>{{ t('fields.code') }}</th><th>{{ t('fields.titre') }}</th><th>{{ t('fields.type') }}</th><th>{{ t('fields.date') }}</th><th>{{ t('fields.classe') }}</th><th>{{ t('fields.statut') }}</th><th>{{ t('actions.actions') }}</th></tr></thead>
            <tbody>
                <tr v-for="item in page.props.evaluations.data" :key="item.id">
                    <td>{{ item.code }}</td>
                    <td>{{ item.titre }}</td>
                    <td>{{ item.type }}</td>
                    <td>{{ item.date }}</td>
                    <td>{{ item.classe?.nom }}</td>
                    <td><span :class="'badge badge-' + (item.statut === 'actif' ? 'success' : 'danger')">{{ t('common.' + item.statut) }}</span></td>
                    <td>
                        <Link :href="route('parametrage.evaluations.show', item?.id)" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> {{ t('actions.view') }}</Link>
                        <Link :href="route('parametrage.evaluations.edit', item?.id)" class="btn btn-xs btn-warning"><i class="fa fa-pencil"></i> {{ t('actions.edit') }}</Link>
                        <button @click="confirmDelete(item)" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> {{ t('actions.delete') }}</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <div v-else class="alert alert-info">{{ t('messages.no_data') }}</div>
        <Pagination :paginator="page.props.evaluations" />
        <ConfirmModal :show="showDeleteModal" @update:show="showDeleteModal = $event" :title="t('messages.confirm.delete.title')" @confirm="deleteItem" />
        <FullPageLoader :show="isLoading" />
    </div>
</template>
