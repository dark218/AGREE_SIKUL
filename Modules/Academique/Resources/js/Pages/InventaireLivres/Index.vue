<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();

const props = defineProps({
    livres: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const searchFilters = ref({ search: props.filters?.search || '' });

const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher un livre…', icon: 'fa-search', width: '260px' },
];

const search = () => router.get(route('academique.inventaire-livres.index'), searchFilters.value, { preserveState: true, preserveScroll: true });
const resetFilters = () => { searchFilters.value.search = ''; router.get(route('academique.inventaire-livres.index')); };

const stockClass = (n) => (n <= 0 ? 'text-danger fw-bold' : (n < 5 ? 'text-warning fw-bold' : 'text-success fw-bold'));
</script>

<template>
    <Head :title="t('fields.inventory') || 'Inventaire'" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('fields.inventory') || 'Inventaire des livres' }}</h4>
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
                                        <th>{{ t('fields.book_title') || 'Titre' }}</th>
                                        <th>{{ t('fields.subject') || 'Sujet/Matière' }}</th>
                                        <th>{{ t('fields.language') || 'Langue' }}</th>
                                        <th>{{ t('fields.authors') || 'Auteur(s)' }}</th>
                                        <th>{{ t('fields.publishers') || 'Editeur(s)' }}</th>
                                        <th>{{ t('fields.edition_year') || 'Année' }}</th>
                                        <th class="text-center">{{ t('fields.initial_quantity') || 'Qté initiale' }}</th>
                                        <th class="text-center">{{ t('fields.exits_loans') || 'Sorties/Prêts' }}</th>
                                        <th class="text-center">{{ t('fields.available_stock') || 'Stock disponible' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="livres?.data && livres.data.length > 0">
                                        <tr v-for="item in livres.data" :key="item.id">
                                            <td><strong>{{ item.titre || '-' }}</strong></td>
                                            <td>{{ item.sujet || '-' }}</td>
                                            <td>{{ item.langue || '-' }}</td>
                                            <td>{{ item.auteurs || '-' }}</td>
                                            <td>{{ item.editeurs || '-' }}</td>
                                            <td>{{ item.annee_edition || '-' }}</td>
                                            <td class="text-center">{{ item.quantite_initiale }}</td>
                                            <td class="text-center">{{ item.sorties_prets }}</td>
                                            <td class="text-center" :class="stockClass(item.stock_disponible)">{{ item.stock_disponible }}</td>
                                        </tr>
                                    </template>
                                    <tr v-else><td colspan="9" class="text-center">{{ t('common.no_data') || 'Aucune donnée' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <Pagination :data="livres" :preserve-scroll="true" />
        </div>
    </div>
</template>
