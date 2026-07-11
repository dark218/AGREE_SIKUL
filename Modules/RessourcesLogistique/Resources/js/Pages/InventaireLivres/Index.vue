<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
defineOptions({ layout: DashboardLayout });

const props = defineProps({ inventaire: Object, filters: Object });

const searchFilters = ref({ search: props.filters?.search || '' });
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher (titre, auteur, sujet)…', icon: 'fa-search', width: '280px' },
];

let searchTimeout;
const search = () => router.get(route('inventaire-livres.index'), searchFilters.value, { preserveState: true, preserveScroll: true });
const resetFilters = () => { searchFilters.value = { search: '' }; router.get(route('inventaire-livres.index')); };
watch(() => searchFilters.value, () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(search, 500); }, { deep: true });

const stockClass = (n) => n > 5 ? 'bg-success' : (n > 0 ? 'bg-warning text-dark' : 'bg-danger');
</script>

<template>
    <Head title="Bibliothèque — Inventaire" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">Inventaire de la bibliothèque</h4>
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
                                        <th>Titre du livre</th>
                                        <th>Sujet / Matière</th>
                                        <th>Langue</th>
                                        <th>Auteur(s)</th>
                                        <th>Éditeur(s)</th>
                                        <th>Année d'édition</th>
                                        <th>Quantité initiale</th>
                                        <th>Sorties / Prêts</th>
                                        <th>Stock disponible</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="inventaire?.data && inventaire.data.length > 0">
                                        <tr v-for="row in inventaire.data" :key="row.id">
                                            <td>{{ row.titre }}</td>
                                            <td><small>{{ row.sujet || '-' }}</small></td>
                                            <td><small>{{ row.langue || '-' }}</small></td>
                                            <td><small>{{ row.auteur || '-' }}</small></td>
                                            <td><small>{{ row.editeur || '-' }}</small></td>
                                            <td><small>{{ row.annee_publication || '-' }}</small></td>
                                            <td>{{ row.quantite_initiale }}</td>
                                            <td>{{ row.sorties }}</td>
                                            <td><span class="badge" :class="stockClass(row.stock_disponible)">{{ row.stock_disponible }}</span></td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="9" class="text-center">Aucun ouvrage</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :data="inventaire" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
