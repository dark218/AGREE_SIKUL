<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    configurations: Object,
    filters: Object,
});
const searchFilters = ref({
    search: props.filters?.search || '',
    type: props.filters?.type || '',
    statut: props.filters?.statut || '',
});
const typeOptions = [
    { id: 'texte', libelle: 'Texte' },
    { id: 'monetaire', libelle: 'Montant' },
    { id: 'pourcentage', libelle: 'Pourcentage' },
    { id: 'booleen', libelle: 'Booléen' },
    { id: 'nombre', libelle: 'Nombre' },
];
const statutOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher…', icon: 'fa-search', width: '220px' },
    { key: 'type', type: 'select', placeholder: 'Tous les types', options: typeOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'statut', type: 'select', placeholder: 'Tous les statuts', options: statutOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];
let searchTimeout;
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
function search() {
    router.get(route('finances.configurations-finances.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
}
function resetFilters() {
    searchFilters.value = {
        search: '',
        type: '',
        statut: '',
    };
    search();
}
function confirmDelete(item) {
    itemToDelete.value = item;
    showDeleteModal.value = true;
}
function deleteConfiguration() {
    showStoreLoader();
    router.put(route('finances.configurations-finances.statut', itemToDelete.value.id), {}, {
        onSuccess: () => {
            showDeleteModal.value = false;
            hideLoader();
        },
        onError: () => hideLoader(),
    });
}
function getTypeLabel(type) {
    const labels = {
        texte: 'Texte',
        monetaire: 'Montant',
        pourcentage: 'Pourcentage',
        booleen: 'Booléen',
        nombre: 'Nombre',
    };
    return labels[type] || type;
}
function formatValue(config) {
    const formatters = {
        'monetaire': (val) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(val),
        'pourcentage': (val) => val + '%',
        'booleen': (val) => val === '1' ? 'Oui' : 'Non',
    };
    return formatters[config.type] ? formatters[config.type](config.valeur) : config.valeur;
}
function getStatutLabel(statut) {
    const labels = {
        actif: 'Actif',
        inactif: 'Inactif',
    };
    return labels[statut] || statut;
}
function getStatutBadgeClass(statut) {
    const classes = {
        actif: 'badge-success',
        inactif: 'badge-secondary',
    };
    return classes[statut] || 'badge-secondary';
}
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
    <Head :title="t('common.configurations-finances')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.configurations-finances') }}</h4>
            <div v-if="can('configurations-finances-create')" class="dashboard-btn">
                <Link :href="route('finances.configurations-finances.create')" class="btn btn-primary">
                    <i class="fa fa-plus"></i> {{ t('common.add') }}
                </Link>
            </div>
        </div>
        <AlertMessage />
        <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters"></FilterBar>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>{{ t('common.code') }}</th>
                        <th>{{ t('common.libelle') }}</th>
                        <th>{{ t('common.type') }}</th>
                        <th>{{ t('common.valeur') }}</th>
                        <th>{{ t('common.statut') }}</th>
                        <th class="fit">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="config in configurations.data" :key="config.id">
                        <td><code>{{ config.code }}</code></td>
                        <td><strong>{{ config.libelle }}</strong></td>
                        <td>
                            <span class="badge badge-info">{{ getTypeLabel(config.type) }}</span>
                        </td>
                        <td>{{ formatValue(config) }}</td>
                        <td>
                            <span :class="['badge', getStatutBadgeClass(config.statut)]">
                                {{ getStatutLabel(config.statut) }}
                            </span>
                        </td>
                        <td class="fit">
                            <div class="action-buttons">
                                <Link
                                    :href="route('finances.configurations-finances.show', config.id)"
                                    class="btn btn-secondary btn-sm"
                                    title="Voir"
                                >
                                    <i class="fa fa-eye"></i>
                                </Link>
                                <Link
                                    v-if="can('configurations-finances-edit')"
                                    :href="route('finances.configurations-finances.edit', config.id)"
                                    class="btn btn-primary btn-sm"
                                    title="Modifier"
                                >
                                    <i class="fa fa-edit"></i>
                                </Link>
                                <button
                                    v-if="can('configurations-finances-delete')"
                                    @click="confirmDelete(config)"
                                    class="btn btn-danger btn-sm"
                                    title="Supprimer"
                                >
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="!configurations.data.length" class="alert alert-info text-center">
                {{ t('common.no_data') }}
            </div>
        </div>
        <Pagination :data="configurations" :preserve-scroll="true" />
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('common.confirm_delete')"
            :message="itemToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')"
            @confirm="deleteConfiguration"
            @cancel="showDeleteModal = false"
        />
        <FullPageLoader :show="isLoading" :message="loaderMessage" />
    </div>
</template>
<style scoped>
.filter-form {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}
.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}
.action-buttons .btn {
    padding: 5px 8px;
    font-size: 12px;
}
code {
    background: #f5f5f5;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 12px;
}
</style>
