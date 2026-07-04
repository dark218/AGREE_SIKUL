<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();

const props = defineProps({
    resultats: Object,
    examens: Array,
    matieres: Array,
    classes: Array,
    filters: Object,
    stats: Object,
    title: String,
});

const searchFilters = ref({
    examen_id: props.filters?.examen_id || '',
    matiere_id: props.filters?.matiere_id || '',
    classe_id: props.filters?.classe_id || '',
    resultat: props.filters?.resultat || '',
});

const resultatOptions = [
    { id: 'admis', libelle: 'Admis' },
    { id: 'refuse', libelle: 'Refusé' },
];

const filterFields = computed(() => [
    { key: 'examen_id', type: 'select', placeholder: 'Examen...', options: props.examens, optionValue: 'id', optionLabel: 'titre', width: '190px' },
    { key: 'matiere_id', type: 'select', placeholder: 'Matière...', options: props.matieres, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'classe_id', type: 'select', placeholder: 'Classe...', options: props.classes, optionValue: 'id', optionLabel: 'nom', width: '190px' },
    { key: 'resultat', type: 'select', placeholder: 'Verdict...', options: resultatOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
]);

let searchTimeout;

const search = () => {
    router.get(route('academique.resultats-examens.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    Object.keys(searchFilters.value).forEach(key => { searchFilters.value[key] = ''; });
    router.get(route('academique.resultats-examens.index'));
};

watch(() => searchFilters.value, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => search(), 500);
}, { deep: true });
</script>

<template>
    <Head :title="title" />
    <div class="body-wrapper">
        <!-- Header -->
        <div class="result-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1"><i class="fa fa-chart-line me-2"></i> Résultats des Examens</h4>
                    <small class="opacity-75">Résultats générés automatiquement depuis les examens en ligne</small>
                </div>
            </div>
        </div>

        <AlertMessage />

        <!-- Stats -->
        <div class="stats-row mt-3">
            <div class="stat-mini stat-blue">
                <i class="fa fa-file-alt"></i>
                <div>
                    <strong>{{ stats.total }}</strong>
                    <span>Compositions</span>
                </div>
            </div>
            <div class="stat-mini stat-green">
                <i class="fa fa-check-circle"></i>
                <div>
                    <strong>{{ stats.reussis }}</strong>
                    <span>Admis</span>
                </div>
            </div>
            <div class="stat-mini stat-red">
                <i class="fa fa-times-circle"></i>
                <div>
                    <strong>{{ stats.echoues }}</strong>
                    <span>Refusés</span>
                </div>
            </div>
            <div class="stat-mini stat-cyan">
                <i class="fa fa-percent"></i>
                <div>
                    <strong>{{ stats.taux_reussite }}%</strong>
                    <span>Réussite</span>
                </div>
            </div>
            <div class="stat-mini stat-orange">
                <i class="fa fa-calculator"></i>
                <div>
                    <strong>{{ stats.moyenne_globale }}</strong>
                    <span>Moyenne</span>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-bar mt-3">
            <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters">
                <template #actions>
                    <a
                        v-if="searchFilters.examen_id"
                        :href="route('academique.resultats-examens.pdf-classe', searchFilters.examen_id)"
                        class="fb-btn-pdf"
                        target="_blank"
                    >
                        <i class="fa fa-file-pdf"></i> Télécharger PV de classe
                    </a>
                </template>
            </FilterBar>
        </div>

        <!-- Tableau -->
        <div class="results-table-card mt-3">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Apprenant</th>
                            <th>Matricule</th>
                            <th>Examen</th>
                            <th>Matière</th>
                            <th>Classe</th>
                            <th class="text-center">Note</th>
                            <th class="text-center">%</th>
                            <th class="text-center">Verdict</th>
                            <th class="text-center">Surveillance</th>
                            <th class="text-center">Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="resultats?.data && resultats.data.length > 0">
                            <tr v-for="r in resultats.data" :key="r.id" :class="{ 'row-triche': r.suspicion_triche }">
                                <td class="fw-bold">{{ r.apprenant_nom }}</td>
                                <td><small class="text-muted">{{ r.apprenant_matricule }}</small></td>
                                <td>{{ r.examen_titre }}</td>
                                <td>{{ r.matiere }}</td>
                                <td>{{ r.classe }}</td>
                                <td class="text-center">
                                    <span class="fw-bold" :class="r.reussi ? 'text-success' : 'text-danger'">
                                        {{ r.score }}
                                    </span>
                                    <small class="text-muted">/{{ r.note_maximum }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="progress" style="height: 18px; min-width: 50px;">
                                        <div class="progress-bar" :class="r.reussi ? 'bg-success' : 'bg-danger'" :style="{ width: Math.round(r.pourcentage) + '%' }">
                                            {{ Math.round(r.pourcentage) }}%
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span v-if="r.absent" class="badge bg-dark">ABSENT (0)</span>
                                    <span v-else class="badge" :class="r.reussi ? 'bg-success' : 'bg-danger'">
                                        {{ r.reussi ? 'ADMIS' : 'REFUSÉ' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span v-if="r.suspicion_triche" class="badge bg-danger"><i class="fa fa-exclamation-triangle"></i> TRICHE</span>
                                    <span v-else-if="r.nb_incidents > 0" class="badge bg-warning text-dark">{{ r.nb_incidents }}</span>
                                    <span v-else class="badge bg-success"><i class="fa fa-check"></i></span>
                                </td>
                                <td class="text-center"><small>{{ r.date_composition }}</small></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <Link :href="route('academique.resultats-examens.show', r.id)" class="btn btn-secondary btn-sm" title="Détails">
                                            <i class="fa fa-eye"></i>
                                        </Link>
                                        <a :href="route('academique.resultats-examens.pdf', r.id)" class="btn btn-danger btn-sm" title="Télécharger PDF" target="_blank">
                                            <i class="fa fa-file-pdf"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                Aucun résultat pour le moment. Les résultats apparaîtront automatiquement quand les apprenants composeront.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="resultats" />
        </div>

        <!-- Mobile cards -->
        <div class="mobile-card-list">
            <template v-if="resultats?.data && resultats.data.length > 0">
                <div v-for="r in resultats.data" :key="'m-' + r.id" class="mobile-card">
                    <div class="mobile-card-body">
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Apprenant</span>
                            <span class="mobile-card-value fw-bold">{{ r.apprenant_nom }}</span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Examen</span>
                            <span class="mobile-card-value">{{ r.examen_titre }}</span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Note</span>
                            <span class="mobile-card-value" :class="r.reussi ? 'text-success' : 'text-danger'">
                                <strong>{{ r.score }}/{{ r.note_maximum }}</strong> ({{ Math.round(r.pourcentage) }}%)
                            </span>
                        </div>
                        <div class="mobile-card-row">
                            <span class="mobile-card-label">Verdict</span>
                            <span v-if="r.absent" class="badge bg-dark">ABSENT (0)</span>
                            <span v-else class="badge" :class="r.reussi ? 'bg-success' : 'bg-danger'">{{ r.reussi ? 'ADMIS' : 'REFUSÉ' }}</span>
                        </div>
                    </div>
                    <div class="mobile-card-actions">
                        <Link :href="route('academique.resultats-examens.show', r.id)" class="btn btn-secondary"><i class="fa fa-eye"></i></Link>
                        <a :href="route('academique.resultats-examens.pdf', r.id)" class="btn btn-danger" target="_blank"><i class="fa fa-file-pdf"></i></a>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped>
.result-header {
    background: linear-gradient(135deg, #0B5697, #0a4a82);
    border-radius: 12px;
    padding: 1.2rem 1.5rem;
    color: white;
}

.stats-row {
    display: flex;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.stat-mini {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.7rem 1rem;
    border-radius: 10px;
    color: white;
    min-width: 140px;
}

.stat-mini i { font-size: 1.3rem; opacity: 0.8; }
.stat-mini strong { font-size: 1.2rem; display: block; }
.stat-mini span { font-size: 0.75rem; opacity: 0.85; }

.stat-blue { background: linear-gradient(135deg, #0B5697, #1a6bb5); }
.stat-green { background: linear-gradient(135deg, #28a745, #20c997); }
.stat-red { background: linear-gradient(135deg, #dc3545, #e74c3c); }
.stat-cyan { background: linear-gradient(135deg, #0FBCAF, #17a2b8); }
.stat-orange { background: linear-gradient(135deg, #E5590C, #fd7e14); }

.results-table-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
}

.row-triche { background-color: #fff5f5 !important; }
</style>
