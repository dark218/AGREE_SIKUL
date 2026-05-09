<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();

const props = defineProps({
    tentative: Object,
    examen: Object,
    questions: Array,
    reponses_existantes: Object,
    temps_restant_secondes: Number,
    title: String,
});

// État
const currentIndex = ref(0);
const reponses = ref({});
const saving = ref(false);
const submitting = ref(false);
const timeLeft = ref(props.temps_restant_secondes);
const showConfirmModal = ref(false);
const showTimeUpModal = ref(false);
const showWarningModal = ref(false);
const warningMessage = ref('');
const warningCount = ref(0);
let timerInterval = null;

// ============================================================
// SYSTÈME ANTI-TRICHE
// ============================================================
const incidents = ref({
    tab_switch: 0,
    copy_attempt: 0,
    paste_attempt: 0,
    right_click: 0,
    fullscreen_exit: 0,
    devtools_open: 0,
    window_blur: 0,
});

const MAX_WARNINGS = 5;

const logIncident = async (type, details = '') => {
    incidents.value[type] = (incidents.value[type] || 0) + 1;
    warningCount.value++;

    try {
        await axios.post(route('academique.composition.log-incident', props.tentative.id), {
            type_incident: type,
            details: details,
        });
    } catch (e) {
        console.error('Log incident error:', e);
    }

    // Avertissement visuel
    const messages = {
        tab_switch: 'Vous avez quitté la fenêtre d\'examen ! Cette action est enregistrée.',
        copy_attempt: 'La copie est interdite pendant l\'examen !',
        paste_attempt: 'Le collage est interdit pendant l\'examen !',
        right_click: 'Le clic droit est désactivé pendant l\'examen.',
        fullscreen_exit: 'Vous êtes sorti du mode plein écran ! Veuillez y revenir.',
        devtools_open: 'L\'ouverture des outils développeur est interdite !',
        window_blur: 'Vous avez quitté la fenêtre d\'examen !',
    };

    warningMessage.value = messages[type] || 'Action suspecte détectée.';
    showWarningModal.value = true;

    // Auto-soumission si trop d'incidents
    if (warningCount.value >= MAX_WARNINGS) {
        warningMessage.value = `Vous avez atteint ${MAX_WARNINGS} avertissements. Votre examen sera soumis automatiquement.`;
        setTimeout(() => {
            autoSubmit();
        }, 3000);
    }

    setTimeout(() => { showWarningModal.value = false; }, 4000);
};

// Anti copier/coller
const onCopy = (e) => { e.preventDefault(); logIncident('copy_attempt'); };
const onPaste = (e) => { e.preventDefault(); logIncident('paste_attempt'); };
const onCut = (e) => { e.preventDefault(); logIncident('copy_attempt', 'cut'); };

// Anti clic droit
const onContextMenu = (e) => { e.preventDefault(); logIncident('right_click'); };

// Détection changement d'onglet/fenêtre
const onVisibilityChange = () => {
    if (document.hidden) {
        logIncident('tab_switch', 'Page hidden');
    }
};

const onWindowBlur = () => {
    logIncident('window_blur', 'Window lost focus');
};

// Anti devtools (Ctrl+Shift+I, F12, Ctrl+U)
const onKeyDown = (e) => {
    // F12
    if (e.key === 'F12') { e.preventDefault(); logIncident('devtools_open', 'F12'); return; }
    // Ctrl+Shift+I (Inspect)
    if (e.ctrlKey && e.shiftKey && e.key === 'I') { e.preventDefault(); logIncident('devtools_open', 'Ctrl+Shift+I'); return; }
    // Ctrl+Shift+J (Console)
    if (e.ctrlKey && e.shiftKey && e.key === 'J') { e.preventDefault(); logIncident('devtools_open', 'Ctrl+Shift+J'); return; }
    // Ctrl+U (View Source)
    if (e.ctrlKey && e.key === 'u') { e.preventDefault(); logIncident('devtools_open', 'Ctrl+U'); return; }
    // Ctrl+C / Ctrl+V / Ctrl+X
    if (e.ctrlKey && e.key === 'c') { e.preventDefault(); logIncident('copy_attempt', 'Ctrl+C'); return; }
    if (e.ctrlKey && e.key === 'v') { e.preventDefault(); logIncident('paste_attempt', 'Ctrl+V'); return; }
    if (e.ctrlKey && e.key === 'x') { e.preventDefault(); logIncident('copy_attempt', 'Ctrl+X'); return; }
    // Print Screen
    if (e.key === 'PrintScreen') { e.preventDefault(); logIncident('copy_attempt', 'PrintScreen'); return; }
};

// Anti sélection texte
const onSelectStart = (e) => { e.preventDefault(); };

// ============================================================

// Initialiser
onMounted(() => {
    if (props.reponses_existantes) {
        Object.keys(props.reponses_existantes).forEach(qId => {
            reponses.value[qId] = props.reponses_existantes[qId];
        });
    }

    // Démarrer le chrono
    timerInterval = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        } else {
            clearInterval(timerInterval);
            autoSubmit();
        }
    }, 1000);

    // Activer anti-triche
    document.addEventListener('copy', onCopy);
    document.addEventListener('paste', onPaste);
    document.addEventListener('cut', onCut);
    document.addEventListener('contextmenu', onContextMenu);
    document.addEventListener('visibilitychange', onVisibilityChange);
    document.addEventListener('keydown', onKeyDown);
    document.addEventListener('selectstart', onSelectStart);
    window.addEventListener('blur', onWindowBlur);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    // Nettoyer anti-triche
    document.removeEventListener('copy', onCopy);
    document.removeEventListener('paste', onPaste);
    document.removeEventListener('cut', onCut);
    document.removeEventListener('contextmenu', onContextMenu);
    document.removeEventListener('visibilitychange', onVisibilityChange);
    document.removeEventListener('keydown', onKeyDown);
    document.removeEventListener('selectstart', onSelectStart);
    window.removeEventListener('blur', onWindowBlur);
});

// Computed
const currentQuestion = computed(() => props.questions[currentIndex.value]);
const isFirst = computed(() => currentIndex.value === 0);
const isLast = computed(() => currentIndex.value === props.questions.length - 1);
const totalQuestions = computed(() => props.questions.length);

const questionsRepondues = computed(() => {
    let count = 0;
    props.questions.forEach(q => {
        const rep = reponses.value[q.id];
        if (rep && (rep.reponse_choisie_id || rep.reponse_libre)) count++;
    });
    return count;
});

const progressPercent = computed(() => {
    return totalQuestions.value > 0 ? Math.round((questionsRepondues.value / totalQuestions.value) * 100) : 0;
});

const formattedTime = computed(() => {
    const h = Math.floor(timeLeft.value / 3600);
    const m = Math.floor((timeLeft.value % 3600) / 60);
    const s = timeLeft.value % 60;
    if (h > 0) return `${h}h ${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

const isTimeWarning = computed(() => timeLeft.value < 300); // < 5 min
const isTimeDanger = computed(() => timeLeft.value < 60); // < 1 min

const currentReponse = computed(() => {
    return reponses.value[currentQuestion.value?.id] || {};
});

// Navigation
const goTo = (index) => {
    if (!props.examen.retour_arriere && index < currentIndex.value) return;
    saveCurrentAnswer();
    currentIndex.value = index;
};

const next = () => {
    saveCurrentAnswer();
    if (!isLast.value) currentIndex.value++;
};

const prev = () => {
    if (props.examen.retour_arriere && !isFirst.value) {
        saveCurrentAnswer();
        currentIndex.value--;
    }
};

// Sélection réponse QCM
const selectReponse = (questionId, reponseId) => {
    if (!reponses.value[questionId]) {
        reponses.value[questionId] = {};
    }
    reponses.value[questionId] = {
        ...reponses.value[questionId],
        reponse_choisie_id: reponseId,
    };
};

// Réponse libre
const updateReponseLibre = (questionId, texte) => {
    if (!reponses.value[questionId]) {
        reponses.value[questionId] = {};
    }
    reponses.value[questionId] = {
        ...reponses.value[questionId],
        reponse_libre: texte,
    };
};

// Sauvegarder la réponse courante (AJAX)
const saveCurrentAnswer = async () => {
    const q = currentQuestion.value;
    const rep = reponses.value[q?.id];
    if (!q || !rep || (!rep.reponse_choisie_id && !rep.reponse_libre)) return;

    saving.value = true;
    try {
        await axios.post(route('academique.composition.sauvegarder-reponse', props.tentative.id), {
            question_id: q.id,
            reponse_choisie_id: rep.reponse_choisie_id || null,
            reponse_libre: rep.reponse_libre || null,
        });
    } catch (e) {
        if (e.response?.data?.time_up) {
            showTimeUpModal.value = true;
        }
        console.error('Erreur sauvegarde:', e);
    } finally {
        saving.value = false;
    }
};

// Soumettre l'examen
const submitExam = () => {
    saveCurrentAnswer();
    showConfirmModal.value = true;
};

const confirmSubmit = () => {
    submitting.value = true;
    showConfirmModal.value = false;
    router.post(route('academique.composition.soumettre', props.tentative.id), {}, {
        onFinish: () => { submitting.value = false; }
    });
};

// Soumission automatique (temps écoulé)
const autoSubmit = () => {
    showTimeUpModal.value = true;
    saveCurrentAnswer();
    setTimeout(() => {
        router.post(route('academique.composition.soumettre', props.tentative.id));
    }, 2000);
};

// Vérifie si une question est répondue
const isAnswered = (questionId) => {
    const rep = reponses.value[questionId];
    return rep && (rep.reponse_choisie_id || rep.reponse_libre);
};
</script>

<template>
    <div class="composer-page">
        <!-- Barre du haut fixe -->
        <div class="composer-topbar">
            <div class="topbar-left">
                <h6 class="mb-0 text-white">{{ examen.titre }}</h6>
                <small class="text-light opacity-75">
                    {{ examen.matiere }} — {{ examen.nombre_questions }} questions
                </small>
            </div>
            <div class="topbar-center">
                <div
                    class="timer-display"
                    :class="{ 'timer-warning': isTimeWarning, 'timer-danger': isTimeDanger }"
                >
                    <i class="fa fa-clock me-1"></i>
                    {{ formattedTime }}
                </div>
            </div>
            <div class="topbar-right">
                <div class="progress-info">
                    <small class="text-light">{{ questionsRepondues }}/{{ totalQuestions }} répondues</small>
                    <div class="progress mt-1" style="height: 6px; width: 120px;">
                        <div class="progress-bar bg-success" :style="{ width: progressPercent + '%' }"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="composer-body">
            <!-- Sidebar : navigation questions -->
            <div class="composer-sidebar">
                <div class="sidebar-title">Questions</div>
                <div class="question-nav-grid">
                    <button
                        v-for="(q, idx) in questions"
                        :key="q.id"
                        class="question-nav-btn"
                        :class="{
                            'active': idx === currentIndex,
                            'answered': isAnswered(q.id) && idx !== currentIndex,
                            'disabled': !examen.retour_arriere && idx < currentIndex
                        }"
                        @click="goTo(idx)"
                        :disabled="!examen.retour_arriere && idx < currentIndex && idx !== currentIndex"
                    >
                        {{ idx + 1 }}
                    </button>
                </div>
                <div class="sidebar-legend mt-3">
                    <div class="legend-item"><span class="legend-dot active"></span> Actuelle</div>
                    <div class="legend-item"><span class="legend-dot answered"></span> Répondue</div>
                    <div class="legend-item"><span class="legend-dot"></span> Non répondue</div>
                </div>

                <!-- Compteur surveillance -->
                <div v-if="warningCount > 0" class="surveillance-counter mt-3">
                    <div class="d-flex align-items-center gap-1 text-danger small">
                        <i class="fa fa-exclamation-triangle"></i>
                        <span>{{ warningCount }}/{{ MAX_WARNINGS }} avertissements</span>
                    </div>
                    <div class="progress mt-1" style="height: 4px;">
                        <div class="progress-bar bg-danger" :style="{ width: (warningCount / MAX_WARNINGS * 100) + '%' }"></div>
                    </div>
                </div>

                <!-- Bouton soumettre -->
                <button
                    class="btn btn-danger w-100 mt-3"
                    @click="submitExam"
                    :disabled="submitting"
                >
                    <i class="fa fa-paper-plane me-1"></i> Soumettre l'examen
                </button>
            </div>

            <!-- Zone de question -->
            <div class="composer-main">
                <!-- Instructions (premier affichage) -->
                <div v-if="examen.instructions && currentIndex === 0" class="instructions-banner">
                    <i class="fa fa-info-circle me-2"></i>
                    <span>{{ examen.instructions }}</span>
                </div>

                <!-- Question courante -->
                <div v-if="currentQuestion" class="question-area">
                    <div class="question-header-bar">
                        <span class="question-num">Question {{ currentIndex + 1 }} / {{ totalQuestions }}</span>
                        <span class="question-pts">{{ currentQuestion.points }} pt{{ currentQuestion.points > 1 ? 's' : '' }}</span>
                        <span v-if="currentQuestion.obligatoire" class="badge bg-danger ms-2">Obligatoire</span>
                        <span v-if="saving" class="ms-2 text-muted small">
                            <span class="spinner-border spinner-border-sm"></span> Sauvegarde...
                        </span>
                    </div>

                    <div class="question-enonce">
                        {{ currentQuestion.enonce }}
                    </div>

                    <!-- QCM / Vrai-Faux -->
                    <div v-if="['qcm', 'vrai_faux'].includes(currentQuestion.type)" class="choices-area">
                        <div
                            v-for="(rep, rIdx) in currentQuestion.reponses"
                            :key="rep.id"
                            class="choice-item"
                            :class="{ 'choice-selected': currentReponse.reponse_choisie_id === rep.id }"
                            @click="selectReponse(currentQuestion.id, rep.id)"
                        >
                            <div class="choice-letter">{{ String.fromCharCode(65 + rIdx) }}</div>
                            <div class="choice-text">{{ rep.texte }}</div>
                            <div v-if="currentReponse.reponse_choisie_id === rep.id" class="choice-check">
                                <i class="fa fa-check-circle"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Réponse libre -->
                    <div v-else-if="currentQuestion.type === 'reponse_libre'" class="libre-area">
                        <textarea
                            :value="currentReponse.reponse_libre || ''"
                            @input="updateReponseLibre(currentQuestion.id, $event.target.value)"
                            class="form-control"
                            rows="6"
                            placeholder="Écrivez votre réponse ici..."
                        ></textarea>
                    </div>

                    <!-- Texte à trous -->
                    <div v-else-if="currentQuestion.type === 'texte_trous'" class="libre-area">
                        <textarea
                            :value="currentReponse.reponse_libre || ''"
                            @input="updateReponseLibre(currentQuestion.id, $event.target.value)"
                            class="form-control"
                            rows="4"
                            placeholder="Complétez le texte..."
                        ></textarea>
                    </div>

                    <!-- Navigation question -->
                    <div class="question-nav-bar">
                        <button
                            v-if="examen.retour_arriere"
                            class="btn btn-outline-secondary"
                            @click="prev"
                            :disabled="isFirst"
                        >
                            <i class="fa fa-arrow-left me-1"></i> Précédente
                        </button>
                        <div v-else></div>

                        <button
                            v-if="!isLast"
                            class="btn btn-primary"
                            @click="next"
                        >
                            Suivante <i class="fa fa-arrow-right ms-1"></i>
                        </button>
                        <button
                            v-else
                            class="btn btn-danger"
                            @click="submitExam"
                            :disabled="submitting"
                        >
                            <i class="fa fa-paper-plane me-1"></i> Soumettre
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Confirmation soumission -->
        <div v-if="showConfirmModal" class="modal-overlay">
            <div class="modal-box">
                <div class="modal-box-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fa fa-exclamation-triangle me-2"></i> Soumettre l'examen ?</h5>
                </div>
                <div class="modal-box-body">
                    <p>Vous avez répondu à <strong>{{ questionsRepondues }}</strong> question(s) sur <strong>{{ totalQuestions }}</strong>.</p>
                    <p v-if="questionsRepondues < totalQuestions" class="text-danger">
                        <i class="fa fa-exclamation-circle me-1"></i>
                        Attention : {{ totalQuestions - questionsRepondues }} question(s) sans réponse !
                    </p>
                    <p class="fw-bold">Cette action est irréversible. Voulez-vous continuer ?</p>
                </div>
                <div class="modal-box-footer">
                    <button class="btn btn-secondary" @click="showConfirmModal = false">Annuler</button>
                    <button class="btn btn-danger" @click="confirmSubmit" :disabled="submitting">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="fa fa-paper-plane me-1"></i> Oui, soumettre
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal avertissement anti-triche -->
        <div v-if="showWarningModal" class="warning-toast">
            <div class="warning-toast-content">
                <i class="fa fa-shield-alt warning-icon"></i>
                <div>
                    <strong>Surveillance active</strong>
                    <p class="mb-0">{{ warningMessage }}</p>
                    <small class="text-light">Avertissement {{ warningCount }}/{{ MAX_WARNINGS }}</small>
                </div>
            </div>
        </div>

        <!-- Modal temps écoulé -->
        <div v-if="showTimeUpModal" class="modal-overlay">
            <div class="modal-box">
                <div class="modal-box-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fa fa-clock me-2"></i> Temps écoulé !</h5>
                </div>
                <div class="modal-box-body text-center">
                    <i class="fa fa-hourglass-end fa-3x text-danger mb-3"></i>
                    <p class="fs-5">Le temps imparti est terminé.</p>
                    <p>Votre examen est en cours de soumission automatique...</p>
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.composer-page {
    min-height: 100vh;
    background: linear-gradient(160deg, #e8f0f8 0%, #f0faf9 30%, #fef6f0 70%, #e8f0f8 100%);
    background-attachment: fixed;
}

/* Top Bar */
.composer-topbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: linear-gradient(135deg, #0B5697 0%, #094578 50%, #0FBCAF 100%);
    padding: 0.8rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(11, 86, 151, 0.35);
}

.timer-display {
    background: white;
    color: #0B5697;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-size: 1.4rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    letter-spacing: 1px;
}

.timer-warning {
    background: linear-gradient(135deg, #fff3cd, #ffe69c);
    color: #856404;
    animation: pulse-timer 1s infinite;
    box-shadow: 0 0 15px rgba(255, 193, 7, 0.4);
}

.timer-danger {
    background: linear-gradient(135deg, #f8d7da, #f5c2c7);
    color: #721c24;
    animation: pulse-timer 0.5s infinite;
    box-shadow: 0 0 20px rgba(220, 53, 69, 0.5);
}

@keyframes pulse-timer {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.08); }
}

/* Body layout */
.composer-body {
    display: flex;
    max-width: 1200px;
    margin: 1.5rem auto;
    gap: 1.5rem;
    padding: 0 1rem;
}

/* Sidebar */
.composer-sidebar {
    width: 230px;
    flex-shrink: 0;
    background: linear-gradient(180deg, #ffffff, #f8fbff);
    border-radius: 16px;
    padding: 1.2rem;
    box-shadow: 0 4px 20px rgba(11, 86, 151, 0.08);
    position: sticky;
    top: 80px;
    height: fit-content;
    border: 1px solid rgba(11, 86, 151, 0.1);
}

.sidebar-title {
    font-weight: 800;
    color: #0B5697;
    margin-bottom: 1rem;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #0FBCAF;
    padding-bottom: 0.5rem;
}

.question-nav-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.question-nav-btn {
    width: 42px;
    height: 42px;
    border: 2px solid #dee2e6;
    border-radius: 10px;
    background: white;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.25s ease;
    color: #555;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.question-nav-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(11, 86, 151, 0.15);
}

.question-nav-btn.active {
    background: linear-gradient(135deg, #0B5697, #0FBCAF);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(11, 86, 151, 0.3);
    transform: scale(1.05);
}

.question-nav-btn.answered {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border-color: #28a745;
    color: #155724;
}

.question-nav-btn.disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.sidebar-legend { font-size: 0.75rem; color: #777; margin-top: 1rem; }
.legend-item { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.legend-dot {
    width: 16px;
    height: 16px;
    border-radius: 5px;
    border: 2px solid #dee2e6;
    background: white;
}
.legend-dot.active { background: linear-gradient(135deg, #0B5697, #0FBCAF); border-color: transparent; }
.legend-dot.answered { background: #d4edda; border-color: #28a745; }

/* Main */
.composer-main {
    flex: 1;
    min-width: 0;
}

.instructions-banner {
    background: linear-gradient(135deg, #e8f8f6, #e8f0f8);
    border: 1px solid #0FBCAF;
    border-left: 4px solid #0FBCAF;
    border-radius: 10px;
    padding: 1rem 1.2rem;
    margin-bottom: 1.2rem;
    color: #0B5697;
    font-size: 0.9rem;
    box-shadow: 0 2px 8px rgba(15, 188, 175, 0.1);
}

.question-area {
    background: white;
    border-radius: 16px;
    padding: 2rem 2.5rem;
    box-shadow: 0 4px 25px rgba(11, 86, 151, 0.08);
    border: 1px solid rgba(11, 86, 151, 0.06);
}

.question-header-bar {
    display: flex;
    align-items: center;
    margin-bottom: 1.2rem;
    padding-bottom: 1rem;
    border-bottom: 3px solid;
    border-image: linear-gradient(90deg, #0B5697, #0FBCAF, #E5590C) 1;
}

.question-num {
    font-weight: 800;
    color: #0B5697;
    font-size: 1.1rem;
}

.question-pts {
    margin-left: auto;
    background: linear-gradient(135deg, #0B5697, #0FBCAF);
    color: white;
    padding: 0.3rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(11, 86, 151, 0.2);
}

.question-enonce {
    font-size: 1.2rem;
    line-height: 1.7;
    color: #2c3e50;
    margin-bottom: 1.8rem;
    padding: 1rem 1.2rem;
    background: linear-gradient(135deg, #f8fbff, #fafffe);
    border-radius: 12px;
    border-left: 4px solid #0B5697;
}

/* Choix QCM */
.choices-area {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.choice-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.1rem 1.4rem;
    border: 2px solid #e8ecf0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #fafbfc, #ffffff);
    position: relative;
    overflow: hidden;
}

.choice-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 0;
    background: linear-gradient(180deg, #0FBCAF, #0B5697);
    transition: width 0.3s ease;
    border-radius: 12px 0 0 12px;
}

.choice-item:hover {
    border-color: #0FBCAF;
    background: linear-gradient(135deg, #f0faf9, #ffffff);
    transform: translateX(4px);
    box-shadow: 0 4px 15px rgba(15, 188, 175, 0.12);
}

.choice-item:hover::before {
    width: 4px;
}

.choice-item.choice-selected {
    border-color: #0B5697;
    background: linear-gradient(135deg, #e8f0f8, #edf7f6);
    box-shadow: 0 4px 20px rgba(11, 86, 151, 0.15);
    transform: translateX(4px);
}

.choice-item.choice-selected::before {
    width: 5px;
    background: linear-gradient(180deg, #E5590C, #0FBCAF);
}

.choice-letter {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e8ecf0, #dee2e6);
    color: #555;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.95rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
    z-index: 1;
}

.choice-selected .choice-letter {
    background: linear-gradient(135deg, #0B5697, #0FBCAF);
    color: white;
    box-shadow: 0 3px 10px rgba(11, 86, 151, 0.3);
}

.choice-text {
    flex: 1;
    font-size: 1.05rem;
    font-weight: 500;
    color: #2c3e50;
    z-index: 1;
}

.choice-check {
    color: #E5590C;
    font-size: 1.4rem;
    z-index: 1;
    animation: check-pop 0.3s ease;
}

@keyframes check-pop {
    0% { transform: scale(0); }
    60% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

/* Réponse libre */
.libre-area textarea {
    font-size: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 1rem;
    background: #fafbfc;
}

.libre-area textarea:focus {
    border-color: #0FBCAF;
    box-shadow: 0 0 0 4px rgba(15, 188, 175, 0.12);
    background: white;
}

/* Navigation */
.question-nav-bar {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    padding-top: 1.2rem;
    border-top: 2px solid #f0f2f5;
}

.question-nav-bar .btn {
    padding: 0.6rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s;
}

.question-nav-bar .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.question-nav-bar .btn-danger {
    background: linear-gradient(135deg, #E5590C, #dc3545);
    border: none;
}

.question-nav-bar .btn-primary {
    background: linear-gradient(135deg, #0B5697, #0FBCAF);
    border: none;
}

/* Sidebar submit button */
.composer-sidebar .btn-danger {
    background: linear-gradient(135deg, #E5590C, #dc3545);
    border: none;
    border-radius: 10px;
    font-weight: 700;
    padding: 0.7rem;
    box-shadow: 0 4px 15px rgba(229, 89, 12, 0.3);
    transition: all 0.3s;
}

.composer-sidebar .btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(229, 89, 12, 0.4);
}

/* Modals */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(11, 86, 151, 0.4);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-box {
    background: white;
    border-radius: 16px;
    width: 450px;
    max-width: 90%;
    overflow: hidden;
    box-shadow: 0 25px 80px rgba(11, 86, 151, 0.3);
    animation: modal-in 0.3s ease;
}

@keyframes modal-in {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.modal-box-header { padding: 1rem 1.5rem; }
.modal-box-body { padding: 1.5rem; }
.modal-box-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* Responsive */
/* Warning Toast Anti-Triche */
.warning-toast {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 10000;
    animation: slide-in-right 0.4s ease;
}

.warning-toast-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(220, 53, 69, 0.4);
    min-width: 350px;
    max-width: 450px;
}

.warning-icon {
    font-size: 2rem;
    opacity: 0.9;
}

@keyframes slide-in-right {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.surveillance-counter {
    background: #fff5f5;
    border: 1px solid #f8d7da;
    border-radius: 8px;
    padding: 0.5rem;
}

/* Anti sélection texte */
.composer-page {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

/* Sauf les textarea pour réponse libre */
.libre-area textarea {
    user-select: text;
    -webkit-user-select: text;
}

@media (max-width: 768px) {
    .composer-body { flex-direction: column; }
    .composer-sidebar {
        width: 100%;
        position: static;
    }
    .question-nav-grid { grid-template-columns: repeat(8, 1fr); }
    .composer-topbar { flex-wrap: wrap; gap: 0.5rem; }
    .warning-toast { right: 10px; left: 10px; }
    .warning-toast-content { min-width: auto; }
}
</style>
