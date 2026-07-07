<!--
  EmploiTempsForm.vue — Refonte Phase 4.4 (Steppers).
  Historique : 575 lignes / 5 sections empilées + debug box → 4 steps guidés.

  Steps :
    1. Semaine     (nom, date début lundi → date fin auto samedi, mois/année auto)
    2. Affectation (année scolaire, classe → école/campus/section/cycle auto)
    3. Contenu     (matière, enseignant)
    4. Planning    (jour, date_debut, date_fin → durée auto, est_valide, statut)

  Debug box supprimée (bruit visuel + surcharge console).
-->

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import FormStepper from '@/Components/Common/FormStepper.vue';

const { t } = useI18n();

const props = defineProps({
    form:            { type: Object, required: true },
    classes:         { type: Array,  default: () => [] },
    sections:        { type: Array,  default: () => [] },
    cycles:          { type: Array,  default: () => [] },
    ecoles:          { type: Array,  default: () => [] },
    campuses:        { type: Array,  default: () => [] },
    anneesScolaires: { type: Array,  default: () => [] },
    matieres:        { type: Array,  default: () => [] },
    enseignants:     { type: Array,  default: () => [] },
    mode: { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
});

const emit = defineEmits(['submit']);

const isReadOnly = props.mode === 'show';
const currentStep = ref(0);

const classeSelected = computed(() => !!props.form.classe_id);

const autoLabel = (list, id, fields = ['libelle', 'nom', 'label', 'name']) => {
    if (!id || !list?.length) return '—';
    const f = list.find(x => String(x.id) === String(id));
    if (!f) return '—';
    for (const k of fields) if (f[k]) return f[k];
    return '—';
};
const sectionLabel = computed(() => autoLabel(props.sections, props.form.section_id));
const cycleLabel   = computed(() => autoLabel(props.cycles,   props.form.cycle_id));
const ecoleLabel   = computed(() => autoLabel(props.ecoles,   props.form.ecole_id));
const campusLabel  = computed(() => autoLabel(props.campuses, props.form.campus_id));

// Auto-fill depuis /api/classes/{id}.
const handleClasseChange = async (id) => {
    if (!id) return;
    try {
        const r = await fetch(`/api/classes/${id}`);
        if (!r.ok) return;
        const d = await r.json();
        props.form.ecole_id          = d.ecole_id          ?? null;
        props.form.campus_id         = d.campus_id         ?? null;
        props.form.section_id        = d.section_id        ?? null;
        props.form.cycle_id          = d.cycle_id          ?? null;
        props.form.annee_scolaire_id = d.annee_scolaire_id ?? null;
    } catch (e) { console.error('handleClasseChange:', e); }
};

onMounted(async () => {
    await nextTick();
    if (props.form.classe_id) await handleClasseChange(props.form.classe_id);
});

// Semaine : date fin = lundi + 5 jours (samedi).
const weekInfo = computed(() => {
    if (!props.form.week_start_date) return { week_end_date: '', month: '', year: '' };
    try {
        const s = new Date(props.form.week_start_date);
        const e = new Date(s);
        e.setDate(e.getDate() + 5);
        const months = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
        return { week_end_date: e.toISOString().split('T')[0], month: months[s.getMonth()], year: s.getFullYear() };
    } catch { return { week_end_date: '', month: '', year: '' }; }
});
watch(() => props.form.week_start_date, () => {
    if (!props.form.week_start_date) return;
    const s = new Date(props.form.week_start_date);
    const e = new Date(s); e.setDate(e.getDate() + 5);
    props.form.week_end_date = e.toISOString().split('T')[0];
});

// Durée auto (heures décimales) depuis date_debut/date_fin.
const formatDurationHM = (h) => {
    if (!h || h <= 0) return '0h0';
    const hh = Math.floor(h);
    const mm = Math.round((h - hh) * 60);
    return `${hh}h${mm}`;
};
watch(() => [props.form.date_debut, props.form.date_fin], () => {
    if (!props.form.date_debut || !props.form.date_fin) return;
    try {
        const diff = (new Date(props.form.date_fin) - new Date(props.form.date_debut)) / 3_600_000;
        const rounded = Math.round(diff * 4) / 4;
        if (rounded > 0) props.form.duree = rounded;
    } catch (e) { console.error('duree calc:', e); }
}, { deep: true });

const statutOptions = [
    { id: 'brouillon', libelle: 'Brouillon' },
    { id: 'valide',    libelle: 'Validé' },
    { id: 'publie',    libelle: 'Publié' },
    { id: 'archive',   libelle: 'Archivé' },
];
const joursOptions = [
    { id: 'lundi',    libelle: 'Lundi' },
    { id: 'mardi',    libelle: 'Mardi' },
    { id: 'mercredi', libelle: 'Mercredi' },
    { id: 'jeudi',    libelle: 'Jeudi' },
    { id: 'vendredi', libelle: 'Vendredi' },
    { id: 'samedi',   libelle: 'Samedi' },
];

const steps = [
    { key: 'semaine',     label: 'Semaine',      icon: 'fas fa-calendar-week',    requiredFields: ['week_name', 'week_start_date'] },
    { key: 'affectation', label: 'Affectation',  icon: 'fas fa-school',           requiredFields: ['annee_scolaire_id', 'classe_id'] },
    { key: 'contenu',     label: 'Contenu',      icon: 'fas fa-chalkboard' },
    { key: 'planning',    label: 'Planning',     icon: 'fas fa-clock',             requiredFields: ['date_debut', 'date_fin', 'statut'] },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="emploi-temps-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : SEMAINE -->
        <template #semaine>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Nom de la semaine <span class="text-danger">*</span></label>
                    <input v-model="form.week_name" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ex : Semaine 1, Semaine du 1-7 janvier" />
                    <small class="form-text text-muted">Exemple : Semaine 1, Semaine du 1-7 janvier</small>
                    <span v-if="form.errors?.week_name" class="text-danger small d-block">{{ form.errors.week_name }}</span>
                </div>
                <div class="col-md-6">
                    <label>Début de semaine (Lundi) <span class="text-danger">*</span></label>
                    <input v-model="form.week_start_date" :disabled="isReadOnly" type="date" class="form-control" />
                    <span v-if="form.errors?.week_start_date" class="text-danger small d-block">{{ form.errors.week_start_date }}</span>
                </div>
                <div class="col-md-6">
                    <label>Fin de semaine (Samedi) <span class="badge bg-secondary">auto</span></label>
                    <input :value="weekInfo.week_end_date" type="date" class="form-control" readonly disabled />
                    <input type="hidden" v-model="form.week_end_date" />
                </div>
                <div class="col-md-6">
                    <label>Informations semaine</label>
                    <div class="alert alert-info mb-0 py-2">
                        <strong v-if="form.week_name && weekInfo.month">
                            {{ form.week_name }} — {{ weekInfo.month }} / {{ weekInfo.year }}
                        </strong>
                        <span v-else-if="form.week_name" class="text-muted">{{ form.week_name }}</span>
                        <span v-else class="text-muted">Entrez le nom et la date de début</span>
                    </div>
                </div>
            </div>
        </template>

        <!-- STEP 2 : AFFECTATION -->
        <template #affectation>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Année scolaire <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.annee_scolaire_id"
                        :options="anneesScolaires"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.annee_scolaire_id" class="text-danger small">{{ form.errors.annee_scolaire_id }}</span>
                </div>
                <div class="col-md-6">
                    <label>Classe <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.classe_id"
                        :options="classes"
                        optionValue="id"
                        optionLabel="nom"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                        @update:modelValue="handleClasseChange"
                    />
                    <span v-if="form.errors?.classe_id" class="text-danger small">{{ form.errors.classe_id }}</span>
                </div>

                <div v-if="classeSelected" class="col-12">
                    <HierarchyContextBar :form="form" :ecoles="ecoles" :campuses="campuses" :sections="sections" :cycles="cycles" />
                </div>

                <div class="col-md-3">
                    <label>Section <span class="badge bg-secondary">auto</span></label>
                    <input :value="sectionLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-3">
                    <label>Cycle <span class="badge bg-secondary">auto</span></label>
                    <input :value="cycleLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-3">
                    <label>École <span class="badge bg-secondary">auto</span></label>
                    <input :value="ecoleLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-3">
                    <label>Campus <span class="badge bg-secondary">auto</span></label>
                    <input :value="campusLabel" type="text" class="form-control" readonly disabled />
                </div>
            </div>
        </template>

        <!-- STEP 3 : CONTENU -->
        <template #contenu>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Matière</label>
                    <SearchableSelect
                        v-model="form.matiere_id"
                        :options="matieres"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-6">
                    <label>Enseignant</label>
                    <SearchableSelect
                        v-model="form.enseignant_id"
                        :options="enseignants"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                </div>
            </div>
        </template>

        <!-- STEP 4 : PLANNING -->
        <template #planning>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Jour</label>
                    <SearchableSelect
                        v-model="form.jour"
                        :options="joursOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-6">
                    <label>Durée <span class="badge bg-secondary">auto</span></label>
                    <input :value="formatDurationHM(form.duree)" type="text" class="form-control" placeholder="0h0" readonly disabled />
                    <input type="hidden" v-model.number="form.duree" />
                </div>
                <div class="col-md-6">
                    <label>Début <span class="text-danger">*</span></label>
                    <input v-model="form.date_debut" :disabled="isReadOnly" type="datetime-local" class="form-control" />
                    <span v-if="form.errors?.date_debut" class="text-danger small d-block">{{ form.errors.date_debut }}</span>
                </div>
                <div class="col-md-6">
                    <label>Fin <span class="text-danger">*</span></label>
                    <input v-model="form.date_fin" :disabled="isReadOnly" type="datetime-local" class="form-control" />
                    <span v-if="form.errors?.date_fin" class="text-danger small d-block">{{ form.errors.date_fin }}</span>
                </div>

                <hr class="mt-3" />
                <div class="col-md-6">
                    <div class="form-check">
                        <input v-model="form.est_valide" :disabled="isReadOnly" type="checkbox" class="form-check-input" id="estValide" />
                        <label class="form-check-label" for="estValide">
                            <i class="fa fa-check-circle me-1"></i> Valider cet emploi du temps
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label>Statut <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.statut"
                        :options="statutOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
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
