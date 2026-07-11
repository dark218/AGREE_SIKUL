<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });

const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({ entrees: Object, filters: Object });

const searchFilters = ref({
    search: props.filters?.search || '',
    type_entree: props.filters?.type_entree || '',
});
const typeOptions = [
    { id: 'emprunt', libelle: 'Emprunt' },
    { id: 'achat', libelle: 'Achat' },
    { id: 'don', libelle: 'Don' },
];
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher (titre, auteur, tiers)…', icon: 'fa-search', width: '260px' },
    { key: 'type_entree', type: 'select', placeholder: "Type d'entrée", options: typeOptions, optionValue: 'id', optionLabel: 'libelle', width: '170px' },
];
const typeLabel = (v) => ({ emprunt: 'Emprunt', achat: 'Achat', don: 'Don' }[v] || v || '-');
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '-';

let searchTimeout;
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const search = () => router.get(route('entrees-livres.index'), searchFilters.value, { preserveState: true, preserveScroll: true });
const resetFilters = () => { searchFilters.value = { search: '', type_entree: '' }; router.get(route('entrees-livres.index')); };
const confirmDelete = (item) => { itemToDelete.value = item; showDeleteModal.value = true; };
const deleteItem = () => {
    if (!itemToDelete.value) return;
    showDeleteLoader();
    router.visit(route('entrees-livres.destroy', itemToDelete.value.id), {
        method: 'delete', preserveScroll: true,
        onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; },
        onFinish: () => hideLoader(),
    });
};
watch(() => searchFilters.value, () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(search, 500); }, { deep: true });
</script>

<template>
    <Head title="Bibliothèque — Entrées de livres" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">Entrées de livres</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('entrees-livres.create')" class="btn btn-primary"><i class="fa fa-plus"></i> Ajouter</Link>
                    </div>
                </div>
            </div>

            <AlertMessage />

            <div class="row m-0">
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters"></FilterBar>

                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Livre</th>
                                        <th>Bibliothèque</th>
                                        <th>Type</th>
                                        <th>Date d'entrée</th>
                                        <th>Quantité</th>
                                        <th>Tiers</th>
                                        <th class="fit">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="entrees?.data && entrees.data.length > 0">
                                        <tr v-for="item in entrees.data" :key="item.id">
                                            <td>{{ item.ouvrage?.titre || '-' }}</td>
                                            <td><small>{{ item.bibliotheque_structure?.libelle || '-' }}</small></td>
                                            <td><span class="badge bg-info">{{ typeLabel(item.type_entree) }}</span></td>
                                            <td><small>{{ fmtDate(item.date_entree) }}</small></td>
                                            <td>{{ item.quantite }}</td>
                                            <td><small>{{ item.tiers || '-' }}</small></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('entrees-livres.show', item.id)" class="btn btn-secondary" title="Voir"><span class="fa fa-eye"></span></Link>
                                                    <Link :href="route('entrees-livres.edit', item.id)" class="btn btn-primary" title="Modifier"><span class="fa fa-edit"></span></Link>
                                                    <button @click="confirmDelete(item)" class="btn btn-danger" title="Supprimer"><span class="fa fa-trash"></span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="7" class="text-center">Aucune entrée</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :data="entrees" />
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            @update:show="showDeleteModal = $event"
            title="Confirmer la suppression"
            message="Supprimer cette entrée ?"
            confirm-text="Supprimer"
            confirm-class="btn-danger"
            @confirm="deleteItem"
        />
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
