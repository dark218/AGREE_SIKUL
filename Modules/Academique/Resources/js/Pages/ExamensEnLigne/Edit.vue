<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ExamenEnLigneForm from './ExamenEnLigneForm.vue';
import QuestionManager from './QuestionManager.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const activeTab = ref('infos');

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    item: Object,
    title: String,
    matieres: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    enseignants: {
        type: Array,
        default: () => [],
    },
    statuts: {
        type: Array,
        default: () => ['brouillon', 'publie', 'en_cours', 'termine', 'corrige'],
    },
});

// Helper to format datetime for datetime-local input
const formatDatetimeLocal = (dateString) => {
    if (!dateString) return '';
    if (dateString.includes('T')) {
        return dateString.substring(0, 16);
    }
    const parts = dateString.split(' ');
    if (parts.length === 2) {
        const timeParts = parts[1].split(':');
        return `${parts[0]}T${timeParts[0]}:${timeParts[1]}`;
    }
    return dateString;
};

const form = useForm({
    titre: props.item.titre || '',
    description: props.item.description || '',
    instructions: props.item.instructions || '',
    planification_examen_id: props.item.planification_examen_id || null,
    classe_id: props.item.classe_id || null,
    matiere_id: props.item.matiere_id || null,
    enseignant_id: props.item.enseignant_id || null,
    date_debut: formatDatetimeLocal(props.item.date_debut) || '',
    date_fin: formatDatetimeLocal(props.item.date_fin) || '',
    nombre_heures: props.item.nombre_heures || null,
    nombre_questions: props.item.nombre_questions || null,
    duree_minutes: props.item.duree_minutes || null,
    note_maximum: props.item.note_maximum || 20,
    note_minimum_passage: props.item.note_minimum_passage || 10,
    melange_questions: props.item.melange_questions || false,
    melange_reponses: props.item.melange_reponses || false,
    nombre_tentatives: props.item.nombre_tentatives || 1,
    afficher_resultat: props.item.afficher_resultat ?? true,
    afficher_correction: props.item.afficher_correction || false,
    retour_arriere: props.item.retour_arriere ?? true,
    mot_de_passe: props.item.mot_de_passe || '',
    etat: props.item.etat || 'brouillon',
});

const submitForm = () => {
    showUpdateLoader();
    form.put(route('academique.examens-en-ligne.update', props.item.id), {
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: () => {
            hideLoader();
        }
    });
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
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('exam.edit_title') || 'Modifier l\'examen en ligne' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />

                            <!-- Tabs -->
                            <ul class="nav nav-tabs mb-3">
                                <li class="nav-item">
                                    <button
                                        class="nav-link"
                                        :class="{ active: activeTab === 'infos' }"
                                        @click="activeTab = 'infos'"
                                    >
                                        <i class="fa fa-info-circle me-1"></i> Informations
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button
                                        class="nav-link"
                                        :class="{ active: activeTab === 'questions' }"
                                        @click="activeTab = 'questions'"
                                    >
                                        <i class="fa fa-question-circle me-1"></i> Questions
                                        <span v-if="item.questions?.length" class="badge bg-primary ms-1">
                                            {{ item.questions.length }}
                                        </span>
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab: Informations -->
                            <div v-show="activeTab === 'infos'">
                                <form @submit.prevent="submitForm">
                                    <ExamenEnLigneForm
                                        :form="form"
                                        :matieres="matieres"
                                        :classes="classes"
                                        :enseignants="enseignants"
                                        :statuts="statuts"
                                        mode="edit"
                                    />
                                    <div class="row mt-3">
                                        <div class="col">
                                            <div class="text-end">
                                                <Link :href="route('academique.examens-en-ligne.index')" class="btn btn-danger">
                                                    <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                                </Link>
                                                <button
                                                    type="submit"
                                                    class="btn btn-primary"
                                                    :disabled="form.processing"
                                                >
                                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                    <i class="fa fa-save"></i> {{ t('actions.validate') || 'Valider' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Tab: Questions -->
                            <div v-show="activeTab === 'questions'">
                                <QuestionManager
                                    :examen-id="item.id"
                                    :read-only="false"
                                />
                                <div class="row mt-3">
                                    <div class="col text-end">
                                        <Link :href="route('academique.examens-en-ligne.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>

<style scoped>
.nav-tabs .nav-link {
    color: #495057;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    color: #0B5697;
    border-bottom: 2px solid #0FBCAF;
    font-weight: 600;
}
</style>
