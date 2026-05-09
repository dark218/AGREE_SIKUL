<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';

defineOptions({ layout: DashboardLayout });

const page = usePage();
const { t } = useI18n();
const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const { note, apprenants, evaluations, anneesScolaires, sections, cycles, classes, ecoles, campuses, periodes, natureExamens, typeExamens, matieres, groupes, enseignants } = defineProps({
    note: Object,
    apprenants: Array,
    evaluations: Array,
    anneesScolaires: Array,
    sections: Array,
    cycles: Array,
    classes: Array,
    ecoles: Array,
    campuses: Array,
    periodes: Array,
    natureExamens: Array,
    typeExamens: Array,
    matieres: Array,
    groupes: Array,
    enseignants: Array,
});

// Helper to get label from array (SAME AS DEVOIR)
const getLabel = (id, options) => {
    if (!id || !options) return '-';
    const item = options.find(o => o.id == id);
    return item ? (item.libelle || item.nom || item.titre || item.label || '-') : '-';
};

// Format date to YYYY-MM-DD
const formatDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
    } catch {
        return '-';
    }
};
</script>

<template>
    <div class="body-wrapper">
        <AlertMessage />
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-eye"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('common.details') || 'Détails' }} - {{ t('common.note') || 'Note' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>

                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <div class="row custom-input">
                                <!-- APPRENANT -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.apprenant') || 'Apprenant' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.apprenant_id, apprenants) }}</div>
                                </div>

                                <!-- EVALUATION -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.evaluation') || 'Évaluation' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.evaluation_id, evaluations) }}</div>
                                </div>

                                <!-- DATE EXAMEN -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('fields.date_examen') || 'Date Examen' }}</label>
                                    <div class="form-control" disabled>{{ formatDate(note.date_examen) }}</div>
                                </div>

                                <!-- MATIERE -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('fields.matiere') || 'Matière' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.matiere_id, matieres) }}</div>
                                </div>

                                <!-- ANNEE SCOLAIRE -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.annee_scolaire') || 'Année scolaire' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.annee_scolaire_id, anneesScolaires) }}</div>
                                </div>

                                <!-- SECTION -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.section') || 'Section' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.section_id, sections) }}</div>
                                </div>

                                <!-- CYCLE -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.cycle') || 'Cycle' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.cycle_id, cycles) }}</div>
                                </div>

                                <!-- CLASSE -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.classe') || 'Classe' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.classe_id, classes) }}</div>
                                </div>

                                <!-- ECOLE -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.ecole') || 'École' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.ecole_id, ecoles) }}</div>
                                </div>

                                <!-- CAMPUS -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.campus') || 'Campus' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.campus_id, campuses) }}</div>
                                </div>

                                <!-- PERIODE -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.periode') || 'Période' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.periode_id, periodes) }}</div>
                                </div>

                                <!-- NATURE EXAMEN -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.nature_examen') || 'Nature examen' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.nature_examen_id, natureExamens) }}</div>
                                </div>

                                <!-- TYPE EXAMEN -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.type_examen') || 'Type examen' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.type_examen_id, typeExamens) }}</div>
                                </div>

                                <!-- GROUPE -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.groupe') || 'Groupe' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.groupe_id, groupes) }}</div>
                                </div>

                                <!-- NOTE ORIGINALE -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('fields.note_originale') || 'Note Originale' }}</label>
                                    <div class="form-control" disabled>{{ note.note_originale || '-' }}</div>
                                </div>

                                <!-- NOTE SUR -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('fields.note_sur') || 'Note Sur' }}</label>
                                    <div class="form-control" disabled>{{ note.note_sur || '-' }}</div>
                                </div>

                                <!-- NOTE NORMALISEE -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('fields.note') || 'Note Normalisée (/20)' }}</label>
                                    <div class="form-control" disabled>{{ note.note || '-' }}</div>
                                </div>

                                <!-- ENSEIGNANT -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('common.enseignant') || 'Enseignant' }}</label>
                                    <div class="form-control" disabled>{{ getLabel(note.enseignant_id, enseignants) }}</div>
                                </div>

                                <!-- STATUT -->
                                <div class="col-sm-6 mb-3">
                                    <label>{{ t('fields.statut') || 'Statut' }}</label>
                                    <div class="form-control" disabled>{{ note.statut || '-' }}</div>
                                </div>

                                <!-- REMARQUES -->
                                <div class="col-sm-12 mb-3">
                                    <label>{{ t('fields.remarques') || 'Remarques' }}</label>
                                    <div class="form-control" disabled>{{ note.remarques || '-' }}</div>
                                </div>
                            </div>

                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.notes.index')" class="btn btn-danger">
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

