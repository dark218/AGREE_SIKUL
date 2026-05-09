<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const props = defineProps({
    tentative: Object,
    examen: Object,
    apprenant: Object,
    reussi: Boolean,
    correction: Array,
    logs: Array,
    title: String,
});

const showLogs = ref(false);

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
        <div class="detail-page">
            <!-- Header -->
            <div class="detail-header" :class="reussi ? 'header-pass' : 'header-fail'">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h4 class="mb-1">
                            <i :class="reussi ? 'fa fa-trophy' : 'fa fa-times-circle'" class="me-2"></i>
                            {{ apprenant.nom }} — {{ apprenant.matricule }}
                        </h4>
                        <p class="mb-0 opacity-75">
                            {{ examen.titre }} — {{ examen.matiere }} — {{ examen.classe }}
                        </p>
                    </div>
                    <Link :href="route('academique.resultats-examens.index')" class="btn btn-light btn-sm">
                        <i class="fa fa-arrow-left me-1"></i> Retour
                    </Link>
                </div>
            </div>

            <!-- Score -->
            <div class="score-section mt-3">
                <div class="score-circle" :class="reussi ? 'circle-pass' : 'circle-fail'">
                    <span class="score-num">{{ tentative.score }}</span>
                    <span class="score-denom">/{{ examen.note_maximum }}</span>
                </div>
                <div class="score-details">
                    <div class="detail-row"><span>Pourcentage</span><strong>{{ Math.round(tentative.pourcentage) }}%</strong></div>
                    <div class="detail-row"><span>Réponses correctes</span><strong>{{ tentative.reponses_correctes }} / {{ tentative.questions_repondues }}</strong></div>
                    <div class="detail-row"><span>Durée</span><strong>{{ Math.round(tentative.temps_passe_minutes) }} min</strong></div>
                    <div class="detail-row"><span>Composé le</span><strong>{{ tentative.debut_composition }}</strong></div>
                    <div class="detail-row">
                        <span>Verdict</span>
                        <span class="badge fs-6" :class="reussi ? 'bg-success' : 'bg-danger'">{{ reussi ? 'ADMIS' : 'REFUSÉ' }}</span>
                    </div>
                    <div class="detail-row" v-if="tentative.nb_incidents_total > 0">
                        <span>Surveillance</span>
                        <span class="badge bg-danger" v-if="tentative.suspicion_triche"><i class="fa fa-exclamation-triangle me-1"></i> SUSPICION TRICHE</span>
                        <span class="badge bg-warning text-dark" v-else>{{ tentative.nb_incidents_total }} incident(s)</span>
                        <button class="btn btn-outline-danger btn-sm ms-2" @click="showLogs = true"><i class="fa fa-eye me-1"></i> Logs</button>
                    </div>
                </div>
            </div>

            <!-- Correction -->
            <div class="correction-section mt-4" v-if="correction && correction.length > 0">
                <h5 class="section-title"><i class="fa fa-check-double me-2"></i> Correction détaillée</h5>
                <div v-for="(q, idx) in correction" :key="idx" class="correction-item" :class="{ 'item-correct': q.est_correcte, 'item-wrong': q.est_correcte === false }">
                    <div class="correction-header">
                        <span class="fw-bold">Q{{ idx + 1 }}</span>
                        <span class="badge bg-dark">{{ q.points }} pts</span>
                        <span class="ms-auto badge" :class="q.est_correcte ? 'bg-success' : (q.est_correcte === false ? 'bg-danger' : 'bg-secondary')">{{ q.points_obtenus }}/{{ q.points }}</span>
                    </div>
                    <p class="mb-2">{{ q.enonce }}</p>
                    <div v-if="q.reponses && q.reponses.length > 0" class="choices-list">
                        <div v-for="(r, rIdx) in q.reponses" :key="r.id" class="choice-row" :class="{ 'choice-correct': r.est_correcte, 'choice-chosen': r.id === q.reponse_choisie_id, 'choice-wrong-chosen': r.id === q.reponse_choisie_id && !r.est_correcte }">
                            <span class="choice-letter-mini">{{ String.fromCharCode(65 + rIdx) }}</span>
                            <span>{{ r.texte }}</span>
                            <i v-if="r.est_correcte" class="fa fa-check ms-auto text-success"></i>
                            <i v-if="r.id === q.reponse_choisie_id && !r.est_correcte" class="fa fa-times ms-auto text-danger"></i>
                            <small v-if="r.id === q.reponse_choisie_id" class="badge bg-info ms-1">Réponse élève</small>
                        </div>
                    </div>
                    <div v-if="q.reponse_libre" class="mt-1 small"><strong>Réponse :</strong> {{ q.reponse_libre }}</div>
                    <div v-if="q.explication" class="explication-box mt-1"><i class="fa fa-lightbulb me-1"></i> {{ q.explication }}</div>
                </div>
            </div>

            <div class="text-end mt-3 mb-3">
                <Link :href="route('academique.resultats-examens.index')" class="btn btn-danger"><i class="fa fa-arrow-left me-1"></i> Retour</Link>
            </div>
        </div>

        <!-- Modal logs -->
        <div v-if="showLogs" class="modal-overlay" @click.self="showLogs = false">
            <div class="modal-box" style="width: 500px;">
                <div class="modal-box-header bg-danger text-white"><h5 class="mb-0"><i class="fa fa-shield-alt me-2"></i> Logs surveillance</h5></div>
                <div class="modal-box-body" style="max-height: 400px; overflow-y: auto;">
                    <div v-for="(log, idx) in logs" :key="idx" class="log-item">
                        <div class="d-flex justify-content-between"><span class="badge bg-danger">{{ incidentLabel(log.type) }}</span><small class="text-muted">{{ log.horodatage }}</small></div>
                        <small v-if="log.details" class="text-muted">{{ log.details }}</small>
                    </div>
                    <div v-if="!logs || logs.length === 0" class="text-center text-muted py-3">Aucun log.</div>
                </div>
                <div class="modal-box-footer"><button class="btn btn-secondary" @click="showLogs = false">Fermer</button></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.detail-header { border-radius: 12px; padding: 1.5rem 2rem; color: white; }
.header-pass { background: linear-gradient(135deg, #28a745, #20c997); }
.header-fail { background: linear-gradient(135deg, #dc3545, #e74c3c); }

.score-section { display: flex; gap: 2rem; align-items: center; background: white; border-radius: 12px; padding: 1.5rem 2rem; box-shadow: 0 2px 15px rgba(0,0,0,0.06); }
.score-circle { width: 110px; height: 110px; border-radius: 50%; border: 6px solid; display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0; }
.circle-pass { border-color: #28a745; }
.circle-fail { border-color: #dc3545; }
.score-num { font-size: 2rem; font-weight: 800; }
.circle-pass .score-num { color: #28a745; }
.circle-fail .score-num { color: #dc3545; }
.score-denom { font-size: 0.9rem; color: #999; }
.score-details { flex: 1; }
.detail-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0; border-bottom: 1px solid #f0f0f0; }
.detail-row span:first-child { color: #777; min-width: 160px; }

.section-title { color: #0B5697; border-bottom: 2px solid #0FBCAF; padding-bottom: 0.5rem; }
.correction-item { background: white; border-radius: 8px; padding: 1rem; margin-bottom: 0.6rem; border-left: 4px solid #dee2e6; box-shadow: 0 1px 5px rgba(0,0,0,0.04); }
.item-correct { border-left-color: #28a745; }
.item-wrong { border-left-color: #dc3545; }
.choices-list { display: flex; flex-direction: column; gap: 0.3rem; }
.choice-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.6rem; border-radius: 5px; font-size: 0.9rem; background: #f8f9fa; }
.choice-correct { background: #d4edda; }
.choice-wrong-chosen { background: #f8d7da; }
.choice-letter-mini { width: 22px; height: 22px; border-radius: 50%; background: #6c757d; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; }
.choice-correct .choice-letter-mini { background: #28a745; }
.choice-wrong-chosen .choice-letter-mini { background: #dc3545; }
.explication-box { padding: 0.4rem 0.6rem; background: #fff3cd; border-radius: 5px; font-size: 0.85rem; color: #856404; }
.log-item { padding: 0.5rem; border-bottom: 1px solid #f0f0f0; }

.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-box { background: white; border-radius: 12px; max-width: 90%; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.modal-box-header { padding: 1rem 1.5rem; }
.modal-box-body { padding: 1.5rem; }
.modal-box-footer { padding: 1rem 1.5rem; border-top: 1px solid #eee; display: flex; justify-content: flex-end; }

@media (max-width: 768px) {
    .score-section { flex-direction: column; text-align: center; }
}
</style>
