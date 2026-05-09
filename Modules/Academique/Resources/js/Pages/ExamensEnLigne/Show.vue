<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ExamenEnLigneForm from './ExamenEnLigneForm.vue';
import QuestionManager from './QuestionManager.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const activeTab = ref('infos');

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    item: Object,
    title: String,
    matieres: Array,
    classes: Array,
    enseignants: Array,
    statuts: {
        type: Array,
        default: () => ['brouillon', 'publie', 'en_cours', 'termine', 'corrige'],
    },
});

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
    titre: page.props.item?.titre || '',
    description: page.props.item?.description || '',
    instructions: page.props.item?.instructions || '',
    planification_examen_id: page.props.item?.planification_examen_id || null,
    classe_id: page.props.item?.classe_id || null,
    matiere_id: page.props.item?.matiere_id || null,
    enseignant_id: page.props.item?.enseignant_id || null,
    date_debut: formatDatetimeLocal(page.props.item?.date_debut) || '',
    date_fin: formatDatetimeLocal(page.props.item?.date_fin) || '',
    nombre_heures: page.props.item?.nombre_heures || 0,
    nombre_questions: page.props.item?.nombre_questions || null,
    duree_minutes: page.props.item?.duree_minutes || null,
    note_maximum: page.props.item?.note_maximum || null,
    note_minimum_passage: page.props.item?.note_minimum_passage || null,
    melange_questions: page.props.item?.melange_questions || false,
    melange_reponses: page.props.item?.melange_reponses || false,
    nombre_tentatives: page.props.item?.nombre_tentatives || 1,
    afficher_resultat: page.props.item?.afficher_resultat ?? true,
    afficher_correction: page.props.item?.afficher_correction || false,
    retour_arriere: page.props.item?.retour_arriere ?? true,
    mot_de_passe: page.props.item?.mot_de_passe || '',
    etat: page.props.item?.etat || 'brouillon',
});
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
                                <h5 class="title mb-0">{{ t('exam.show_title') || 'Détails de l\'examen' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <!-- Tabs -->
                            <ul class="nav nav-tabs mb-3">
                                <li class="nav-item">
                                    <button class="nav-link" :class="{ active: activeTab === 'infos' }" @click="activeTab = 'infos'">
                                        <i class="fa fa-info-circle me-1"></i> Informations
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" :class="{ active: activeTab === 'questions' }" @click="activeTab = 'questions'">
                                        <i class="fa fa-question-circle me-1"></i> Questions
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab Infos -->
                            <div v-show="activeTab === 'infos'">
                                <ExamenEnLigneForm
                                    :form="form"
                                    :matieres="matieres"
                                    :classes="classes"
                                    :enseignants="enseignants"
                                    :statuts="statuts"
                                    mode="show"
                                />
                            </div>

                            <!-- Tab Questions (lecture seule) -->
                            <div v-show="activeTab === 'questions'">
                                <QuestionManager
                                    :examen-id="props.item.id"
                                    :read-only="true"
                                />
                            </div>

                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.examens-en-ligne.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                        </Link>
                                        <Link :href="route('academique.examens-en-ligne.edit', props.item.id)" class="btn btn-primary">
                                            <i class="fa fa-edit"></i> {{ t('actions.edit') || 'Modifier' }}
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
