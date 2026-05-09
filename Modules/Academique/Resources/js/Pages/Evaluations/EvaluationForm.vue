<script setup>
import { useI18n } from 'vue-i18n';
import { watch, onMounted, computed } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    classes: {
        type: Array,
        default: () => [],
    },
    matieres: {
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

// Handle date format for HTML5 date input (expects YYYY-MM-DD)
const evaluationDate = computed({
    get() {
        if (!props.form?.date) return '';
        const dateStr = String(props.form.date);
        // Extract YYYY-MM-DD from ISO format like "2026-03-23T00:00:00.000000Z"
        return dateStr.split('T')[0] || dateStr.substring(0, 10);
    },
    set(value) {
        if (props.form) {
            props.form.date = value || null;
        }
    }
});

/**
 * DEBUG: Log date format information
 */
const debugDateFormat = () => {
    console.group('📅 [DEBUG] Date Format Information');
    console.log('Form date value:', props.form.date);
    console.log('Date type:', typeof props.form.date);
    console.log('Date instanceof Date:', props.form.date instanceof Date);
    console.log('Raw date string:', String(props.form.date));

    // Try parsing as different formats
    if (props.form.date) {
        console.log('--- Parsed Formats ---');
        console.log('ISO format:', new Date(props.form.date).toISOString());
        console.log('Local format:', new Date(props.form.date).toLocaleString());
        console.log('Date object:', new Date(props.form.date));
    }
    console.groupEnd();
};

// Watch date changes and log format
watch(() => props.form.date, (newDate) => {
    console.log('🔍 [DEBUG] Date changed:', newDate);
    debugDateFormat();
}, { immediate: true });

// Watch matiere_id changes to auto-fill coefficient
watch(() => props.form.matiere_id, (newVal) => {
    if (newVal) {
        handleMatiereChange(newVal);
    }
});

// Log on mount
onMounted(() => {
    console.log('📱 [DEBUG] Evaluation Form Mounted - Mode:', props.mode);
    debugDateFormat();
    // Auto-fill coefficient if matiere is already selected
    if (props.form?.matiere_id) {
        handleMatiereChange(props.form.matiere_id);
    }
});

// Handle classe selection to auto-fill dependent fields
const handleClasseChange = async (newClasseId) => {
    if (!newClasseId) return;

    try {
        console.log('[Auto-fill] Fetching classe data for ID:', newClasseId);
        const response = await fetch(`/api/classes/${newClasseId}`);
        if (!response.ok) {
            console.error('[Auto-fill] API error:', response.status);
            return;
        }
        const data = await response.json();
        console.log('[Auto-fill] Data received:', data);

        // Auto-fill dependent fields
        props.form.ecole_id = data.ecole_id || null;
        props.form.campus_id = data.campus_id || null;
        props.form.section_id = data.section_id || null;
        props.form.cycle_id = data.cycle_id || null;
        props.form.annee_scolaire_id = data.annee_scolaire_id || null;

        console.log('[Auto-fill] Form updated:', {
            ecole_id: props.form.ecole_id,
            campus_id: props.form.campus_id,
            section_id: props.form.section_id,
            cycle_id: props.form.cycle_id,
            annee_scolaire_id: props.form.annee_scolaire_id
        });
    } catch (error) {
        console.error('[Auto-fill] Error:', error);
    }
};

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

const statutOptions = [
    { id: 'actif', libelle: t('common.actif') || 'Actif' },
    { id: 'inactif', libelle: t('common.inactif') || 'Inactif' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }}</label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code') || 'Code'" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Titre -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.titre') || 'Titre' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.titre" class="form-control" :placeholder="t('fields.titre') || 'Titre'" :disabled="isReadOnly">
                <span v-if="form.errors?.titre" class="text-danger">
                    <strong>{{ form.errors.titre }}</strong>
                </span>
            </div>
        </div>
        <!-- Type -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type') || 'Type' }}</label>
                <input type="text" v-model="form.type" class="form-control" :placeholder="t('fields.type') || 'Type'" :disabled="isReadOnly">
                <span v-if="form.errors?.type" class="text-danger">
                    <strong>{{ form.errors.type }}</strong>
                </span>
            </div>
        </div>
        <!-- Classe -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.classe') || 'Classe' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.classe_id"
                    @update:modelValue="handleClasseChange"
                    :options="classes"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.classe_id" class="text-danger">
                    <strong>{{ form.errors.classe_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Contexte hiérarchique (auto-rempli par la classe) -->
        <HierarchyContextBar :form="form" />

        <!-- Matière -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.matiere') || 'Matière' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    :model-value="form.matiere_id"
                    :options="matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                    @update:model-value="(val) => { form.matiere_id = val; handleMatiereChange(val); }"
                />
                <span v-if="form.errors?.matiere_id" class="text-danger">
                    <strong>{{ form.errors.matiere_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Date -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date') || 'Date' }}</label>
                <input type="date" v-model="evaluationDate" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date" class="text-danger">
                    <strong>{{ form.errors.date }}</strong>
                </span>
            </div>
        </div>
        <!-- Coefficient -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.coefficient') || 'Coefficient' }}</label>
                <input type="number" v-model.number="form.coefficient" class="form-control" :disabled="isReadOnly" step="0.01">
                <span v-if="form.errors?.coefficient" class="text-danger">
                    <strong>{{ form.errors.coefficient }}</strong>
                </span>
            </div>
        </div>
        <!-- Sur -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.sur') || 'Sur' }}</label>
                <input type="number" v-model.number="form.sur" class="form-control" :disabled="isReadOnly" step="0.01">
                <span v-if="form.errors?.sur" class="text-danger">
                    <strong>{{ form.errors.sur }}</strong>
                </span>
            </div>
        </div>
        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.statut"
                    :options="statutOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
