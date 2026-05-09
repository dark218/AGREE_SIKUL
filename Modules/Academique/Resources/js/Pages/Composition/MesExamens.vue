<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();

const props = defineProps({
    examens: Array,
    apprenant: Object,
    error: String,
    title: String,
});

// Modal mot de passe
const showPasswordModal = ref(false);
const selectedExamen = ref(null);
const motDePasse = ref('');
const passwordError = ref('');
const loadingExamId = ref(null);

const openExam = (examen) => {
    if (examen.en_cours) {
        loadingExamId.value = examen.id;
        router.get(route('academique.composition.composer', examen.tentative_en_cours_id));
        return;
    }

    if (!examen.peut_composer) return;

    if (examen.a_mot_de_passe) {
        selectedExamen.value = examen;
        motDePasse.value = '';
        passwordError.value = '';
        showPasswordModal.value = true;
    } else {
        startExam(examen.id);
    }
};

const startExam = (examenId, password = null) => {
    loadingExamId.value = examenId;
    const data = password ? { mot_de_passe: password } : {};
    router.post(route('academique.composition.demarrer', examenId), data, {
        onError: (errors) => {
            loadingExamId.value = null;
            passwordError.value = 'Mot de passe incorrect.';
        },
        onFinish: () => {
            loadingExamId.value = null;
        }
    });
};

const submitPassword = () => {
    if (!motDePasse.value.trim()) {
        passwordError.value = 'Veuillez entrer le mot de passe.';
        return;
    }
    showPasswordModal.value = false;
    startExam(selectedExamen.value.id, motDePasse.value);
};

const statutBadge = (examen) => {
    if (examen.en_cours) return { class: 'bg-warning text-dark', label: 'En cours' };
    if (examen.meilleure_note !== null) return { class: 'bg-success', label: 'Terminé' };
    if (!examen.peut_composer) return { class: 'bg-secondary', label: 'Épuisé' };
    return { class: 'bg-primary', label: 'Disponible' };
};
</script>

<template>
    <Head :title="title" />
    <div class="body-wrapper">
        <div class="mes-examens">
            <!-- Header -->
            <div class="exam-header">
                <div class="exam-header-content">
                    <div>
                        <h4 class="mb-1">
                            <i class="fa fa-graduation-cap me-2"></i>
                            Mes Examens en Ligne
                        </h4>
                        <p v-if="apprenant" class="mb-0 text-light opacity-75">
                            {{ apprenant.prenoms }} {{ apprenant.nom }} — {{ apprenant.matricule }} — {{ apprenant.classe }}
                        </p>
                    </div>
                </div>
            </div>

            <AlertMessage />

            <!-- Erreur profil -->
            <div v-if="error" class="alert alert-warning mt-3">
                <i class="fa fa-exclamation-triangle me-2"></i>
                {{ error }}
            </div>

            <!-- Liste des examens -->
            <div v-if="examens && examens.length > 0" class="exam-grid mt-3">
                <div
                    v-for="examen in examens"
                    :key="examen.id"
                    class="exam-card"
                    :class="{
                        'exam-card-available': examen.peut_composer && !examen.en_cours,
                        'exam-card-in-progress': examen.en_cours,
                        'exam-card-done': examen.meilleure_note !== null && !examen.en_cours,
                        'exam-card-disabled': !examen.peut_composer && !examen.en_cours
                    }"
                >
                    <!-- Badge statut -->
                    <div class="exam-card-badge">
                        <span class="badge" :class="statutBadge(examen).class">
                            {{ statutBadge(examen).label }}
                        </span>
                    </div>

                    <!-- Infos examen -->
                    <div class="exam-card-body">
                        <h5 class="exam-title">{{ examen.titre }}</h5>
                        <div class="exam-meta">
                            <div v-if="examen.matiere" class="exam-meta-item">
                                <i class="fa fa-book"></i>
                                <span>{{ examen.matiere }}</span>
                            </div>
                            <div v-if="examen.enseignant" class="exam-meta-item">
                                <i class="fa fa-user-tie"></i>
                                <span>{{ examen.enseignant }}</span>
                            </div>
                            <div class="exam-meta-item">
                                <i class="fa fa-clock"></i>
                                <span>{{ examen.duree_minutes }} minutes</span>
                            </div>
                            <div class="exam-meta-item">
                                <i class="fa fa-question-circle"></i>
                                <span>{{ examen.nombre_questions }} questions</span>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="exam-dates">
                            <small>
                                <i class="fa fa-calendar me-1"></i>
                                Du {{ examen.date_debut }} au {{ examen.date_fin }}
                            </small>
                        </div>

                        <!-- Barème -->
                        <div class="exam-bareme">
                            <span class="badge bg-dark">Note sur {{ examen.note_maximum }}</span>
                            <span class="badge bg-outline">
                                Tentative {{ examen.nb_tentatives_utilisees }}/{{ examen.nombre_tentatives_max }}
                            </span>
                            <span v-if="examen.a_mot_de_passe" class="badge bg-warning text-dark">
                                <i class="fa fa-lock me-1"></i> Protégé
                            </span>
                        </div>

                        <!-- Meilleure note si déjà passé -->
                        <div v-if="examen.meilleure_note !== null" class="exam-result mt-2">
                            <div class="result-badge" :class="examen.meilleure_note >= (examen.note_maximum / 2) ? 'result-pass' : 'result-fail'">
                                <span class="result-score">{{ examen.meilleure_note }}</span>
                                <span class="result-max">/{{ examen.note_maximum }}</span>
                            </div>
                        </div>

                        <!-- Description -->
                        <p v-if="examen.description" class="exam-description">{{ examen.description }}</p>
                    </div>

                    <!-- Action -->
                    <div class="exam-card-footer">
                        <button
                            v-if="examen.en_cours"
                            class="btn btn-warning w-100"
                            @click="openExam(examen)"
                            :disabled="loadingExamId !== null"
                        >
                            <span v-if="loadingExamId === examen.id" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="fa fa-play-circle me-1"></i> Reprendre l'examen
                        </button>
                        <button
                            v-else-if="examen.peut_composer"
                            class="btn btn-primary w-100"
                            @click="openExam(examen)"
                            :disabled="loadingExamId !== null"
                        >
                            <span v-if="loadingExamId === examen.id" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="fa fa-play me-1"></i>
                            {{ examen.nb_tentatives_utilisees > 0 ? 'Retenter l\'examen' : 'Commencer l\'examen' }}
                        </button>
                        <button
                            v-else
                            class="btn btn-secondary w-100"
                            disabled
                        >
                            <i class="fa fa-ban me-1"></i> Plus de tentatives
                        </button>
                    </div>
                </div>
            </div>

            <!-- Aucun examen -->
            <div v-else-if="!error" class="text-center py-5 mt-3">
                <i class="fa fa-inbox fa-3x text-muted mb-3 d-block"></i>
                <h5 class="text-muted">Aucun examen disponible pour le moment</h5>
                <p class="text-muted">Les examens apparaîtront ici lorsque vos enseignants les publieront.</p>
            </div>
        </div>

        <!-- Modal Mot de passe -->
        <div v-if="showPasswordModal" class="modal-overlay" @click.self="showPasswordModal = false">
            <div class="modal-box">
                <div class="modal-box-header">
                    <h5 class="mb-0"><i class="fa fa-lock me-2"></i> Examen protégé</h5>
                </div>
                <div class="modal-box-body">
                    <p class="mb-3">
                        Cet examen est protégé par un mot de passe.
                        <br><strong>Demandez le mot de passe à votre surveillant.</strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mot de passe</label>
                        <input
                            v-model="motDePasse"
                            type="text"
                            class="form-control form-control-lg text-center"
                            placeholder="Entrez le mot de passe..."
                            @keyup.enter="submitPassword"
                            autofocus
                        />
                        <div v-if="passwordError" class="text-danger small mt-1">{{ passwordError }}</div>
                    </div>
                </div>
                <div class="modal-box-footer">
                    <button class="btn btn-secondary" @click="showPasswordModal = false">Annuler</button>
                    <button class="btn btn-primary" @click="submitPassword" :disabled="loadingExamId !== null">
                        <span v-if="loadingExamId !== null" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="fa fa-unlock me-1"></i> Accéder à l'examen
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.exam-header {
    background: linear-gradient(135deg, #0B5697 0%, #0a4a82 100%);
    border-radius: 12px;
    padding: 1.5rem 2rem;
    color: white;
}

.exam-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1.2rem;
}

.exam-card {
    border-radius: 12px;
    border: 2px solid #e0e0e0;
    overflow: hidden;
    background: white;
    display: flex;
    flex-direction: column;
    transition: all 0.3s;
    position: relative;
}

.exam-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.exam-card-available { border-color: #0B5697; }
.exam-card-in-progress { border-color: #ffc107; background: #fffdf0; }
.exam-card-done { border-color: #28a745; }
.exam-card-disabled { border-color: #dee2e6; opacity: 0.7; }

.exam-card-badge {
    position: absolute;
    top: 12px;
    right: 12px;
}

.exam-card-body {
    padding: 1.2rem;
    flex: 1;
}

.exam-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0B5697;
    margin-bottom: 0.8rem;
    padding-right: 80px;
}

.exam-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.8rem;
    margin-bottom: 0.6rem;
}

.exam-meta-item {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.85rem;
    color: #555;
}

.exam-meta-item i {
    color: #0FBCAF;
    width: 16px;
}

.exam-dates {
    color: #777;
    margin-bottom: 0.6rem;
}

.exam-bareme {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.bg-outline {
    background: transparent;
    border: 1px solid #6c757d;
    color: #6c757d;
}

.exam-result {
    display: flex;
    align-items: center;
}

.result-badge {
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-weight: 700;
}

.result-pass {
    background: #d4edda;
    color: #155724;
}

.result-fail {
    background: #f8d7da;
    color: #721c24;
}

.result-score { font-size: 1.3rem; }
.result-max { font-size: 0.9rem; opacity: 0.8; }

.exam-description {
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #666;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.exam-card-footer {
    padding: 0.8rem 1.2rem;
    border-top: 1px solid #f0f0f0;
    background: #fafafa;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-box {
    background: white;
    border-radius: 12px;
    width: 420px;
    max-width: 90%;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-box-header {
    background: #0B5697;
    color: white;
    padding: 1rem 1.5rem;
}

.modal-box-body { padding: 1.5rem; }

.modal-box-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}
</style>
