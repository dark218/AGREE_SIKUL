<script setup>
import { computed, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useClasseCascade } from '@/Composables/useClasseCascade';
const { t } = useI18n();
const props = defineProps({
    form: Object,
    matieres: Array,
    classes: Array,
    mode: {
        type: String,
        default: 'create',
    },
});
const isReadOnly = computed(() => props.mode === 'show');
const isEditMode = computed(() => props.mode === 'edit');

// Function to format datetime-local string to French date display
const formatDatetimeLocal = (dateStr) => {
    if (!dateStr) return 'N/A';
    try {
        console.log('🔧 Formatting date:', dateStr);

        // Parse format: '2026-03-24T00:00' -> Date object
        // Split by 'T' to separate date and time
        const [datePart, timePart] = dateStr.split('T');
        const [year, month, day] = datePart.split('-');
        const [hour, minute] = timePart.split(':');

        // Create date in local timezone (not UTC)
        const date = new Date(year, month - 1, day, hour, minute, 0);

        console.log('✅ Parsed date:', date, 'Formatted:', date.toLocaleString('fr-FR'));

        return date.toLocaleString('fr-FR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    } catch (error) {
        console.error('❌ Date formatting error:', error, dateStr);
        return dateStr || 'N/A';
    }
};

// DEBUG: Log form changes
console.log('🔍 DEVOIRFORM.VUE - Props received:', {
    form_date_debut: props.form?.date_debut,
    form_date_fin: props.form?.date_fin,
    mode: props.mode,
});

// Calculate nombre_heures from date_debut and date_fin
const calculateHeures = () => {
    if (props.form?.date_debut && props.form?.date_fin) {
        const start = new Date(props.form.date_debut);
        const end = new Date(props.form.date_fin);
        const diffMs = end - start;
        const diffHeures = diffMs / (1000 * 60 * 60);
        if (diffHeures > 0) {
            props.form.nombre_heures = Math.round(diffHeures * 100) / 100;
            console.log('📊 Heures calculées:', diffHeures, '-> arrondi:', props.form.nombre_heures);
        }
    }
};

// Watch for changes in dates
watch(() => props.form?.date_debut, (newVal) => {
    console.log('👀 DEVOIRFORM - date_debut changed:', newVal);
    calculateHeures();
}, { deep: true });

watch(() => props.form?.date_fin, (newVal) => {
    console.log('👀 DEVOIRFORM - date_fin changed:', newVal);
    calculateHeures();
}, { deep: true });

// Watch for changes in matiere_id to auto-fill coefficient
watch(() => props.form?.matiere_id, (newVal) => {
    console.log('👀 DEVOIRFORM - matiere_id changed:', newVal);
    handleMatiereChange(newVal);
}, { deep: true });

// Calculate on component mount
onMounted(() => {
    calculateHeures();
    // Auto-fill coefficient if matiere is already selected
    if (props.form?.matiere_id) {
        handleMatiereChange(props.form.matiere_id);
    }
});

// Auto-fill des FK dépendantes via composable (instantané, depuis la liste passée en prop)
// Hérite : ecole_id, campus_id, institution_id, niveau_id, section_id, cycle_id, annee_scolaire_id, pays_id
useClasseCascade(props.form, () => props.classes);

// Conserve le handler pour le @update:model-value (compat avec template existant)
const handleClasseChange = () => {
    // Le composable s'occupe de tout le reste automatiquement via le watch.
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
</script>
<template>
    <div class="form-content">
        <!-- Matière -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">{{ t('common.matiere') || 'Matière' }} <span class="text-danger">*</span></label>
                <div v-if="isReadOnly" class="form-control" disabled>
                    {{ matieres?.find(m => m.id === form.matiere_id)?.libelle || 'N/A' }}
                </div>
                <SearchableSelect
                    v-else
                    :model-value="form.matiere_id"
                    :options="matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :disabled="isEditMode"
                    @update:model-value="(val) => { form.matiere_id = val; handleMatiereChange(val); }"
                />
                <div v-if="form.errors?.matiere_id" class="text-danger small mt-1">
                    {{ form.errors.matiere_id[0] || form.errors.matiere_id }}
                </div>
            </div>
            <!-- Classe -->
            <div class="col-md-6">
                <label class="form-label">{{ t('common.classe') || 'Classe' }} <span class="text-danger">*</span></label>
                <div v-if="isReadOnly" class="form-control" disabled>
                    {{ classes?.find(c => c.id === form.classe_id)?.nom || 'N/A' }}
                </div>
                <SearchableSelect
                    v-else
                    :model-value="form.classe_id"
                    :options="classes"
                    optionValue="id"
                    optionLabel="nom"
                    :disabled="isEditMode"
                    @update:model-value="(val) => { form.classe_id = val; handleClasseChange(val); }"
                />
                <div v-if="form.errors?.classe_id" class="text-danger small mt-1">
                    {{ form.errors.classe_id[0] || form.errors.classe_id }}
                </div>
            </div>
        </div>
        <!-- Contexte hiérarchique hérité automatiquement de la Classe -->
        <div class="row mb-3">
            <InheritedContextBar
                :source="classes?.find(c => String(c.id) === String(form.classe_id)) || null"
                title="Hérité de la classe"
            />
        </div>

        <!-- Titre -->
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">{{ t('common.titre') || 'Titre' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.titre"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.titre }"
                    :disabled="isReadOnly"
                    placeholder="Titre du devoir"
                />
                <div v-if="form.errors?.titre" class="text-danger small mt-1">
                    {{ form.errors.titre[0] || form.errors.titre }}
                </div>
            </div>
        </div>
        <!-- Description -->
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">{{ t('common.description') || 'Description' }}</label>
                <textarea
                    v-model="form.description"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.description }"
                    :disabled="isReadOnly"
                    rows="3"
                    placeholder="Description du devoir"
                />
                <div v-if="form.errors?.description" class="text-danger small mt-1">
                    {{ form.errors.description[0] || form.errors.description }}
                </div>
            </div>
        </div>
        <!-- Date Début & Date Fin -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">{{ t('common.date_debut') || 'Date Début' }} <span class="text-danger">*</span></label>
                <div v-if="isReadOnly" class="form-control" disabled>
                    {{ formatDatetimeLocal(form.date_debut) }}
                </div>
                <input
                    v-else
                    v-model="form.date_debut"
                    type="datetime-local"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.date_debut }"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors?.date_debut" class="text-danger small mt-1">
                    {{ form.errors.date_debut[0] || form.errors.date_debut }}
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ t('common.date_fin') || 'Date Fin' }} <span class="text-danger">*</span></label>
                <div v-if="isReadOnly" class="form-control" disabled>
                    {{ formatDatetimeLocal(form.date_fin) }}
                </div>
                <input
                    v-else
                    v-model="form.date_fin"
                    type="datetime-local"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.date_fin }"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors?.date_fin" class="text-danger small mt-1">
                    {{ form.errors.date_fin[0] || form.errors.date_fin }}
                </div>
            </div>
        </div>
        <!-- Nombre d'heures (calculated) -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">{{ t('fields.nombre_heures') || 'Nombre d\'heures' }}</label>
                <input
                    :value="form.nombre_heures || 0"
                    type="number"
                    step="0.01"
                    class="form-control"
                    disabled
                />
                <small class="text-muted d-block mt-1">Calculé automatiquement</small>
            </div>
        </div>
        <!-- Coefficient -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">{{ t('common.coefficient') || 'Coefficient' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.coefficient"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.coefficient }"
                    :disabled="isReadOnly"
                    placeholder="0.00"
                />
                <div v-if="form.errors?.coefficient" class="text-danger small mt-1">
                    {{ form.errors.coefficient[0] || form.errors.coefficient }}
                </div>
            </div>
        </div>
        <!-- Statut -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">{{ t('common.statut') || 'Statut' }} <span class="text-danger">*</span></label>
                <select
                    v-model="form.statut"
                    class="form-select"
                    :class="{ 'is-invalid': form.errors?.statut }"
                    :disabled="isReadOnly"
                >
                    <option value="">-- Sélectionner --</option>
                    <option value="actif">Actif</option>
                    <option value="inactif">Inactif</option>
                </select>
                <div v-if="form.errors?.statut" class="text-danger small mt-1">
                    {{ form.errors.statut[0] || form.errors.statut }}
                </div>
            </div>
        </div>
    </div>
</template>
