<!--
  EmploiTempsForm.vue — refonte "propre" (cadre + créneaux).
  Étape 1 « Définition des périodes » : on choisit la CLASSE → École/Campus/
     Institution/Cycle/Niveau/Section/Année remontent AUTOMATIQUEMENT (lecture
     seule). Puis Période, Libellé, Dates, Durée (auto), Statut.
  Étape 2 « Créneaux » : grille répétable jour × heure → Matière + Enseignant + Salle.
-->
<script setup>
import { ref, computed, watch } from 'vue';
import FormStepper from '@/Components/Common/FormStepper.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const props = defineProps({
    form:            { type: Object, required: true },
    mode:            { type: String, default: 'create' },
    classes:         { type: Array, default: () => [] },
    ecoles:          { type: Array, default: () => [] },
    campuses:        { type: Array, default: () => [] },
    institutions:    { type: Array, default: () => [] },
    sections:        { type: Array, default: () => [] },
    cycles:          { type: Array, default: () => [] },
    niveaux:         { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    periodes:        { type: Array, default: () => [] },
    matieres:        { type: Array, default: () => [] },
    enseignants:     { type: Array, default: () => [] },
});
defineEmits(['submit']);

const isReadOnly = props.mode === 'show';
const currentStep = ref(0);

const steps = [
    { key: 'contexte', label: 'Définition des périodes', icon: 'fas fa-sitemap', requiredFields: ['classe_id'] },
    { key: 'creneaux', label: 'Créneaux',                icon: 'fas fa-table-cells' },
];

const joursOptions = [
    { id: 'lundi', libelle: 'Lundi' }, { id: 'mardi', libelle: 'Mardi' },
    { id: 'mercredi', libelle: 'Mercredi' }, { id: 'jeudi', libelle: 'Jeudi' },
    { id: 'vendredi', libelle: 'Vendredi' }, { id: 'samedi', libelle: 'Samedi' },
];
const statutOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// ── Cascade : Classe → tout le contexte académique (source de vérité unique) ──
watch(() => props.form.classe_id, (id) => {
    if (isReadOnly) return;
    const c = props.classes.find(x => String(x.id) === String(id));
    if (!c) return;
    props.form.ecole_id   = c.ecole_id ?? null;
    props.form.campus_id  = c.campus_id ?? null;
    props.form.section_id = c.section_id ?? null;
    props.form.cycle_id   = c.cycle_id ?? null;
    props.form.niveau_id  = c.niveau_id ?? null;
    if (c.annee_scolaire_id) props.form.annee_scolaire_id = c.annee_scolaire_id;
});

const label = (list, id, key = 'libelle') => {
    const f = list.find(x => String(x.id) === String(id));
    return f ? (f[key] ?? f.libelle ?? f.nom) : '—';
};
const ecoleLabel   = computed(() => label(props.ecoles,   props.form.ecole_id));
const campusLabel  = computed(() => label(props.campuses, props.form.campus_id));
const sectionLabel = computed(() => label(props.sections, props.form.section_id));
const cycleLabel   = computed(() => label(props.cycles,   props.form.cycle_id));
const niveauLabel  = computed(() => label(props.niveaux,  props.form.niveau_id));
const anneeLabel   = computed(() => label(props.anneesScolaires, props.form.annee_scolaire_id));
const institutionLabel = computed(() => {
    const campus = props.campuses.find(x => String(x.id) === String(props.form.campus_id));
    if (!campus?.institution_id) return '—';
    return label(props.institutions, campus.institution_id);
});

// Durée (jours) auto depuis les dates de validité
watch(() => [props.form.date_debut, props.form.date_fin], ([d1, d2]) => {
    if (isReadOnly) return;
    if (d1 && d2) {
        const days = Math.round((new Date(d2) - new Date(d1)) / 86400000);
        props.form.duree = days >= 0 ? days : 0;
    }
});

// ── Créneaux ──
if (!Array.isArray(props.form.creneaux)) props.form.creneaux = [];
const addCreneau = () => {
    props.form.creneaux.push({ jour: '', heure_debut: '', heure_fin: '', matiere_id: '', enseignant_id: '', salle: '' });
};
const removeCreneau = (i) => props.form.creneaux.splice(i, 1);
</script>

<template>
    <FormStepper v-model="currentStep" :steps="steps" :form="form" persist-key="emploi-temps-form" @submit="$emit('submit')">
        <!-- ÉTAPE 1 : DÉFINITION DES PÉRIODES -->
        <template #contexte>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="fw-medium">Année scolaire</label>
                    <SearchableSelect v-model="form.annee_scolaire_id" :options="anneesScolaires" option-value="id" option-label="libelle" placeholder="--" :disabled="isReadOnly" />
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Classe <span class="text-danger">*</span></label>
                    <SearchableSelect v-model="form.classe_id" :options="classes" option-value="id" option-label="libelle" placeholder="-- Choisir la classe --" :disabled="isReadOnly" />
                    <small class="text-muted">Niveau, section, cycle et année se remplissent automatiquement.</small>
                    <span v-if="form.errors?.classe_id" class="text-danger small d-block">{{ form.errors.classe_id }}</span>
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Période</label>
                    <SearchableSelect v-model="form.periode_id" :options="periodes" option-value="id" option-label="libelle" placeholder="-- Trimestre / Semestre --" :disabled="isReadOnly" />
                </div>
                <!-- École / Campus sortis du bloc auto (une institution peut avoir 2 écoles dans 2 campus). -->
                <div class="col-md-6">
                    <label class="fw-medium">École</label>
                    <SearchableSelect v-model="form.ecole_id" :options="ecoles" option-value="id" option-label="libelle" placeholder="--" :disabled="isReadOnly" />
                    <small class="text-muted">Pré-remplie par la classe, modifiable.</small>
                </div>
                <div class="col-md-6">
                    <label class="fw-medium">Campus</label>
                    <SearchableSelect v-model="form.campus_id" :options="campuses" option-value="id" option-label="libelle" placeholder="--" :disabled="isReadOnly" />
                </div>

                <!-- Contexte hérité (auto, lecture seule) -->
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

                <div class="col-md-6">
                    <label class="fw-medium">Libellé période</label>
                    <input v-model="form.libelle" type="text" class="form-control" maxlength="255" :disabled="isReadOnly" placeholder="Ex : EDT 6ème A — Trimestre 1" />
                </div>
                <div class="col-md-2">
                    <label class="fw-medium">Date début</label>
                    <input v-model="form.date_debut" type="date" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-2">
                    <label class="fw-medium">Date fin</label>
                    <input v-model="form.date_fin" type="date" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-2">
                    <label class="fw-medium">Durée (jours)</label>
                    <input :value="form.duree" type="number" class="form-control" readonly disabled />
                </div>

                <div class="col-md-3">
                    <label class="fw-medium">Statut de disponibilité</label>
                    <SearchableSelect v-model="form.etat" :options="statutOptions" option-value="id" option-label="libelle" :disabled="isReadOnly" />
                </div>
            </div>
        </template>

        <!-- ÉTAPE 2 : CRÉNEAUX -->
        <template #creneaux>
            <div v-if="form.errors?.creneaux" class="alert alert-danger">{{ form.errors.creneaux }}</div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width:130px">Jour</th>
                            <th style="width:110px">Heure début</th>
                            <th style="width:110px">Heure fin</th>
                            <th>Matière</th>
                            <th>Enseignant</th>
                            <th style="width:130px">Salle</th>
                            <th class="fit" v-if="!isReadOnly"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(c, i) in form.creneaux" :key="i">
                            <td><SearchableSelect v-model="c.jour" :options="joursOptions" option-value="id" option-label="libelle" placeholder="Jour" :disabled="isReadOnly" /></td>
                            <td><input v-model="c.heure_debut" type="time" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="c.heure_fin" type="time" class="form-control" :disabled="isReadOnly" /></td>
                            <td><SearchableSelect v-model="c.matiere_id" :options="matieres" option-value="id" option-label="libelle" placeholder="Matière" :disabled="isReadOnly" /></td>
                            <td><SearchableSelect v-model="c.enseignant_id" :options="enseignants" option-value="id" option-label="libelle" placeholder="Enseignant" :disabled="isReadOnly" /></td>
                            <td><input v-model="c.salle" type="text" class="form-control" maxlength="125" :disabled="isReadOnly" /></td>
                            <td class="fit" v-if="!isReadOnly">
                                <button type="button" class="btn btn-danger btn-sm" @click="removeCreneau(i)"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr v-if="!form.creneaux.length">
                            <td :colspan="isReadOnly ? 6 : 7" class="text-center text-muted">Aucun créneau. Cliquez « Ajouter une ligne ».</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button v-if="!isReadOnly" type="button" class="btn btn-outline-primary btn-sm mt-2" @click="addCreneau">
                <i class="fa fa-plus"></i> Ajouter une ligne
            </button>
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
