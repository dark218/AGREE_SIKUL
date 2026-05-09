<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    emploiTemps: Object,
    classes: { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    campuses: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
    enseignants: { type: Array, default: () => [] },
});

const emploiData = props.emploiTemps || page.props.emploiTemps;
const jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
const joursLibelle = {
    lundi: 'Lundi', mardi: 'Mardi', mercredi: 'Mercredi',
    jeudi: 'Jeudi', vendredi: 'Vendredi', samedi: 'Samedi'
};

const formatDurationHM = (decimalHours) => {
    if (!decimalHours || decimalHours <= 0) return '0h0';
    const hours = Math.floor(decimalHours);
    const minutes = Math.round((decimalHours - hours) * 60);
    return `${hours}h${minutes}`;
};

const formatDateTime = (datetime) => {
    if (!datetime) return '-';
    try {
        const date = new Date(datetime);
        return date.toLocaleString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch (e) {
        return datetime;
    }
};
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-eye"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('actions.view') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <!-- Infos Semaine -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="mb-2">{{ emploiData?.week_name }} - {{ emploiData?.classe?.nom }}</h5>
                                    <p class="text-muted mb-0">
                                        {{ t('common.annee_scolaire') }}: {{ emploiData?.anneeScolaire?.libelle }}
                                        | Période: {{ emploiData?.week_start_date ? new Date(emploiData.week_start_date).toLocaleDateString('fr-FR') : '-' }} - {{ emploiData?.week_end_date ? new Date(emploiData.week_end_date).toLocaleDateString('fr-FR') : '-' }}
                                        | Statut: <span class="badge" :class="{ 'bg-secondary': emploiData?.statut === 'brouillon', 'bg-info': emploiData?.statut === 'valide', 'bg-success': emploiData?.statut === 'publie', 'bg-dark': emploiData?.statut === 'archive' }">{{ t('common.' + emploiData?.statut) }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Cours par Jour -->
                            <template v-for="jour in jours" :key="jour">
                                <div class="mb-4" v-if="emploiData?.coursesGroupedByDay?.[jour]?.length > 0">
                                    <h6 class="fw-bold mb-2" style="border-bottom: 2px solid #ddd; padding-bottom: 0.5rem;">{{ joursLibelle[jour] }}</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Matière</th>
                                                    <th>Enseignant</th>
                                                    <th>Début</th>
                                                    <th>Fin</th>
                                                    <th>Durée</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="course in emploiData?.coursesGroupedByDay[jour]" :key="course.id">
                                                    <td>{{ course.matiere?.libelle || '-' }}</td>
                                                    <td>{{ course.enseignant ? `${course.enseignant.prenoms} ${course.enseignant.nom}` : '-' }}</td>
                                                    <td>{{ formatDateTime(course.date_debut) }}</td>
                                                    <td>{{ formatDateTime(course.date_fin) }}</td>
                                                    <td><strong>{{ formatDurationHM(course.duree) }}</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>

                            <!-- Aucun cours -->
                            <div v-if="!Object.values(emploiData?.coursesGroupedByDay || {}).some(day => day.length > 0)" class="alert alert-warning">
                                Aucun cours défini pour cette semaine
                            </div>

                            <!-- Boutons Retour et PDF -->
                            <div class="row mt-4 border-top pt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <a :href="route('academique.emplois_du_temps.pdf', emploiData.id)" class="btn btn-success" target="_blank" title="Télécharger en PDF">
                                            <i class="fa fa-file-pdf"></i> {{ t('actions.download') }} PDF
                                        </a>
                                        <Link :href="route('academique.emplois_du_temps.index')" class="btn btn-danger">
                                            {{ t('actions.back') }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
