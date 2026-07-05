<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    entrees: { type: Object, required: true },
    structures: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const searchFilters = ref({
    search: props.filters?.search || '',
    type_entree: props.filters?.type_entree || '',
    bibliotheque_structure_id: props.filters?.bibliotheque_structure_id || '',
});

const typeOptions = [
    { id: 'emprunt', libelle: 'Emprunt' },
    { id: 'achat', libelle: 'Achat' },
    { id: 'don', libelle: 'Don' },
];
const structuresOptions = computed(() => props.structures.map(s => ({ id: s.id, libelle: s.libelle })));

const filterFields = computed(() => [
    { key: 'search', type: 'text', placeholder: 'Rechercher…', icon: 'fa-search', width: '220px' },
    { key: 'type_entree', type: 'select', placeholder: "Type d'entrée", options: typeOptions, optionValue: 'id', optionLabel: 'libelle', width: '160px' },
    { key: 'bibliotheque_structure_id', type: 'select', placeholder: 'Bibliothèque', options: structuresOptions.value, optionValue: 'id', optionLabel: 'libelle', width: '180px' },
]);

const search = () => router.get(route('academique.entrees-livres.index'), searchFilters.value, { preserveState: true, preserveScroll: true });
const resetFilters = () => { Object.keys(searchFilters.value).forEach(k => searchFilters.value[k] = ''); router.get(route('academique.entrees-livres.index')); };

const showModal = ref(false);
const itemId = ref(null);
const confirmDelete = (id) => { itemId.value = id; showModal.value = true; };
const handleConfirm = () => {
    showDeleteLoader();
    router.delete(route('academique.entrees-livres.destroy', itemId.value), {
        preserveScroll: true,
        onSuccess: () => { showModal.value = false; },
        onFinish: () => hideLoader(),
    });
};
</script>

<template>
    <Head :title="t('fields.book_entries') || 'Entrées de livres'" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('fields.book_entries') || 'Entrées de livres' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('entrees-livres-create')">
                        <Link :href="route('academique.entrees-livres.create')" class="btn btn-primary">{{ t('actions.add') || 'Ajouter' }}</Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0">
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters" />
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.book_title') || 'Livre' }}</th>
                                        <th>{{ t('fields.library') || 'Bibliothèque' }}</th>
                                        <th>{{ t('fields.entry_type') || "Type" }}</th>
                                        <th>{{ t('fields.entry_date') || "Date d'entrée" }}</th>
                                        <th>{{ t('fields.quantity') || 'Qté' }}</th>
                                        <th>{{ t('fields.provider') || 'Tiers' }}</th>
                                        <th>{{ t('fields.status') || 'État' }}</th>
                                        <th class="fit">{{ t('common.actions') || 'Actions' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="entrees?.data && entrees.data.length > 0">
                                        <tr v-for="item in entrees.data" :key="item.id">
                                            <td><strong>{{ item.livre?.titre_manuel || '-' }}</strong></td>
                                            <td>{{ item.structure?.libelle || '-' }}</td>
                                            <td><span class="badge bg-info">{{ item.type_entree }}</span></td>
                                            <td>{{ item.date_entree ? String(item.date_entree).substring(0,10) : '-' }}</td>
                                            <td>{{ item.quantite }}</td>
                                            <td>{{ item.tiers || '-' }}</td>
                                            <td><span class="badge" :class="item.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ item.etat }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.entrees-livres.show', item.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                                    <Link :href="route('academique.entrees-livres.edit', item.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                                    <button @click="confirmDelete(item.id)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else><td colspan="8" class="text-center">{{ t('common.no_data') || 'Aucune donnée' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <Pagination :data="entrees" :preserve-scroll="true" />
        </div>
        <ConfirmModal :show="showModal" title="Supprimer" message="Confirmer la suppression ?" @confirm="handleConfirm" @update:show="showModal = $event" @close="showModal = false" />
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
