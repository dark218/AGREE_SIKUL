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

const props = defineProps({ emploisTemps: Object, filters: Object });

const searchFilters = ref({
    search: props.filters?.search || '',
    etat: props.filters?.etat || '',
});
const statutOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher (libellé, classe)…', icon: 'fa-search', width: '260px' },
    { key: 'etat', type: 'select', placeholder: 'Statut', options: statutOptions, optionValue: 'id', optionLabel: 'libelle', width: '160px' },
];

let searchTimeout;
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const search = () => router.get(route('academique.emplois_du_temps.index'), searchFilters.value, { preserveState: true, preserveScroll: true });
const resetFilters = () => { searchFilters.value = { search: '', etat: '' }; router.get(route('academique.emplois_du_temps.index')); };
const confirmDelete = (item) => { itemToDelete.value = item; showDeleteModal.value = true; };
const deleteItem = () => {
    if (!itemToDelete.value) return;
    showDeleteLoader();
    router.visit(route('academique.emplois_du_temps.destroy', itemToDelete.value.id), {
        method: 'delete', preserveScroll: true,
        onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; },
        onFinish: () => hideLoader(),
    });
};
watch(() => searchFilters.value, () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(search, 500); }, { deep: true });
</script>

<template>
    <Head title="Emplois du temps" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">Emplois du temps</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('academique.emplois_du_temps.create')" class="btn btn-primary"><i class="fa fa-plus"></i> Ajouter</Link>
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
                                        <th>Libellé</th>
                                        <th>Classe</th>
                                        <th>Année</th>
                                        <th>Libellé période</th>
                                        <th>Date début</th>
                                        <th>Date fin</th>
                                        <th>Créneaux</th>
                                        <th>Statut</th>
                                        <th class="fit">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="emploisTemps?.data && emploisTemps.data.length > 0">
                                        <tr v-for="item in emploisTemps.data" :key="item.id">
                                            <td>{{ item.libelle || '-' }}</td>
                                            <td>{{ item.classe }}</td>
                                            <td><small>{{ item.annee || '-' }}</small></td>
                                            <td><small>{{ item.periode || '-' }}</small></td>
                                            <td><small>{{ item.date_debut }}</small></td>
                                            <td><small>{{ item.date_fin }}</small></td>
                                            <td><span class="badge bg-info">{{ item.nb_creneaux }}</span></td>
                                            <td>
                                                <span class="badge" :class="item.etat === 'actif' ? 'bg-success' : 'bg-danger'">
                                                    {{ item.etat === 'actif' ? 'Actif' : 'Inactif' }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.emplois_du_temps.show', item.id)" class="btn btn-secondary" title="Voir"><span class="fa fa-eye"></span></Link>
                                                    <Link :href="route('academique.emplois_du_temps.edit', item.id)" class="btn btn-primary" title="Modifier"><span class="fa fa-edit"></span></Link>
                                                    <button @click="confirmDelete(item)" class="btn btn-danger" title="Supprimer"><span class="fa fa-trash"></span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="9" class="text-center">Aucun emploi du temps</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :data="emploisTemps" />
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            @update:show="showDeleteModal = $event"
            title="Confirmer la suppression"
            message="Supprimer cet emploi du temps et ses créneaux ?"
            confirm-text="Supprimer"
            confirm-class="btn-danger"
            @confirm="deleteItem"
        />
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
