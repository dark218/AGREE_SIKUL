<!--
  NoteForm.vue — Refonte Phase 4.2 (Steppers).
  Historique : 565 lignes / 6 sections / 18 champs saisis → 2 steps / 3-4 champs saisis.

  Steps :
    1. Contexte  (apprenant, évaluation → tout le reste auto-fill via API évaluation +
                  cascade apprenant/classe).
    2. Résultat  (note_originale, note_sur, note normalisée auto, remarques, statut).

  Auto-fill préservé :
    - matiere_id → coefficient (API /matieres/{id}/api-show)
    - evaluation_id → date_examen (API /evaluations/{id}/api-show)
    - classe_id → ecole/campus/section/cycle/annee (useClasseCascade)
    - apprenant_id → classe (useApprenantCascade)
-->

<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FormStepper from '@/Components/Common/FormStepper.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useClasseCascade } from '@/Composables/useClasseCascade';
import { useApprenantCascade } from '@/Composables/useApprenantCascade';

const { t } = useI18n();

const props = defineProps({
    form:            { type: Object, required: true },
    apprenants:      { type: Array,  default: () => [] },
    evaluations:     { type: Array,  default: () => [] },
    anneesScolaires: { type: Array,  default: () => [] },
    sections:        { type: Array,  default: () => [] },
    cycles:          { type: Array,  default: () => [] },
    classes:         { type: Array,  default: () => [] },
    ecoles:          { type: Array,  default: () => [] },
    campuses:        { type: Array,  default: () => [] },
    periodes:        { type: Array,  default: () => [] },
    natureExamens:   { type: Array,  default: () => [] },
    typeExamens:     { type: Array,  default: () => [] },
    matieres:        { type: Array,  default: () => [] },
    groupes:         { type: Array,  default: () => [] },
    enseignants:     { type: Array,  default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const emit = defineEmits(['submit']);

const isReadOnly = props.mode === 'show';
const currentStep = ref(0);

// Cascades auto : classe → ecole/campus/section/cycle/année, apprenant → classe.
useClasseCascade(props.form, () => props.classes);
useApprenantCascade(props.form, () => props.apprenants);

// Auto-fill coefficient depuis matière (API).
const handleMatiereChange = async (id) => {
    if (!id) return;
    try {
        const r = await fetch(`/academique/matieres/${id}/api-show`);
        if (!r.ok) return;
        const d = await r.json();
        props.form.coefficient = d.coefficient || 0;
    } catch (e) { console.error('handleMatiereChange:', e); }
};

// Auto-fill date_examen depuis évaluation (API).
const handleEvaluationChange = async (id) => {
    if (!id) return;
    try {
        const r = await fetch(`/academique/evaluations/${id}/api-show`);
        if (!r.ok) return;
        const d = await r.json();
        props.form.date_examen = d.date || null;
    } catch (e) { console.error('handleEvaluationChange:', e); }
};

if (props.mode !== 'show') {
    watch(() => props.form.matiere_id,    (v) => v && handleMatiereChange(v));
    watch(() => props.form.evaluation_id, (v) => v && handleEvaluationChange(v));
}
onMounted(() => {
    if (props.form?.matiere_id)    handleMatiereChange(props.form.matiere_id);
    if (props.form?.evaluation_id) handleEvaluationChange(props.form.evaluation_id);
});

// Labels auto pour affichage read-only.
const autoLabel = (list, id, keyLibelle = 'libelle', keyNom = 'nom') => {
    if (!id || !list?.length) return '—';
    const f = list.find(x => String(x.id) === String(id));
    return f?.[keyLibelle] || f?.[keyNom] || '—';
};
const anneeScolaireLabel = computed(() => autoLabel(props.anneesScolaires, props.form.annee_scolaire_id));
const classeLabel        = computed(() => autoLabel(props.classes,        props.form.classe_id));
const sectionLabel       = computed(() => autoLabel(props.sections,       props.form.section_id));
const cycleLabel         = computed(() => autoLabel(props.cycles,         props.form.cycle_id));
const ecoleLabel         = computed(() => autoLabel(props.ecoles,         props.form.ecole_id));
const campusLabel        = computed(() => autoLabel(props.campuses,       props.form.campus_id));
const matiereLabel       = computed(() => autoLabel(props.matieres,       props.form.matiere_id));
const enseignantLabel    = computed(() => autoLabel(props.enseignants,    props.form.enseignant_id));

const noteNormalisee = computed(() =>
    props.form.note_originale && props.form.note_sur
        ? ((props.form.note_originale / props.form.note_sur) * 20).toFixed(2)
        : ''
);

const statutOptions = [
    { id: 'en_attente', libelle: 'En attente' },
    { id: 'validee',    libelle: 'Validée' },
    { id: 'rejetee',    libelle: 'Rejetée' },
    { id: 'suspendue',  libelle: 'Suspendue' },
];

const steps = [
    { key: 'contexte',  label: 'Contexte',  icon: 'fas fa-user-graduate', requiredFields: ['apprenant_id', 'evaluation_id'] },
    { key: 'resultat',  label: 'Résultat',  icon: 'fas fa-star',           requiredFields: ['note_originale', 'note_sur', 'statut'] },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="note-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : CONTEXTE (apprenant + évaluation → tout auto) -->
        <template #contexte>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Apprenant <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.apprenant_id"
                        :options="apprenants"
                        :disabled="isReadOnly || mode === 'edit'"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                    />
                    <span v-if="form.errors?.apprenant_id" class="text-danger small">{{ form.errors.apprenant_id }}</span>
                </div>
                <div class="col-md-6">
                    <label>Évaluation <span class="text-danger">*</span></label>
                    <SearchableSelect
                        :model-value="form.evaluation_id"
                        :options="evaluations"
                        :disabled="isReadOnly"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        @update:model-value="(v) => { form.evaluation_id = v; handleEvaluationChange(v); }"
                    />
                    <span v-if="form.errors?.evaluation_id" class="text-danger small">{{ form.errors.evaluation_id }}</span>
                </div>

                <!-- Contexte hérité de la classe (via cascade) -->
                <div class="col-12">
                    <InheritedContextBar
                        v-if="form.classe_id"
                        :source="classes?.find(c => String(c.id) === String(form.classe_id)) || null"
                        title="Contexte hérité de la classe"
                    />
                </div>

                <hr class="mt-3" />
                <div class="col-12">
                    <h6 class="text-muted"><i class="fa fa-magic me-1"></i> Champs auto-remplis</h6>
                    <small class="text-muted d-block mb-2">Ces champs sont dérivés de l'apprenant et de l'évaluation sélectionnés.</small>
                </div>
                <div class="col-md-4">
                    <label>Classe <span class="badge bg-secondary">auto</span></label>
                    <input :value="classeLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>Année scolaire <span class="badge bg-secondary">auto</span></label>
                    <input :value="anneeScolaireLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>Section <span class="badge bg-secondary">auto</span></label>
                    <input :value="sectionLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>Cycle <span class="badge bg-secondary">auto</span></label>
                    <input :value="cycleLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>École <span class="badge bg-secondary">auto</span></label>
                    <input :value="ecoleLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>Campus <span class="badge bg-secondary">auto</span></label>
                    <input :value="campusLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>Date d'examen <span class="badge bg-secondary">auto</span></label>
                    <input v-model="form.date_examen" type="date" class="form-control" :disabled="isReadOnly" />
                </div>

                <hr class="mt-3" />
                <div class="col-12">
                    <h6 class="text-muted"><i class="fa fa-pencil-alt me-1"></i> Précisions (optionnel)</h6>
                    <small class="text-muted d-block mb-2">Ajustez si nécessaire — sinon les valeurs déduites de l'évaluation sont utilisées.</small>
                </div>
                <div class="col-md-4">
                    <label>Période</label>
                    <SearchableSelect
                        v-model="form.periode_id"
                        :options="periodes"
                        :disabled="isReadOnly"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                    />
                </div>
                <div class="col-md-4">
                    <label>Nature d'examen</label>
                    <SearchableSelect
                        v-model="form.nature_examen_id"
                        :options="natureExamens"
                        :disabled="isReadOnly"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                    />
                </div>
                <div class="col-md-4">
                    <label>Type d'examen</label>
                    <SearchableSelect
                        v-model="form.type_examen_id"
                        :options="typeExamens"
                        :disabled="isReadOnly"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                    />
                </div>
                <div class="col-md-4">
                    <label>Matière</label>
                    <SearchableSelect
                        :model-value="form.matiere_id"
                        :options="matieres"
                        :disabled="isReadOnly"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                        @update:model-value="(v) => { form.matiere_id = v; handleMatiereChange(v); }"
                    />
                </div>
                <div class="col-md-4">
                    <label>Groupe</label>
                    <SearchableSelect
                        v-model="form.groupe_id"
                        :options="groupes"
                        :disabled="isReadOnly"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                    />
                </div>
                <div class="col-md-4">
                    <label>Enseignant</label>
                    <SearchableSelect
                        v-model="form.enseignant_id"
                        :options="enseignants"
                        :disabled="isReadOnly"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                    />
                </div>
            </div>
        </template>

        <!-- STEP 2 : RÉSULTAT (les seuls VRAIS champs à saisir) -->
        <template #resultat>
            <div class="row g-3">
                <div class="col-12">
                    <div class="alert alert-info d-flex align-items-center py-2">
                        <i class="fa fa-user-graduate me-2"></i>
                        <div class="small">
                            Note pour <strong>{{ apprenants.find(a => String(a.id) === String(form.apprenant_id))?.libelle || '—' }}</strong>
                            en <strong>{{ matiereLabel }}</strong>
                            (Enseignant : {{ enseignantLabel }})
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label>Note originale <span class="text-danger">*</span></label>
                    <input v-model.number="form.note_originale" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" placeholder="Ex: 15" />
                    <small class="text-muted">Note saisie par l'enseignant</small>
                    <span v-if="form.errors?.note_originale" class="text-danger small d-block">{{ form.errors.note_originale }}</span>
                </div>
                <div class="col-md-4">
                    <label>Note sur (échelle) <span class="text-danger">*</span></label>
                    <select v-model.number="form.note_sur" :disabled="isReadOnly" class="form-control">
                        <option value="">-- Sélectionner --</option>
                        <option :value="10">10 (Interrogation)</option>
                        <option :value="20">20 (Devoir, Contrôle)</option>
                        <option :value="100">100 (Examen)</option>
                        <option :value="5">5</option>
                        <option :value="50">50</option>
                        <option :value="200">200</option>
                    </select>
                    <span v-if="form.errors?.note_sur" class="text-danger small d-block">{{ form.errors.note_sur }}</span>
                </div>
                <div class="col-md-4">
                    <label>Note normalisée /20 <span class="badge bg-secondary">auto</span></label>
                    <input :value="noteNormalisee" type="text" class="form-control fw-bold text-primary" readonly disabled />
                    <small class="text-muted">Formule : (originale / sur) × 20</small>
                </div>

                <div class="col-12">
                    <label>Remarques</label>
                    <textarea v-model="form.remarques" :disabled="isReadOnly" class="form-control" rows="2" placeholder="Optionnel"></textarea>
                </div>

                <div class="col-md-6">
                    <label>Statut <span class="text-danger">*</span></label>
                    <select v-model="form.statut" :disabled="isReadOnly" class="form-control">
                        <option value="">-- Sélectionner --</option>
                        <option v-for="o in statutOptions" :key="o.id" :value="o.id">{{ o.libelle }}</option>
                    </select>
                    <span v-if="form.errors?.statut" class="text-danger small d-block">{{ form.errors.statut }}</span>
                </div>
            </div>
        </template>
    </FormStepper>
</template>

<style scoped>
.form-control {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.55rem 0.85rem;
    font-size: 0.95rem;
}
.form-control:focus {
    border-color: #0b5697;
    box-shadow: 0 0 0 0.2rem rgba(11, 86, 151, 0.15);
}
label {
    font-weight: 500;
    color: #374151;
    font-size: 0.9rem;
    margin-bottom: 0.4rem;
    display: block;
}
</style>
