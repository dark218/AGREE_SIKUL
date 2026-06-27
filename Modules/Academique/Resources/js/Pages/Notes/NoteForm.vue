<script setup>
import { computed, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useClasseCascade } from '@/Composables/useClasseCascade';
import { useApprenantCascade } from '@/Composables/useApprenantCascade';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    apprenants: {
        type: Array,
        default: () => [],
    },
    evaluations: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    periodes: {
        type: Array,
        default: () => [],
    },
    natureExamens: {
        type: Array,
        default: () => [],
    },
    typeExamens: {
        type: Array,
        default: () => [],
    },
    matieres: {
        type: Array,
        default: () => [],
    },
    groupes: {
        type: Array,
        default: () => [],
    },
    enseignants: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = props.mode === 'show';
const classeSelected = computed(() => !!props.form.classe_id);

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '—';
};
const sectionLabel = computed(() => autoLabel(props.sections, props.form.section_id));
const cycleLabel = computed(() => autoLabel(props.cycles, props.form.cycle_id));
const ecoleLabel = computed(() => autoLabel(props.ecoles, props.form.ecole_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

// COMPREHENSIVE DEBUGGING
console.log('🔵 [NoteForm] Component initialized');
console.log('📋 [NoteForm] Props:', {
    formKeys: Object.keys(props.form),
    formValues: props.form,
    mode: props.mode,
    isReadOnly: isReadOnly,
    apparenantsCoun: props.apprenants.length,
    evaluationsCount: props.evaluations.length,
    matiuresCount: props.matieres.length,
});

watch(() => props.form, (newForm) => {
    console.log('🔄 [NoteForm] Form data changed:', newForm);
}, { deep: true });

// Cascade auto via composables (instantané, depuis listes en props)
useClasseCascade(props.form, () => props.classes);
useApprenantCascade(props.form, () => props.apprenants);

const handleClasseChange = () => { /* composable gère tout via watch */ };

const statutOptions = [
    { id: 'en_attente', libelle: t('common.en_attente') || 'En attente' },
    { id: 'validee', libelle: t('common.validee') || 'Validée' },
    { id: 'rejetee', libelle: t('common.rejetee') || 'Rejetée' },
    { id: 'suspendue', libelle: t('common.suspendue') || 'Suspendue' },
];

const handleApprenantChange = () => { /* composable useApprenantCascade gère tout via watch */ };

// Handle matiere selection to auto-fill coefficient
const handleMatiereChange = async (newMatiereId) => {
    if (!newMatiereId) return;

    try {
        console.log('[Auto-fill Matiere] Fetching matiere data for ID:', newMatiereId);
        const response = await fetch(`/academique/matieres/${newMatiereId}/api-show`);
        if (!response.ok) {
            console.error('[Auto-fill Matiere] API error:', response.status);
            return;
        }
        const data = await response.json();
        console.log('[Auto-fill Matiere] Data received:', data);

        // Auto-fill coefficient from matiere
        props.form.coefficient = data.coefficient || 0;

        console.log('[Auto-fill Matiere] Form updated:', {
            coefficient: props.form.coefficient
        });
    } catch (error) {
        console.error('[Auto-fill Matiere] Error:', error);
    }
};

// Handle evaluation selection to auto-fill date
const handleEvaluationChange = async (newEvaluationId) => {
    if (!newEvaluationId) return;

    try {
        console.log('[Auto-fill Evaluation] Fetching evaluation data for ID:', newEvaluationId);
        const response = await fetch(`/academique/evaluations/${newEvaluationId}/api-show`);
        if (!response.ok) {
            console.error('[Auto-fill Evaluation] API error:', response.status);
            return;
        }
        const data = await response.json();
        console.log('[Auto-fill Evaluation] Data received:', data);

        // Auto-fill date_examen from evaluation date
        props.form.date_examen = data.date || null;

        console.log('[Auto-fill Evaluation] Form updated:', {
            date_examen: props.form.date_examen
        });
    } catch (error) {
        console.error('[Auto-fill Evaluation] Error:', error);
    }
};

// Watch classe_id changes in create/edit mode
if (props.mode !== 'show') {
    watch(() => props.form.classe_id, (newVal) => {
        if (newVal) {
            handleClasseChange(newVal);
        }
    });

    // Watch apprenant_id changes in create mode only
    if (props.mode === 'create') {
        watch(() => props.form.apprenant_id, (newVal) => {
            if (newVal) {
                handleApprenantChange(newVal);
            }
        });
    }

    // Watch matiere_id changes to auto-fill coefficient
    watch(() => props.form.matiere_id, (newVal) => {
        if (newVal) {
            handleMatiereChange(newVal);
        }
    });

    // Watch evaluation_id changes to auto-fill date
    watch(() => props.form.evaluation_id, (newVal) => {
        if (newVal) {
            handleEvaluationChange(newVal);
        }
    });
}

// Auto-fill on mount if values are already selected
onMounted(() => {
    if (props.form?.matiere_id) {
        handleMatiereChange(props.form.matiere_id);
    }
    if (props.form?.evaluation_id) {
        handleEvaluationChange(props.form.evaluation_id);
    }
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Informations de base -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">{{ t('common.basic_information') || 'Informations de base' }}</h5>
        </div>

        <!-- Apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.apprenant') || 'Apprenant' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.apprenant_id"
                    :options="apprenants"
                    :disabled="isReadOnly || props.mode === 'edit'"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.apprenant_id" class="text-danger">
                    <strong>{{ form.errors.apprenant_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Évaluation -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.evaluation') || 'Évaluation' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    :model-value="form.evaluation_id"
                    :options="evaluations"
                    :disabled="isReadOnly"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    @update:model-value="(val) => { form.evaluation_id = val; handleEvaluationChange(val); }"
                />
                <span v-if="form.errors?.evaluation_id" class="text-danger">
                    <strong>{{ form.errors.evaluation_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Date Examen -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_examen') || 'Date Examen' }}</label>
                <input type="date" v-model="form.date_examen" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_examen" class="text-danger">
                    <strong>{{ form.errors.date_examen }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 2: Affectation scolaire -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.academic_assignment') || 'Affectation scolaire' }}</h5>
        </div>

        <!-- Année scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_scolaire') || 'Année scolaire' }}</label>
                <SearchableSelect
                    v-model="form.annee_scolaire_id"
                    :options="anneesScolaires"
                    :disabled="isReadOnly"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.annee_scolaire_id" class="text-danger">
                    <strong>{{ form.errors.annee_scolaire_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Section -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="sectionLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Cycle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.cycle') || 'Cycle' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="cycleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Classe -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.classe') || 'Classe' }}</label>
                <SearchableSelect
                    v-model="form.classe_id"
                    @update:modelValue="handleClasseChange"
                    :options="classes"
                    :disabled="isReadOnly"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.classe_id" class="text-danger">
                    <strong>{{ form.errors.classe_id }}</strong>
                </span>
            </div>
        </div>

        <InheritedContextBar
            v-if="classeSelected"
            :source="classes?.find(c => String(c.id) === String(form.classe_id)) || null"
            title="Hérité de la classe"
        />

        <!-- École -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ecole') || 'École' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Campus -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') || 'Campus' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Section 3: Examen -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.exam') || 'Examen' }}</h5>
        </div>

        <!-- Période -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.periode') || 'Période' }}</label>
                <SearchableSelect
                    v-model="form.periode_id"
                    :options="periodes"
                    :disabled="isReadOnly"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.periode_id" class="text-danger">
                    <strong>{{ form.errors.periode_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Nature Examen -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nature_examen') || 'Nature d\'examen' }}</label>
                <SearchableSelect
                    v-model="form.nature_examen_id"
                    :options="natureExamens"
                    :disabled="isReadOnly"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.nature_examen_id" class="text-danger">
                    <strong>{{ form.errors.nature_examen_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Type Examen -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type_examen') || 'Type d\'examen' }}</label>
                <SearchableSelect
                    v-model="form.type_examen_id"
                    :options="typeExamens"
                    :disabled="isReadOnly"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.type_examen_id" class="text-danger">
                    <strong>{{ form.errors.type_examen_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 4: Matière -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.subject') || 'Matière' }}</h5>
        </div>

        <!-- Matière -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.matiere') || 'Matière' }}</label>
                <SearchableSelect
                    :model-value="form.matiere_id"
                    :options="matieres"
                    :disabled="isReadOnly"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    @update:model-value="(val) => { form.matiere_id = val; handleMatiereChange(val); }"
                />
                <span v-if="form.errors?.matiere_id" class="text-danger">
                    <strong>{{ form.errors.matiere_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Groupe -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.groupe') || 'Groupe' }}</label>
                <SearchableSelect
                    v-model="form.groupe_id"
                    :options="groupes"
                    :disabled="isReadOnly"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.groupe_id" class="text-danger">
                    <strong>{{ form.errors.groupe_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Enseignant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.enseignant') || 'Enseignant' }}</label>
                <SearchableSelect
                    v-model="form.enseignant_id"
                    :options="enseignants"
                    :disabled="isReadOnly"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.enseignant_id" class="text-danger">
                    <strong>{{ form.errors.enseignant_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 5: Résultats -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.results') || 'Résultats' }}</h5>
        </div>

        <!-- Note Originale (saisie par l'enseignant) -->
        <div class="col-sm-3">
            <div class="mb-3">
                <label>Note Originale <span class="text-danger">*</span></label>
                <input type="number" v-model.number="form.note_originale" class="form-control" placeholder="Ex: 8, 15, 72..." :disabled="isReadOnly" min="0" step="0.01">
                <small class="text-muted d-block mt-1">La note saisie (interrogation, devoir, etc.)</small>
                <span v-if="form.errors?.note_originale" class="text-danger">
                    <strong>{{ form.errors.note_originale }}</strong>
                </span>
            </div>
        </div>

        <!-- Note Sur (échelle) -->
        <div class="col-sm-3">
            <div class="mb-3">
                <label>Note Sur <span class="text-danger">*</span></label>
                <select v-model.number="form.note_sur" class="form-control" :disabled="isReadOnly">
                    <option value="">-- Sélectionner --</option>
                    <option :value="10">10 (Interrogation)</option>
                    <option :value="20">20 (Devoir, Contrôle)</option>
                    <option :value="100">100 (Examen)</option>
                    <option :value="0" disabled>---</option>
                    <option :value="5">5</option>
                    <option :value="50">50</option>
                    <option :value="200">200</option>
                </select>
                <small class="text-muted d-block mt-1">Échelle de la note</small>
                <span v-if="form.errors?.note_sur" class="text-danger">
                    <strong>{{ form.errors.note_sur }}</strong>
                </span>
            </div>
        </div>

        <!-- Note Normalisée (calculée automatiquement à /20) -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>Note Normalisée (auto)</label>
                <input type="number"
                    :value="form.note_originale && form.note_sur ? ((form.note_originale / form.note_sur) * 20).toFixed(2) : ''"
                    class="form-control"
                    disabled
                    placeholder="Calculée">
                <small class="text-muted d-block mt-1">Formule: (original / sur) × 20</small>
            </div>
        </div>

        <!-- Remarques -->
        <div class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.remarques') || 'Remarques' }}</label>
                <textarea v-model="form.remarques" class="form-control" :placeholder="t('fields.remarques')" :disabled="isReadOnly" rows="3"></textarea>
                <span v-if="form.errors?.remarques" class="text-danger">
                    <strong>{{ form.errors.remarques }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 6: Statut (last) -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.status') || 'Statut' }}</h5>
        </div>

        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }} <span class="text-danger">*</span></label>
                <select v-model="form.statut" class="form-control" :disabled="isReadOnly">
                    <option value="">{{ t('actions.select') || '-- Sélectionner --' }}</option>
                    <option v-for="option in statutOptions" :key="option.id" :value="option.id">
                        {{ option.libelle }}
                    </option>
                </select>
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.section-title {
    font-weight: 600;
    color: #333;
    font-size: 1.1rem;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 0.5rem;
}
</style>
