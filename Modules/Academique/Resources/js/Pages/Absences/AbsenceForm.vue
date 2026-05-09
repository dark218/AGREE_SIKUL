<script setup>
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';

const { t } = useI18n();

// Format date_absence to Y-m-d format for input type="date"
onMounted(() => {
    if (props.form.date_absence) {
        // If it looks like ISO format (contains T or Z), extract just Y-m-d
        if (typeof props.form.date_absence === 'string' && (props.form.date_absence.includes('T') || props.form.date_absence.includes('Z'))) {
            const dateObj = new Date(props.form.date_absence);
            props.form.date_absence = dateObj.toISOString().split('T')[0];
            console.log('✅ Formatted date_absence:', props.form.date_absence);
        }
    }
});

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    apprenants: {
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
    sections: {
        type: Array,
        default: () => [],
    },
    cycles: {
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

const weekOptions = [
    { id: 1, libelle: 'Semaine 1' },
    { id: 2, libelle: 'Semaine 2' },
    { id: 3, libelle: 'Semaine 3' },
    { id: 4, libelle: 'Semaine 4' },
    { id: 5, libelle: 'Semaine 5' },
];

const dayOptions = [
    { id: 'lundi', libelle: 'Lundi' },
    { id: 'mardi', libelle: 'Mardi' },
    { id: 'mercredi', libelle: 'Mercredi' },
    { id: 'jeudi', libelle: 'Jeudi' },
    { id: 'vendredi', libelle: 'Vendredi' },
    { id: 'samedi', libelle: 'Samedi' },
    { id: 'dimanche', libelle: 'Dimanche' },
];

const statutOptions = [
    { id: 'en_attente', libelle: t('common.en_attente') || 'En attente' },
    { id: 'justifiee', libelle: t('common.justifiee') || 'Justifiée' },
    { id: 'non_justifiee', libelle: t('common.non_justifiee') || 'Non justifiée' },
    { id: 'partiellement_justifiee', libelle: t('common.partiellement_justifiee') || 'Partiellement justifiée' },
];

const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Calcul automatique du nombre d'heures
const nombreHeuresCalcule = computed(() => {
    if (!props.form.time_from || !props.form.time_to) return null;

    try {
        // Parse les heures au format HH:mm
        const [hFrom, mFrom] = props.form.time_from.split(':').map(Number);
        const [hTo, mTo] = props.form.time_to.split(':').map(Number);

        // Convertir en minutes
        const minutesFrom = hFrom * 60 + mFrom;
        const minutesTo = hTo * 60 + mTo;

        // Calculer la différence en minutes
        const diffMinutes = minutesTo - minutesFrom;

        if (diffMinutes <= 0) return null;

        // Convertir en heures décimales
        const heures = (diffMinutes / 60).toFixed(2);

        // Mettre à jour le formulaire automatiquement
        props.form.nombre_heures = parseFloat(heures);

        return heures;
    } catch (e) {
        return null;
    }
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Informations -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">{{ t('common.basic_information') || 'Informations' }}</h5>
        </div>

        <!-- Apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.apprenant') || 'Apprenant' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.apprenant_id"
                    :options="apprenants"
                    :disabled="isReadOnly || props.mode === 'edit'"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.apprenant_id" class="text-danger">
                    <strong>{{ form.errors.apprenant_id }}</strong>
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
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.classe_id" class="text-danger">
                    <strong>{{ form.errors.classe_id }}</strong>
                </span>
            </div>
        </div>

        <HierarchyContextBar v-if="classeSelected" :form="form" :ecoles="ecoles" :campuses="campuses" :sections="sections" :cycles="cycles" />

        <!-- Date d'absence -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_absence') || 'Date d\'absence' }} <span class="text-danger">*</span></label>
                <input type="date" v-model="form.date_absence" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_absence" class="text-danger">
                    <strong>{{ form.errors.date_absence }}</strong>
                </span>
            </div>
        </div>

        <!-- Semaine -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.week_number') || 'Semaine' }} <span class="text-danger">*</span></label>
                <select v-model.number="form.week_number" class="form-control" :disabled="isReadOnly">
                    <option value="">{{ t('actions.select') || '-- Sélectionner --' }}</option>
                    <option v-for="option in weekOptions" :key="option.id" :value="option.id">
                        {{ option.libelle }}
                    </option>
                </select>
                <span v-if="form.errors?.week_number" class="text-danger">
                    <strong>{{ form.errors.week_number }}</strong>
                </span>
            </div>
        </div>

        <!-- Jour de la semaine -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.day_of_week') || 'Jour de la semaine' }} <span class="text-danger">*</span></label>
                <select v-model="form.day_of_week" class="form-control" :disabled="isReadOnly">
                    <option value="">{{ t('actions.select') || '-- Sélectionner --' }}</option>
                    <option v-for="option in dayOptions" :key="option.id" :value="option.id">
                        {{ option.libelle }}
                    </option>
                </select>
                <span v-if="form.errors?.day_of_week" class="text-danger">
                    <strong>{{ form.errors.day_of_week }}</strong>
                </span>
            </div>
        </div>

        <!-- Heure de début -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.time_from') || 'Heure de début' }}</label>
                <input type="time" v-model="form.time_from" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.time_from" class="text-danger">
                    <strong>{{ form.errors.time_from }}</strong>
                </span>
            </div>
        </div>

        <!-- Heure de fin -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.time_to') || 'Heure de fin' }}</label>
                <input type="time" v-model="form.time_to" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.time_to" class="text-danger">
                    <strong>{{ form.errors.time_to }}</strong>
                </span>
            </div>
        </div>

        <!-- Nombre d'heures (calculé) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nombre_heures') || 'Nombre d\'heures' }}</label>
                <input
                    type="number"
                    v-model.number="form.nombre_heures"
                    class="form-control"
                    :placeholder="nombreHeuresCalcule || '0.00'"
                    step="0.25"
                    :disabled="isReadOnly"
                    readonly
                >
                <small class="text-muted" v-if="nombreHeuresCalcule">
                    Calculé: {{ nombreHeuresCalcule }} h
                </small>
                <span v-if="form.errors?.nombre_heures" class="text-danger">
                    <strong>{{ form.errors.nombre_heures }}</strong>
                </span>
            </div>
        </div>

        <!-- Motif -->
        <div class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.motif') || 'Motif' }}</label>
                <textarea v-model="form.motif" class="form-control" :placeholder="t('fields.motif')" :disabled="isReadOnly" rows="3"></textarea>
                <span v-if="form.errors?.motif" class="text-danger">
                    <strong>{{ form.errors.motif }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 2: Justification et État -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.status') || 'Justification et État' }}</h5>
        </div>

        <!-- État de justification -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>État de justification <span class="text-danger">*</span></label>
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

        <!-- État d'activation -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>État d'activation <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="etatOptions"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
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
