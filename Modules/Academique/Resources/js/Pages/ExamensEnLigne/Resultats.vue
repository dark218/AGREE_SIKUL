<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const props = defineProps({
    examen: Object,
    tentatives: Array,
    stats: Object,
    title: String,
});

const showLogsModal = ref(false);
const selectedLogs = ref([]);
const selectedApprenant = ref('');

const openLogs = (tentative) => {
    selectedLogs.value = tentative.logs_surveillance || [];
    selectedApprenant.value = tentative.apprenant_nom;
    showLogsModal.value = true;
};

const incidentLabel = (type) => {
    const labels = {
        tab_switch: 'Changement d\'onglet',
        copy_attempt: 'Tentative de copie',
        paste_attempt: 'Tentative de collage',
        right_click: 'Clic droit',
        fullscreen_exit: 'Sortie plein écran',
        devtools_open: 'Outils développeur',
        window_blur: 'Fenêtre quittée',
    };
    return labels[type] || type;
};
</script>

<template>
    <Head :title="title" />
    <div class="body-wrapper">
        <div class="resultats-admin">
            <!-- Header -->
            <div class="result-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h4 class="mb-1">
                            <i class="fa fa-chart-bar me-2"></i>
                            Résultats : {{ examen.titre }}
                        </h4>
                        <p class="mb-0 opacity-75">
                            {{ examen.matiere }} — {{ examen.classe }} — {{ examen.enseignant }}
                            — {{ examen.nombre_questions }} questions — Note sur {{ examen.note_maximum }}
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a :href="route('academique.resultats-examens.pdf-classe', examen.id)" class="btn btn-danger btn-sm" target="_blank"><i class="fa fa-file-pdf me-1"></i> PV Classe PDF</a>
                        <Link :href="route('academique.examens-en-ligne.index')" class="btn btn-light btn-sm">
                            <i class="fa fa-arrow-left me-1"></i> Retour
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="stats-grid mt-3">
                <div class="stat-card stat-blue">
                    <div class="stat-icon"><i class="fa fa-users"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.nb_tentatives }}</div>
                        <div class="stat-label">Compositions</div>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.taux_reussite }}%</div>
                        <div class="stat-label">Taux de réussite</div>
                    </div>
                </div>
                <div class="stat-card stat-cyan">
                    <div class="stat-icon"><i class="fa fa-calculator"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.moyenne_classe }}/{{ examen.note_maximum }}</div>
                        <div class="stat-label">Moyenne classe</div>
                    </div>
                </div>
                <div class="stat-card stat-orange">
                    <div class="stat-icon"><i class="fa fa-trophy"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.note_max }}/{{ examen.note_maximum }}</div>
                        <div class="stat-label">Meilleure note</div>
                    </div>
                </div>
                <div class="stat-card" :class="stats.nb_suspicions > 0 ? 'stat-red' : 'stat-grey'">
                    <div class="stat-icon"><i class="fa fa-shield-alt"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ stats.nb_suspicions }}</div>
                        <div class="stat-label">Suspicions triche</div>
                    </div>
                </div>
            </div>

            <!-- Tableau des résultats -->
            <div class="results-table-card mt-3">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Apprenant</th>
                                <th>Matricule</th>
                                <th class="text-center">Note</th>
                                <th class="text-center">%</th>
                                <th class="text-center">Correctes</th>
                                <th class="text-center">Durée</th>
                                <th class="text-center">Verdict</th>
                                <th class="text-center">Surveillance</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(t, idx) in tentatives"
                                :key="t.id"
                                :class="{ 'table-danger-light': t.suspicion_triche }"
                            >
                                <td>{{ idx + 1 }}</td>
                                <td class="fw-bold">{{ t.apprenant_nom }}</td>
                                <td><small class="text-muted">{{ t.apprenant_matricule }}</small></td>
                                <td class="text-center">
                                    <span class="fw-bold fs-5" :class="t.reussi ? 'text-success' : 'text-danger'">
                                        {{ t.score }}
                                    </span>
                                    <small class="text-muted">/{{ examen.note_maximum }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px; min-width: 60px;">
                                        <div
                                            class="progress-bar"
                                            :class="t.reussi ? 'bg-success' : 'bg-danger'"
                                            :style="{ width: Math.round(t.pourcentage) + '%' }"
                                        >
                                            {{ Math.round(t.pourcentage) }}%
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    {{ t.reponses_correctes }}/{{ t.questions_repondues }}
                                </td>
                                <td class="text-center">
                                    <small>{{ Math.round(t.temps_passe_minutes) }} min</small>
                                </td>
                                <td class="text-center">
                                    <span v-if="t.absent" class="badge bg-dark">ABSENT (0)</span>
                                    <span v-else class="badge" :class="t.reussi ? 'bg-success' : 'bg-danger'">
                                        {{ t.reussi ? 'ADMIS' : 'REFUSÉ' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div v-if="t.nb_incidents_total > 0" class="d-flex align-items-center justify-content-center gap-1">
                                        <span v-if="t.suspicion_triche" class="badge bg-danger">
                                            <i class="fa fa-exclamation-triangle me-1"></i> TRICHE
                                        </span>
                                        <span v-else class="badge bg-warning text-dark">
                                            {{ t.nb_incidents_total }} incident(s)
                                        </span>
                                    </div>
                                    <span v-else class="badge bg-success">
                                        <i class="fa fa-check me-1"></i> OK
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button
                                        v-if="t.nb_incidents_total > 0"
                                        class="btn btn-outline-danger btn-sm"
                                        @click="openLogs(t)"
                                        title="Voir les logs"
                                    >
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="tentatives.length === 0">
                                <td colspan="10" class="text-center py-4 text-muted">
                                    Aucune composition pour cet examen.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Logs Surveillance -->
        <div v-if="showLogsModal" class="modal-overlay" @click.self="showLogsModal = false">
            <div class="modal-box" style="width: 550px;">
                <div class="modal-box-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-shield-alt me-2"></i>
                        Logs surveillance — {{ selectedApprenant }}
                    </h5>
                </div>
                <div class="modal-box-body" style="max-height: 400px; overflow-y: auto;">
                    <div v-if="selectedLogs.length === 0" class="text-center text-muted py-3">
                        Aucun log disponible.
                    </div>
                    <div v-for="(log, idx) in selectedLogs" :key="idx" class="log-item">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-danger">{{ incidentLabel(log.type) }}</span>
                            <small class="text-muted">{{ log.horodatage }}</small>
                        </div>
                        <small v-if="log.details" class="text-muted">{{ log.details }}</small>
                    </div>
                </div>
                <div class="modal-box-footer">
                    <button class="btn btn-secondary" @click="showLogsModal = false">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.result-header {
    background: linear-gradient(135deg, #0B5697 0%, #0a4a82 100%);
    border-radius: 12px;
    padding: 1.5rem 2rem;
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
}

.stat-card {
    border-radius: 12px;
    padding: 1.2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: white;
}

.stat-blue { background: linear-gradient(135deg, #0B5697, #1a6bb5); }
.stat-green { background: linear-gradient(135deg, #28a745, #20c997); }
.stat-cyan { background: linear-gradient(135deg, #0FBCAF, #17a2b8); }
.stat-orange { background: linear-gradient(135deg, #E5590C, #fd7e14); }
.stat-red { background: linear-gradient(135deg, #dc3545, #c82333); }
.stat-grey { background: linear-gradient(135deg, #6c757d, #5a6268); }

.stat-icon { font-size: 1.8rem; opacity: 0.8; }
.stat-value { font-size: 1.5rem; font-weight: 800; }
.stat-label { font-size: 0.8rem; opacity: 0.85; }

.results-table-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
}

.results-table-card thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #0FBCAF;
    font-size: 0.8rem;
    text-transform: uppercase;
    color: #0B5697;
    padding: 0.8rem;
}

.table-danger-light {
    background-color: #fff5f5 !important;
}

.log-item {
    padding: 0.6rem;
    border-bottom: 1px solid #f0f0f0;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-box {
    background: white;
    border-radius: 12px;
    max-width: 90%;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-box-header { padding: 1rem 1.5rem; }
.modal-box-body { padding: 1.5rem; }
.modal-box-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
}
</style>
