<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const props = defineProps({
    tentative: Object,
    examen: Object,
    reussi: Boolean,
    correction: Array,
    title: String,
});

const pourcentageFormatted = computed(() => Math.round(props.tentative.pourcentage || 0));
const tempsFormatted = computed(() => {
    const min = Math.floor(props.tentative.temps_passe_minutes || 0);
    const sec = Math.round(((props.tentative.temps_passe_minutes || 0) - min) * 60);
    return `${min}min ${sec}s`;
});

const circleColor = computed(() => {
    if (props.reussi) return '#28a745';
    return '#dc3545';
});
</script>

<template>
    <Head :title="title" />
    <div class="body-wrapper">
        <div class="resultat-page">
            <!-- Header résultat -->
            <div class="resultat-header" :class="reussi ? 'resultat-pass' : 'resultat-fail'">
                <div class="resultat-icon">
                    <i :class="reussi ? 'fa fa-trophy' : 'fa fa-times-circle'" class="fa-3x"></i>
                </div>
                <h3>{{ reussi ? 'Félicitations !' : 'Examen terminé' }}</h3>
                <p class="mb-0">{{ examen.titre }} — {{ examen.matiere }}</p>
            </div>

            <!-- Score principal -->
            <div class="score-card" v-if="examen.afficher_resultat">
                <div class="score-circle" :style="{ borderColor: circleColor }">
                    <span class="score-value" :style="{ color: circleColor }">{{ tentative.score }}</span>
                    <span class="score-max">/{{ examen.note_maximum }}</span>
                </div>
                <div class="score-details">
                    <div class="score-detail-item">
                        <div class="score-detail-label">Pourcentage</div>
                        <div class="score-detail-value">{{ pourcentageFormatted }}%</div>
                        <div class="progress mt-1" style="height: 8px;">
                            <div
                                class="progress-bar"
                                :class="reussi ? 'bg-success' : 'bg-danger'"
                                :style="{ width: pourcentageFormatted + '%' }"
                            ></div>
                        </div>
                    </div>
                    <div class="score-stats">
                        <div class="stat-item">
                            <i class="fa fa-check-circle text-success"></i>
                            <div>
                                <span class="stat-value">{{ tentative.reponses_correctes }}</span>
                                <span class="stat-label">correctes</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fa fa-question-circle text-primary"></i>
                            <div>
                                <span class="stat-value">{{ tentative.questions_repondues }}</span>
                                <span class="stat-label">répondues / {{ examen.nombre_questions }}</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fa fa-clock text-warning"></i>
                            <div>
                                <span class="stat-value">{{ tempsFormatted }}</span>
                                <span class="stat-label">durée</span>
                            </div>
                        </div>
                    </div>
                    <div class="verdict mt-2">
                        <span class="badge fs-6" :class="reussi ? 'bg-success' : 'bg-danger'">
                            {{ reussi ? 'ADMIS' : 'NON ADMIS' }}
                        </span>
                        <small class="text-muted ms-2">
                            (seuil : {{ examen.note_minimum_passage }}/{{ examen.note_maximum }})
                        </small>
                    </div>
                </div>
            </div>

            <!-- Message si résultat masqué -->
            <div v-else class="score-card text-center">
                <i class="fa fa-eye-slash fa-2x text-muted mb-2"></i>
                <p>L'enseignant n'a pas activé l'affichage des résultats pour cet examen.</p>
                <p class="text-muted small">Vos résultats seront communiqués ultérieurement.</p>
            </div>

            <!-- Correction détaillée -->
            <div v-if="correction && correction.length > 0" class="correction-section mt-4">
                <h5 class="correction-title">
                    <i class="fa fa-check-double me-2"></i> Correction détaillée
                </h5>

                <div
                    v-for="(q, idx) in correction"
                    :key="idx"
                    class="correction-item"
                    :class="{ 'correction-correct': q.est_correcte, 'correction-wrong': q.est_correcte === false }"
                >
                    <div class="correction-header">
                        <span class="fw-bold">Question {{ idx + 1 }}</span>
                        <span class="badge bg-dark">{{ q.points }} pts</span>
                        <span class="ms-auto badge" :class="q.est_correcte ? 'bg-success' : (q.est_correcte === false ? 'bg-danger' : 'bg-secondary')">
                            {{ q.est_correcte ? 'Correct' : (q.est_correcte === false ? 'Incorrect' : 'Non corrigé') }}
                            — {{ q.points_obtenus }}/{{ q.points }} pts
                        </span>
                    </div>
                    <div class="correction-enonce">{{ q.enonce }}</div>

                    <div v-if="q.reponses && q.reponses.length > 0" class="correction-choices">
                        <div
                            v-for="(r, rIdx) in q.reponses"
                            :key="r.id"
                            class="correction-choice"
                            :class="{
                                'choice-correct': r.est_correcte,
                                'choice-chosen': r.id === q.reponse_choisie_id,
                                'choice-wrong-chosen': r.id === q.reponse_choisie_id && !r.est_correcte,
                            }"
                        >
                            <span class="correction-letter">{{ String.fromCharCode(65 + rIdx) }}</span>
                            <span>{{ r.texte }}</span>
                            <i v-if="r.est_correcte" class="fa fa-check ms-auto text-success"></i>
                            <i v-if="r.id === q.reponse_choisie_id && !r.est_correcte" class="fa fa-times ms-auto text-danger"></i>
                            <span v-if="r.id === q.reponse_choisie_id" class="badge bg-info ms-2">Votre réponse</span>
                        </div>
                    </div>

                    <div v-if="q.reponse_libre" class="mt-2 small">
                        <strong>Votre réponse :</strong> {{ q.reponse_libre }}
                    </div>

                    <div v-if="q.explication" class="correction-explication">
                        <i class="fa fa-lightbulb me-1"></i> {{ q.explication }}
                    </div>
                </div>
            </div>

            <!-- Bouton retour -->
            <div class="text-center mt-4 mb-4">
                <Link :href="route('academique.composition.mes-examens')" class="btn btn-primary btn-lg">
                    <i class="fa fa-arrow-left me-2"></i> Retour à mes examens
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.resultat-page {
    max-width: 800px;
    margin: 0 auto;
}

.resultat-header {
    text-align: center;
    padding: 2rem;
    border-radius: 12px;
    color: white;
}

.resultat-pass {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.resultat-fail {
    background: linear-gradient(135deg, #dc3545, #e74c3c);
}

.resultat-icon { margin-bottom: 0.5rem; }

/* Score Card */
.score-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-top: -1rem;
    margin-left: 2rem;
    margin-right: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    display: flex;
    gap: 2rem;
    align-items: center;
}

.score-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 6px solid;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.score-value { font-size: 2.2rem; font-weight: 800; line-height: 1; }
.score-max { font-size: 1rem; color: #999; }

.score-details { flex: 1; }

.score-stats {
    display: flex;
    gap: 1.5rem;
    margin-top: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stat-item i { font-size: 1.2rem; }
.stat-value { font-weight: 700; font-size: 1.1rem; }
.stat-label { font-size: 0.8rem; color: #777; display: block; }

/* Correction */
.correction-title {
    color: #0B5697;
    border-bottom: 2px solid #0FBCAF;
    padding-bottom: 0.5rem;
}

.correction-item {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.8rem;
    border-left: 4px solid #dee2e6;
}

.correction-correct { border-left-color: #28a745; }
.correction-wrong { border-left-color: #dc3545; }

.correction-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.correction-enonce {
    margin-bottom: 0.8rem;
    font-size: 0.95rem;
}

.correction-choices {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.correction-choice {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.9rem;
    background: #f8f9fa;
}

.correction-choice.choice-correct {
    background: #d4edda;
}

.correction-choice.choice-wrong-chosen {
    background: #f8d7da;
}

.correction-letter {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.7rem;
    flex-shrink: 0;
}

.choice-correct .correction-letter { background: #28a745; }
.choice-wrong-chosen .correction-letter { background: #dc3545; }

.correction-explication {
    margin-top: 0.5rem;
    padding: 0.5rem 0.8rem;
    background: #fff3cd;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #856404;
}

@media (max-width: 768px) {
    .score-card {
        flex-direction: column;
        text-align: center;
        margin-left: 0.5rem;
        margin-right: 0.5rem;
    }
    .score-stats { flex-direction: column; gap: 0.5rem; }
}
</style>
