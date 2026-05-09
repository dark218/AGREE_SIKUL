<script setup>
import { useI18n } from 'vue-i18n';
import { watch, computed, onMounted, nextTick, ref } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';

const { t } = useI18n();

// DEBUG BOX
const debugLogs = ref([]);
const addDebugLog = (message, data = null) => {
    const timestamp = new Date().toLocaleTimeString();
    debugLogs.value.push({
        time: timestamp,
        message,
        data: data ? JSON.stringify(data) : ''
    });
    console.log(message, data);
};

// Override console.log for debugging
const originalLog = console.log;
console.log = function(...args) {
    originalLog.apply(console, args);
    if (args[0]?.toString().includes('[')) {
        addDebugLog(args[0]?.toString(), args[1]);
    }
};

// Define props FIRST before using them
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    classes: {
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
    ecoles: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    matieres: {
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

// Computed réactif pour masquer les champs hiérarchiques quand une classe est sélectionnée
const classeSelected = computed(() => !!props.form.classe_id);

// Labels calculés pour afficher les champs auto-remplis en lecture seule
const autoLabel = (list, id, fields = ['libelle', 'nom', 'label', 'name']) => {
    if (!id || !list?.length) return '—';
    const found = list.find(item => String(item.id) === String(id));
    if (!found) return '—';
    for (const f of fields) { if (found[f]) return found[f]; }
    return '—';
};
const sectionLabel = computed(() => autoLabel(props.sections, props.form.section_id));
const cycleLabel = computed(() => autoLabel(props.cycles, props.form.cycle_id));
const ecoleLabel = computed(() => autoLabel(props.ecoles, props.form.ecole_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

console.log('[EmploiTempsForm] Component loaded, isReadOnly:', isReadOnly);

// Handle classe selection to auto-fill dependent fields
const handleClasseChange = async (newClasseId) => {
    console.log('%c[handleClasseChange] CALLED!', 'color: red; font-size: 14px; font-weight: bold;', 'newClasseId:', newClasseId);

    if (!newClasseId) {
        console.log('[handleClasseChange] newClasseId is falsy, returning');
        return;
    }

    try {
        console.log('%c[Auto-fill] Fetching classe data for ID:', 'color: blue; font-weight: bold;', newClasseId);
        const response = await fetch(`/api/classes/${newClasseId}`);
        console.log('[Auto-fill] API Response status:', response.status, response.ok);

        if (!response.ok) {
            console.error('[Auto-fill] API error:', response.status);
            return;
        }
        const data = await response.json();
        console.log('%c[Auto-fill] Data received:', 'color: green; font-weight: bold;', data);

        // Auto-fill dependent fields
        console.log('[Auto-fill] Before assignment - form:', props.form);
        props.form.ecole_id = data.ecole_id || null;
        props.form.campus_id = data.campus_id || null;
        props.form.section_id = data.section_id || null;
        props.form.cycle_id = data.cycle_id || null;
        props.form.annee_scolaire_id = data.annee_scolaire_id || null;

        console.log('%c[Auto-fill] Form updated:', 'color: green; font-weight: bold;', {
            ecole_id: props.form.ecole_id,
            campus_id: props.form.campus_id,
            section_id: props.form.section_id,
            cycle_id: props.form.cycle_id,
            annee_scolaire_id: props.form.annee_scolaire_id
        });
    } catch (error) {
        console.error('%c[Auto-fill] ERROR CAUGHT:', 'color: red; font-weight: bold;', error);
    }
};

console.log('[EmploiTempsForm] handleClasseChange function defined:', typeof handleClasseChange);

// Call handleClasseChange on mount if classe_id already has a value
onMounted(async () => {
    console.log('[onMounted] Component mounted, classe_id:', props.form.classe_id);
    await nextTick();
    if (props.form.classe_id) {
        console.log('[onMounted] classe_id has value, calling handleClasseChange');
        await handleClasseChange(props.form.classe_id);
    }
});

// Format décimal hours to readable "XhYYm" format (e.g., 1.92 -> "1h55")
const formatDurationHM = (decimalHours) => {
    if (!decimalHours || decimalHours <= 0) return '0h0';
    const hours = Math.floor(decimalHours);
    const minutes = Math.round((decimalHours - hours) * 60);
    return `${hours}h${minutes}`;
};

// Calculate week end date and month from week_start_date
const weekInfo = computed(() => {
    if (!props.form.week_start_date) return { week_end_date: '', month: '', year: '' };

    try {
        const startDate = new Date(props.form.week_start_date);

        // Calculate end date (Saturday, 5 days after Monday)
        const endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + 5);
        const weekEndDate = endDate.toISOString().split('T')[0];

        // Get month in French
        const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        const month = months[startDate.getMonth()];

        // Get year
        const year = startDate.getFullYear();

        return {
            week_end_date: weekEndDate,
            month: month,
            year: year
        };
    } catch (e) {
        console.error('Erreur calcul semaine:', e);
        return { week_end_date: '', month: '', year: '' };
    }
});

// Auto-populate week_end_date when week_start_date changes
watch(
    () => props.form.week_start_date,
    () => {
        if (props.form.week_start_date) {
            const startDate = new Date(props.form.week_start_date);
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 5);
            props.form.week_end_date = endDate.toISOString().split('T')[0];
        }
    }
);

const statutOptions = [
    { id: 'brouillon', libelle: t('common.brouillon') || 'Brouillon' },
    { id: 'valide', libelle: t('common.valide') || 'Validé' },
    { id: 'publie', libelle: t('common.publie') || 'Publié' },
    { id: 'archive', libelle: t('common.archive') || 'Archivé' },
];

const joursOptions = [
    { id: 'lundi', libelle: 'Lundi' },
    { id: 'mardi', libelle: 'Mardi' },
    { id: 'mercredi', libelle: 'Mercredi' },
    { id: 'jeudi', libelle: 'Jeudi' },
    { id: 'vendredi', libelle: 'Vendredi' },
    { id: 'samedi', libelle: 'Samedi' },
];

// Auto-calculate duration when dates change (in hours)
watch(
    () => [props.form.date_debut, props.form.date_fin],
    () => {
        if (props.form.date_debut && props.form.date_fin) {
            try {
                const debut = new Date(props.form.date_debut);
                const fin = new Date(props.form.date_fin);
                const diffMs = fin - debut;
                const diffMinutes = Math.round(diffMs / (1000 * 60));
                const diffHeures = diffMinutes / 60;

                console.log(`[Duration] Début: ${props.form.date_debut}, Fin: ${props.form.date_fin}`);
                console.log(`[Duration] Minutes totales: ${diffMinutes}, Heures décimales: ${diffHeures}`);

                const diffHeuresRounded = Math.round(diffHeures * 4) / 4;

                if (diffHeuresRounded > 0) {
                    props.form.duree = diffHeuresRounded;
                    console.log(`[Duration] Durée finale: ${diffHeuresRounded} heures`);
                }
            } catch (e) {
                console.error('Erreur calcul durée:', e);
            }
        }
    },
    { deep: true }
);

// Watch removed - using event handler instead for better Inertia compatibility
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- ==================== SECTION 1: DÉFINITION DE LA SEMAINE ==================== -->
        <div class="col-12">
            <h5 class="section-title mb-3">{{ t('fields.week_definition') || 'Définition de la Semaine' }}</h5>
        </div>

        <!-- Nom de la Semaine -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.week_name') || 'Nom de la Semaine' }} <span class="text-danger">*</span></label>
                <input
                    type="text"
                    v-model="form.week_name"
                    class="form-control"
                    placeholder="Ex: Semaine 1, Semaine 2, etc."
                    :disabled="isReadOnly"
                >
                <small class="form-text text-muted">Exemple: Semaine 1, Semaine 2, Semaine du 1-7 janvier</small>
                <span v-if="form.errors?.week_name" class="text-danger">
                    <strong>{{ form.errors.week_name }}</strong>
                </span>
            </div>
        </div>

        <!-- Date Début de Semaine (Lundi) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.week_start_date') || 'Début de Semaine (Lundi)' }} <span class="text-danger">*</span></label>
                <input
                    type="date"
                    v-model="form.week_start_date"
                    class="form-control"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.week_start_date" class="text-danger">
                    <strong>{{ form.errors.week_start_date }}</strong>
                </span>
            </div>
        </div>

        <!-- Date Fin de Semaine (Samedi) - Auto-calculée -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.week_end_date') || 'Fin de Semaine (Samedi)' }} <small class="text-muted">(auto-calculée)</small></label>
                <input
                    type="date"
                    :value="weekInfo.week_end_date"
                    class="form-control"
                    disabled
                    style="cursor: not-allowed; background-color: #f0f0f0;"
                >
                <input type="hidden" v-model="form.week_end_date">
            </div>
        </div>

        <!-- Affichage de la Semaine (Mois et Année) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.week_info') || 'Informations Semaine' }}</label>
                <div class="alert alert-info mb-0">
                    <strong v-if="form.week_name && weekInfo.month">
                        {{ form.week_name }} (Semaine {{ weekInfo.month }} / {{ weekInfo.year }})
                    </strong>
                    <span v-else-if="form.week_name" class="text-muted">
                        {{ form.week_name }}
                    </span>
                    <span v-else class="text-muted">Entrez le nom de la semaine et sélectionnez une date</span>
                </div>
            </div>
        </div>

        <!-- ==================== SECTION 2: AFFECTATION SCOLAIRE ==================== -->
        <div class="col-12 mt-4">
            <h5 class="section-title mb-3">{{ t('fields.school_assignment') || 'Affectation Scolaire' }}</h5>
        </div>

        <!-- Année Scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_scolaire') || 'Année Scolaire' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.annee_scolaire_id"
                    :options="anneesScolaires"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.annee_scolaire_id" class="text-danger">
                    <strong>{{ form.errors.annee_scolaire_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Classe -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.classe') || 'Classe' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.classe_id"
                    :options="classes"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                    @update:modelValue="handleClasseChange"
                />
                <span v-if="form.errors?.classe_id" class="text-danger">
                    <strong>{{ form.errors.classe_id }}</strong>
                </span>
            </div>
        </div>

        <HierarchyContextBar v-if="classeSelected" :form="form" :ecoles="ecoles" :campuses="campuses" :sections="sections" :cycles="cycles" />

        <!-- Section (auto-rempli, lecture seule) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="sectionLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Cycle (auto-rempli, lecture seule) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.cycle') || 'Cycle' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="cycleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- École (auto-rempli, lecture seule) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ecole') || 'Ecole' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Campus (auto-rempli, lecture seule) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') || 'Campus' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- ==================== SECTION 3: CONTENU PÉDAGOGIQUE ==================== -->
        <div class="col-12 mt-4">
            <h5 class="section-title mb-3">{{ t('fields.content') || 'Contenu Pédagogique' }}</h5>
        </div>

        <!-- Matière -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.matiere') || 'Matière' }}</label>
                <SearchableSelect
                    v-model="form.matiere_id"
                    :options="matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere_id" class="text-danger">
                    <strong>{{ form.errors.matiere_id }}</strong>
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
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.enseignant_id" class="text-danger">
                    <strong>{{ form.errors.enseignant_id }}</strong>
                </span>
            </div>
        </div>

        <!-- ==================== SECTION 4: PLANIFICATION ==================== -->
        <div class="col-12 mt-4">
            <h5 class="section-title mb-3">{{ t('fields.planning') || 'Planification' }}</h5>
        </div>

        <!-- Jour -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.jour') || 'Jour' }}</label>
                <SearchableSelect
                    v-model="form.jour"
                    :options="joursOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.jour" class="text-danger">
                    <strong>{{ form.errors.jour }}</strong>
                </span>
            </div>
        </div>

        <!-- Durée (heures) - Auto-calculée -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.duree') || 'Durée' }} <small class="text-muted">(auto-calculée)</small></label>
                <input
                    type="text"
                    :value="formatDurationHM(form.duree)"
                    class="form-control"
                    placeholder="0h0"
                    disabled
                    style="cursor: not-allowed; background-color: #f0f0f0;"
                >
                <input type="hidden" v-model.number="form.duree">
                <span v-if="form.errors?.duree" class="text-danger">
                    <strong>{{ form.errors.duree }}</strong>
                </span>
            </div>
        </div>

        <!-- Date Début -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.debut') || 'Début' }} <span class="text-danger">*</span></label>
                <input
                    type="datetime-local"
                    v-model="form.date_debut"
                    class="form-control"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.date_debut" class="text-danger">
                    <strong>{{ form.errors.date_debut }}</strong>
                </span>
            </div>
        </div>

        <!-- Date Fin -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.fin') || 'Fin' }} <span class="text-danger">*</span></label>
                <input
                    type="datetime-local"
                    v-model="form.date_fin"
                    class="form-control"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.date_fin" class="text-danger">
                    <strong>{{ form.errors.date_fin }}</strong>
                </span>
            </div>
        </div>

        <!-- ==================== SECTION 5: VALIDATION ET STATUT ==================== -->
        <div class="col-12 mt-4">
            <h5 class="section-title mb-3">{{ t('fields.validation') || 'Validation' }}</h5>
        </div>

        <!-- Est Validé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.est_valide') || 'Est Validé' }}</label>
                <div class="form-check">
                    <input
                        type="checkbox"
                        v-model="form.est_valide"
                        class="form-check-input"
                        :disabled="isReadOnly"
                        id="estValide"
                    >
                    <label class="form-check-label" for="estValide">
                        {{ t('fields.validate_schedule') || 'Valider cet emploi du temps' }}
                    </label>
                </div>
                <span v-if="form.errors?.est_valide" class="text-danger">
                    <strong>{{ form.errors.est_valide }}</strong>
                </span>
            </div>
        </div>

        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }} <span class="text-danger">*</span></label>
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

        <!-- DEBUG BOX -->
        <div style="position: fixed; bottom: 20px; right: 20px; width: 400px; max-height: 300px; background: #1a1a1a; color: #0f0; border: 2px solid #0f0; border-radius: 5px; padding: 10px; overflow-y: auto; font-family: monospace; font-size: 11px; z-index: 9999; box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);">
            <div style="font-weight: bold; margin-bottom: 10px; color: #0f0;">🔧 DEBUG BOX</div>
            <div v-if="debugLogs.length === 0" style="color: #888;">En attente de logs...</div>
            <div v-for="(log, idx) in debugLogs.slice(-10)" :key="idx" style="margin-bottom: 5px; border-bottom: 1px solid #333; padding-bottom: 5px;">
                <div style="color: #0f0;">[{{ log.time }}] {{ log.message }}</div>
                <div v-if="log.data" style="color: #0a0; margin-top: 2px;">{{ log.data }}</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 0.5rem;
}
</style>
