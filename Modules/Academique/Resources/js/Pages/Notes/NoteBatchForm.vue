<!--
  NoteBatchForm.vue — saisie EN LOT des notes (spec).
  Onglet « Contexte » : Classe (ancre → Année/Niveau/Section/Cycle/École/Campus/
     Institution AUTO), Période, Nature/Type d'examen, Matière, Groupe, Enseignant,
     Note maximale prévue. Choisir la classe charge ses apprenants.
  Onglet « Résultat » : tableau des apprenants × Note / Mention (auto) / Observation.
-->
<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import FormStepper from '@/Components/Common/FormStepper.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const props = defineProps({
    form:            { type: Object, required: true },
    classes:         { type: Array, default: () => [] },
    ecoles:          { type: Array, default: () => [] },
    campuses:        { type: Array, default: () => [] },
    institutions:    { type: Array, default: () => [] },
    sections:        { type: Array, default: () => [] },
    cycles:          { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    periodes:        { type: Array, default: () => [] },
    natureExamens:   { type: Array, default: () => [] },
    typeExamens:     { type: Array, default: () => [] },
    matieres:        { type: Array, default: () => [] },
    groupes:         { type: Array, default: () => [] },
    enseignants:     { type: Array, default: () => [] },
});
defineEmits(['submit']);

const currentStep = ref(0);
const loadingApprenants = ref(false);

const steps = [
    { key: 'contexte', label: 'Contexte',  icon: 'fas fa-sitemap',        requiredFields: ['classe_id', 'note_sur'] },
    { key: 'resultat', label: 'Résultat',  icon: 'fas fa-table-list' },
];

const label = (list, id) => {
    const f = list.find(x => String(x.id) === String(id));
    return f ? (f.libelle ?? f.nom) : '—';
};
const selectedClasse = computed(() => props.classes.find(c => String(c.id) === String(props.form.classe_id)));
const niveauLabel = computed(() => selectedClasse.value?.niveau_libelle || '—');
const ecoleLabel  = computed(() => label(props.ecoles,  props.form.ecole_id));
const campusLabel = computed(() => label(props.campuses, props.form.campus_id));
const sectionLabel = computed(() => label(props.sections, props.form.section_id));
const cycleLabel  = computed(() => label(props.cycles,  props.form.cycle_id));
const anneeLabel  = computed(() => label(props.anneesScolaires, props.form.annee_scolaire_id));
const institutionLabel = computed(() => {
    const campus = props.campuses.find(x => String(x.id) === String(props.form.campus_id));
    if (!campus?.institution_id) return '—';
    return label(props.institutions, campus.institution_id);
});

// Cascade : Classe → contexte + chargement des apprenants (tableau Résultat)
watch(() => props.form.classe_id, async (id) => {
    const c = props.classes.find(x => String(x.id) === String(id));
    if (!c) return;
    props.form.ecole_id          = c.ecole_id ?? null;
    props.form.campus_id         = c.campus_id ?? null;
    props.form.section_id        = c.section_id ?? null;
    props.form.cycle_id          = c.cycle_id ?? null;
    if (c.annee_scolaire_id) props.form.annee_scolaire_id = c.annee_scolaire_id;

    if (!id) { props.form.lignes = []; return; }
    loadingApprenants.value = true;
    try {
        const { data } = await axios.get(route('academique.notes.apprenants_classe', id));
        props.form.lignes = (data.apprenants || []).map(a => ({
            apprenant_id: a.id,
            nom: a.nom,
            matricule: a.matricule,
            note_originale: '',
            mention: '',
            observation: '',
        }));
    } catch (e) {
        props.form.lignes = [];
    } finally {
        loadingApprenants.value = false;
    }
});

// Mention automatique par ligne (dérivée de la note ramenée sur 20)
// Barème francophone à 9 niveaux (section francophone).
const mentionFor = (n20) =>
    n20 >= 16 ? 'Très Bien'
    : n20 >= 14 ? 'Bien'
    : n20 >= 12 ? 'Assez Bien'
    : n20 >= 10 ? 'Passable'
    : n20 >= 8 ? 'Médiocre'
    : n20 >= 6 ? 'Insuffisant'
    : n20 >= 4 ? 'Faible'
    : n20 >= 2 ? 'Très faible'
    : 'Nul';
const recomputeMention = (ligne) => {
    const sur = parseFloat(props.form.note_sur);
    const n = parseFloat(ligne.note_originale);
    if (!isNaN(n) && sur > 0) {
        ligne.mention = mentionFor((n / sur) * 20);
    } else {
        ligne.mention = '';
    }
};

if (!Array.isArray(props.form.lignes)) props.form.lignes = [];
</script>

<template>
    <FormStepper v-model="currentStep" :steps="steps" :form="form" persist-key="note-batch-form" @submit="$emit('submit')">
        <!-- ONGLET CONTEXTE -->
        <template #contexte>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="fw-medium">Année scolaire</label>
                    <SearchableSelect v-model="form.annee_scolaire_id" :options="anneesScolaires" option-value="id" option-label="libelle" placeholder="--" />
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Classe <span class="text-danger">*</span></label>
                    <SearchableSelect v-model="form.classe_id" :options="classes" option-value="id" option-label="libelle" placeholder="-- Choisir la classe --" />
                    <small class="text-muted">Charge les apprenants et remplit le contexte.</small>
                    <span v-if="form.errors?.classe_id" class="text-danger small d-block">{{ form.errors.classe_id }}</span>
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Période</label>
                    <SearchableSelect v-model="form.periode_id" :options="periodes" option-value="id" option-label="libelle" placeholder="--" />
                </div>
                <!-- École / Campus sortis du bloc auto (une institution peut avoir 2 écoles dans 2 campus). -->
                <div class="col-md-4">
                    <label class="fw-medium">École</label>
                    <SearchableSelect v-model="form.ecole_id" :options="ecoles" option-value="id" option-label="libelle" placeholder="--" />
                    <small class="text-muted">Pré-rempli par la classe, modifiable.</small>
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Campus</label>
                    <SearchableSelect v-model="form.campus_id" :options="campuses" option-value="id" option-label="libelle" placeholder="--" />
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Date d'examen</label>
                    <input v-model="form.date_examen" type="date" class="form-control" />
                </div>

                <div class="col-12">
                    <div class="auto-block">
                        <div class="auto-title"><i class="fa fa-sitemap"></i> Contexte académique <span class="badge bg-secondary">auto</span></div>
                        <div class="row g-2">
                            <div class="col-md-3"><span class="lbl">Institution</span><span class="val">{{ institutionLabel }}</span></div>
                            <div class="col-md-3"><span class="lbl">Cycle</span><span class="val">{{ cycleLabel }}</span></div>
                            <div class="col-md-3"><span class="lbl">Niveau</span><span class="val">{{ niveauLabel }}</span></div>
                            <div class="col-md-3"><span class="lbl">Section</span><span class="val">{{ sectionLabel }}</span></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="fw-medium">Nature d'examen</label>
                    <SearchableSelect v-model="form.nature_examen_id" :options="natureExamens" option-value="id" option-label="libelle" placeholder="--" />
                </div>
                <div class="col-md-3">
                    <label class="fw-medium">Type d'examen</label>
                    <SearchableSelect v-model="form.type_examen_id" :options="typeExamens" option-value="id" option-label="libelle" placeholder="--" />
                </div>
                <div class="col-md-3">
                    <label class="fw-medium">Matière</label>
                    <SearchableSelect v-model="form.matiere_id" :options="matieres" option-value="id" option-label="libelle" placeholder="--" />
                </div>
                <div class="col-md-3">
                    <label class="fw-medium">Groupe de matière</label>
                    <SearchableSelect v-model="form.groupe_id" :options="groupes" option-value="id" option-label="libelle" placeholder="--" />
                </div>

                <div class="col-md-4">
                    <label class="fw-medium">Enseignant</label>
                    <SearchableSelect v-model="form.enseignant_id" :options="enseignants" option-value="id" option-label="libelle" placeholder="--" />
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Note maximale prévue <span class="text-danger">*</span></label>
                    <input v-model.number="form.note_sur" type="number" min="0.01" step="0.01" class="form-control" placeholder="Ex : 20" />
                    <span v-if="form.errors?.note_sur" class="text-danger small d-block">{{ form.errors.note_sur }}</span>
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Note normalisée</label>
                    <input value="/ 20 (automatique)" type="text" class="form-control" readonly disabled />
                </div>

                <div class="col-md-6">
                    <label class="fw-medium">Titre / Libellé</label>
                    <input v-model="form.titre" type="text" class="form-control" maxlength="125" placeholder="Ex : Composition Maths — Trimestre 1" />
                </div>
            </div>
        </template>

        <!-- ONGLET RÉSULTAT -->
        <template #resultat>
            <div v-if="form.errors?.lignes" class="alert alert-danger">{{ form.errors.lignes }}</div>
            <div v-if="loadingApprenants" class="text-muted"><i class="fa fa-spinner fa-spin"></i> Chargement des apprenants…</div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Apprenant</th>
                            <th style="width:140px">Note (/ {{ form.note_sur || '?' }})</th>
                            <th style="width:160px">Mention</th>
                            <th>Observation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(ligne, i) in form.lignes" :key="ligne.apprenant_id">
                            <td>{{ ligne.nom }} <small class="text-muted">{{ ligne.matricule }}</small></td>
                            <td><input v-model="ligne.note_originale" type="number" min="0" step="0.01" class="form-control" @input="recomputeMention(ligne)" /></td>
                            <td><input v-model="ligne.mention" type="text" class="form-control" /></td>
                            <td><input v-model="ligne.observation" type="text" class="form-control" /></td>
                        </tr>
                        <tr v-if="!form.lignes.length">
                            <td colspan="4" class="text-center text-muted">Choisissez d'abord une classe (onglet Contexte).</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </FormStepper>
</template>

<style scoped>
label.fw-medium { font-weight: 500; color: #374151; font-size: .9rem; margin-bottom: .35rem; display: block; }
.auto-block { background:#f8fafc; border:1px solid #e9eef5; border-radius:10px; padding:14px 16px; }
.auto-title { font-weight:600; color:#0B5697; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
.auto-block .lbl { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.4px; color:#94a3b8; }
.auto-block .val { display:block; font-weight:600; color:#1e293b; }
</style>
