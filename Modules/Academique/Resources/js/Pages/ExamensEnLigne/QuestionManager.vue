<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t } = useI18n();

const props = defineProps({
    examenId: {
        type: [Number, String],
        required: true,
    },
    readOnly: {
        type: Boolean,
        default: false,
    },
});

const questions = ref([]);
const totalPoints = ref(0);
const loading = ref(false);
const saving = ref(false);
const showForm = ref(false);
const editingQuestion = ref(null);
const errors = ref({});
const successMessage = ref('');
const errorMessage = ref('');

// Formulaire question
const questionForm = ref({
    type: 'qcm',
    enonce: '',
    explication: '',
    points: 1,
    obligatoire: true,
    reponses: [
        { texte: '', est_correcte: false, explication: '' },
        { texte: '', est_correcte: false, explication: '' },
    ],
});

const typeOptions = [
    { id: 'qcm', label: 'QCM (Choix multiples)', icon: 'fa-list-ul' },
    { id: 'vrai_faux', label: 'Vrai / Faux', icon: 'fa-check-circle' },
    { id: 'reponse_libre', label: 'Réponse libre', icon: 'fa-pen' },
    { id: 'texte_trous', label: 'Texte à trous', icon: 'fa-puzzle-piece' },
];

const needsReponses = computed(() => ['qcm', 'vrai_faux'].includes(questionForm.value.type));

const questionsUrl = computed(() => `/academique/examens-en-ligne/${props.examenId}/questions`);

// Charger les questions
const loadQuestions = async () => {
    loading.value = true;
    try {
        const response = await axios.get(questionsUrl.value);
        questions.value = response.data.questions;
        totalPoints.value = response.data.total_points;
    } catch (e) {
        console.error('Erreur chargement questions:', e);
    } finally {
        loading.value = false;
    }
};

// Réinitialiser le formulaire
const resetForm = () => {
    questionForm.value = {
        type: 'qcm',
        enonce: '',
        explication: '',
        points: 1,
        obligatoire: true,
        reponses: [
            { texte: '', est_correcte: false, explication: '' },
            { texte: '', est_correcte: false, explication: '' },
        ],
    };
    editingQuestion.value = null;
    errors.value = {};
};

// Ouvrir le formulaire pour nouvelle question
const openNewForm = () => {
    resetForm();
    showForm.value = true;
};

// Ouvrir le formulaire pour modifier
const openEditForm = (question) => {
    editingQuestion.value = question.id;
    questionForm.value = {
        type: question.type,
        enonce: question.enonce,
        explication: question.explication || '',
        points: question.points,
        obligatoire: question.obligatoire,
        reponses: question.reponses?.length > 0
            ? question.reponses.map(r => ({
                id: r.id,
                texte: r.texte,
                est_correcte: r.est_correcte,
                explication: r.explication || '',
            }))
            : [
                { texte: '', est_correcte: false, explication: '' },
                { texte: '', est_correcte: false, explication: '' },
            ],
    };
    showForm.value = true;
};

// Quand on change le type de question
const onTypeChange = () => {
    if (questionForm.value.type === 'vrai_faux') {
        questionForm.value.reponses = [
            { texte: 'Vrai', est_correcte: true, explication: '' },
            { texte: 'Faux', est_correcte: false, explication: '' },
        ];
    } else if (questionForm.value.type === 'qcm') {
        if (questionForm.value.reponses.length < 2) {
            questionForm.value.reponses = [
                { texte: '', est_correcte: false, explication: '' },
                { texte: '', est_correcte: false, explication: '' },
            ];
        }
    }
};

// Ajouter un choix de réponse
const addReponse = () => {
    questionForm.value.reponses.push({ texte: '', est_correcte: false, explication: '' });
};

// Supprimer un choix de réponse
const removeReponse = (index) => {
    if (questionForm.value.reponses.length > 2) {
        questionForm.value.reponses.splice(index, 1);
    }
};

// Sauvegarder la question
const saveQuestion = async () => {
    saving.value = true;
    errors.value = {};
    successMessage.value = '';
    errorMessage.value = '';

    try {
        const data = {
            type: questionForm.value.type,
            enonce: questionForm.value.enonce,
            explication: questionForm.value.explication,
            points: questionForm.value.points,
            obligatoire: questionForm.value.obligatoire,
        };

        if (needsReponses.value) {
            data.reponses = questionForm.value.reponses;
        }

        console.log('Envoi question vers:', questionsUrl.value, data);

        let response;
        if (editingQuestion.value) {
            response = await axios.put(`${questionsUrl.value}/${editingQuestion.value}`, data);
        } else {
            response = await axios.post(questionsUrl.value, data);
        }

        console.log('Réponse serveur:', response.data);
        successMessage.value = editingQuestion.value ? 'Question mise à jour !' : 'Question ajoutée avec succès !';
        showForm.value = false;
        resetForm();
        await loadQuestions();

        setTimeout(() => { successMessage.value = ''; }, 3000);
    } catch (e) {
        console.error('Erreur sauvegarde question:', e.response?.status, e.response?.data || e.message);
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {};
            errorMessage.value = 'Veuillez corriger les erreurs ci-dessous.';
        } else {
            errorMessage.value = e.response?.data?.message || 'Erreur lors de la sauvegarde. Vérifiez la console.';
        }
    } finally {
        saving.value = false;
    }
};

// Supprimer une question
const deleteQuestion = async (questionId) => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette question ?')) return;

    try {
        await axios.delete(`${questionsUrl.value}/${questionId}`);
        await loadQuestions();
    } catch (e) {
        console.error('Erreur suppression:', e);
    }
};

// Type badge
const typeBadge = (type) => {
    const map = {
        qcm: { class: 'bg-primary', label: 'QCM' },
        vrai_faux: { class: 'bg-info', label: 'Vrai/Faux' },
        reponse_libre: { class: 'bg-warning', label: 'Libre' },
        texte_trous: { class: 'bg-secondary', label: 'Trous' },
    };
    return map[type] || { class: 'bg-secondary', label: type };
};

onMounted(() => {
    loadQuestions();
});
</script>

<template>
    <div class="question-manager">
        <!-- En-tête -->
        <div class="qm-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="fa fa-question-circle me-1"></i>
                        Banque de questions
                    </h6>
                    <small class="text-muted">
                        {{ questions.length }} question(s) — Total : {{ totalPoints }} pts
                    </small>
                </div>
                <button
                    v-if="!readOnly"
                    class="btn btn-primary btn-sm"
                    @click="openNewForm"
                    :disabled="showForm"
                >
                    <i class="fa fa-plus me-1"></i> Ajouter une question
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div v-if="successMessage" class="alert alert-success mt-2 py-2 small">
            <i class="fa fa-check-circle me-1"></i> {{ successMessage }}
        </div>
        <div v-if="errorMessage" class="alert alert-danger mt-2 py-2 small">
            <i class="fa fa-exclamation-circle me-1"></i> {{ errorMessage }}
        </div>

        <!-- Formulaire d'ajout/modification -->
        <div v-if="showForm && !readOnly" class="qm-form-card mt-3">
            <div class="card">
                <div class="card-header bg-primary text-white py-2">
                    <strong>
                        <i class="fa fa-edit me-1"></i>
                        {{ editingQuestion ? 'Modifier la question' : 'Nouvelle question' }}
                    </strong>
                </div>
                <div class="card-body">
                    <!-- Type de question -->
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-bold">Type de question <span class="text-danger">*</span></label>
                            <div class="type-selector">
                                <div
                                    v-for="opt in typeOptions"
                                    :key="opt.id"
                                    class="type-option"
                                    :class="{ active: questionForm.type === opt.id }"
                                    @click="questionForm.type = opt.id; onTypeChange()"
                                >
                                    <i :class="'fa ' + opt.icon"></i>
                                    <span>{{ opt.label }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label fw-bold">Points <span class="text-danger">*</span></label>
                            <input
                                v-model.number="questionForm.points"
                                type="number"
                                min="0.5"
                                step="0.5"
                                class="form-control form-control-sm"
                            />
                            <div v-if="errors.points" class="text-danger small mt-1">{{ errors.points[0] }}</div>
                        </div>
                        <div class="col-sm-3 d-flex align-items-end">
                            <div class="form-check">
                                <input
                                    v-model="questionForm.obligatoire"
                                    type="checkbox"
                                    class="form-check-input"
                                    id="obligatoire"
                                />
                                <label class="form-check-label" for="obligatoire">Obligatoire</label>
                            </div>
                        </div>
                    </div>

                    <!-- Énoncé -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Énoncé de la question <span class="text-danger">*</span></label>
                        <textarea
                            v-model="questionForm.enonce"
                            class="form-control"
                            rows="3"
                            placeholder="Saisissez l'énoncé de la question..."
                        ></textarea>
                        <div v-if="errors.enonce" class="text-danger small mt-1">{{ errors.enonce[0] }}</div>
                    </div>

                    <!-- Réponses (QCM / Vrai-Faux) -->
                    <div v-if="needsReponses" class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fa fa-list me-1"></i>
                            Choix de réponse
                        </label>
                        <div class="alert alert-warning py-2 small mb-2">
                            <i class="fa fa-hand-pointer me-1"></i>
                            <strong>Cliquez sur "Bonne réponse"</strong> à droite pour indiquer la réponse correcte. Elle deviendra <strong class="text-success">verte</strong>.
                        </div>
                        <div v-if="errors.reponses" class="text-danger small mb-2">{{ errors.reponses[0] }}</div>

                        <div
                            v-for="(rep, index) in questionForm.reponses"
                            :key="index"
                            class="reponse-row"
                            :class="{ 'reponse-correcte': rep.est_correcte }"
                        >
                            <div class="reponse-letter">
                                {{ String.fromCharCode(65 + index) }}
                            </div>
                            <div class="reponse-input">
                                <input
                                    v-model="rep.texte"
                                    type="text"
                                    class="form-control form-control-sm"
                                    :placeholder="'Réponse ' + String.fromCharCode(65 + index) + '...'"
                                    :disabled="questionForm.type === 'vrai_faux'"
                                />
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm bonne-reponse-btn"
                                :class="rep.est_correcte ? 'btn-success' : 'btn-outline-secondary'"
                                @click="rep.est_correcte = !rep.est_correcte"
                                :title="rep.est_correcte ? 'Cliquer pour retirer' : 'Cliquer pour marquer comme bonne réponse'"
                            >
                                <i class="fa" :class="rep.est_correcte ? 'fa-check-circle' : 'fa-circle'"></i>
                                {{ rep.est_correcte ? 'Bonne réponse' : 'Bonne réponse ?' }}
                            </button>
                            <button
                                v-if="questionForm.type === 'qcm' && questionForm.reponses.length > 2"
                                class="btn btn-outline-danger btn-sm"
                                @click="removeReponse(index)"
                                title="Supprimer ce choix"
                            >
                                <i class="fa fa-times"></i>
                            </button>
                        </div>

                        <button
                            v-if="questionForm.type === 'qcm'"
                            class="btn btn-outline-primary btn-sm mt-2"
                            @click="addReponse"
                        >
                            <i class="fa fa-plus me-1"></i> Ajouter un choix
                        </button>
                    </div>

                    <!-- Info pour réponse libre -->
                    <div v-if="questionForm.type === 'reponse_libre'" class="alert alert-info small mb-3">
                        <i class="fa fa-info-circle me-1"></i>
                        Les questions à réponse libre nécessitent une correction manuelle par l'enseignant.
                    </div>

                    <!-- Explication (optionnel) -->
                    <div class="mb-3">
                        <label class="form-label">Explication (affichée après correction)</label>
                        <textarea
                            v-model="questionForm.explication"
                            class="form-control form-control-sm"
                            rows="2"
                            placeholder="Explication de la bonne réponse (optionnel)..."
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-secondary btn-sm" @click="showForm = false; resetForm()">
                            <i class="fa fa-times me-1"></i> Annuler
                        </button>
                        <button class="btn btn-primary btn-sm" @click="saveQuestion" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="fa fa-save me-1"></i>
                            {{ editingQuestion ? 'Mettre à jour' : 'Ajouter' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des questions -->
        <div class="qm-list mt-3">
            <div v-if="loading" class="text-center py-4">
                <span class="spinner-border spinner-border-sm me-1"></span> Chargement...
            </div>

            <div v-else-if="questions.length === 0" class="text-center py-4 text-muted">
                <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                Aucune question pour cet examen.
                <br />
                <small v-if="!readOnly">Cliquez sur "Ajouter une question" pour commencer.</small>
            </div>

            <div v-else>
                <div
                    v-for="(q, qIndex) in questions"
                    :key="q.id"
                    class="question-card"
                >
                    <div class="question-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="question-number">Q{{ qIndex + 1 }}</span>
                            <span class="badge" :class="typeBadge(q.type).class">
                                {{ typeBadge(q.type).label }}
                            </span>
                            <span class="badge bg-dark">{{ q.points }} pt{{ q.points > 1 ? 's' : '' }}</span>
                            <span v-if="q.obligatoire" class="badge bg-danger">Obligatoire</span>
                        </div>
                        <div v-if="!readOnly" class="question-actions">
                            <button class="btn btn-outline-primary btn-sm" @click="openEditForm(q)" title="Modifier">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" @click="deleteQuestion(q.id)" title="Supprimer">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="question-body">
                        <p class="mb-2">{{ q.enonce }}</p>
                        <!-- Réponses -->
                        <div v-if="q.reponses && q.reponses.length > 0" class="reponses-list">
                            <div
                                v-for="(r, rIndex) in q.reponses"
                                :key="r.id"
                                class="reponse-item"
                                :class="{ 'reponse-correcte-display': r.est_correcte }"
                            >
                                <span class="reponse-letter-display">{{ String.fromCharCode(65 + rIndex) }}</span>
                                <span>{{ r.texte }}</span>
                                <i v-if="r.est_correcte" class="fa fa-check-circle text-success ms-2"></i>
                            </div>
                        </div>
                        <!-- Explication -->
                        <div v-if="q.explication" class="mt-2 small text-muted">
                            <i class="fa fa-lightbulb me-1"></i> {{ q.explication }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.question-manager {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    background: #fafafa;
}

.qm-header {
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #0FBCAF;
}

/* Type Selector */
.type-selector {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.type-option {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.2s;
    background: white;
}

.type-option:hover {
    border-color: #0FBCAF;
}

.type-option.active {
    border-color: #0B5697;
    background-color: #e8f0f8;
    color: #0B5697;
    font-weight: 600;
}

/* Réponses dans le formulaire */
.reponse-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    margin-bottom: 0.4rem;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    background: white;
    transition: all 0.2s;
}

.reponse-row.reponse-correcte {
    border-color: #28a745;
    background-color: #f0fff0;
}

.bonne-reponse-btn {
    white-space: nowrap;
    min-width: 140px;
    font-weight: 600;
    font-size: 0.8rem;
}

.bonne-reponse-btn.btn-success {
    animation: pulse-green 0.3s ease;
}

@keyframes pulse-green {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.reponse-letter {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #0B5697;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.reponse-input {
    flex: 1;
}

/* Question Card dans la liste */
.question-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    background: white;
    overflow: hidden;
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.question-number {
    font-weight: 700;
    color: #0B5697;
    font-size: 0.9rem;
}

.question-actions {
    display: flex;
    gap: 0.3rem;
}

.question-body {
    padding: 0.8rem 1rem;
}

/* Réponses dans la liste */
.reponses-list {
    margin-top: 0.5rem;
}

.reponse-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.6rem;
    margin-bottom: 0.25rem;
    border-radius: 4px;
    font-size: 0.85rem;
    background: #f8f9fa;
}

.reponse-item.reponse-correcte-display {
    background: #d4edda;
    font-weight: 500;
}

.reponse-letter-display {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.65rem;
    flex-shrink: 0;
}

.reponse-correcte-display .reponse-letter-display {
    background: #28a745;
}
</style>
