<!--
  Saisie EN LOT d'absences apprenants.
  Onglet « Absence » : contexte (Année/Classe/École/Campus/Matière/Enseignant)
    + liste des apprenants à cocher + dates/durée/statut.
  Onglet « Justificatifs » : une ligne par apprenant coché (fichiers + commentaire + état).
-->
<script setup>
import { ref, computed, watch } from 'vue';
import FormStepper from '@/Components/Common/FormStepper.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const props = defineProps({
    form: { type: Object, required: true },
    apprenants: { type: Array, default: () => [] },
    classes: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    campuses: { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
    enseignants: { type: Array, default: () => [] },
});
defineEmits(['submit']);

const currentStep = ref(0);
const steps = [
    { key: 'absence', label: 'Absence', icon: 'fas fa-calendar-xmark', requiredFields: ['date_debut', 'date_fin'] },
    { key: 'justificatifs', label: 'Justificatifs', icon: 'fas fa-paperclip' },
];

const statutOptions = [
    { id: 'en_attente', libelle: 'En attente' },
    { id: 'validee', libelle: 'Validée' },
    { id: 'rejetee', libelle: 'Rejetée' },
];
const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Apprenants filtrés par la classe choisie.
const apprenantsQuery = ref('');
const filteredApprenants = computed(() => {
    let list = props.apprenants;
    if (props.form.classe_id) list = list.filter((a) => String(a.classe_id) === String(props.form.classe_id));
    const q = apprenantsQuery.value.trim().toLowerCase();
    if (q) list = list.filter((a) => `${a.nom} ${a.prenoms} ${a.matricule}`.toLowerCase().includes(q));
    return list;
});
const apprenantLabel = (id) => {
    const a = props.apprenants.find((x) => String(x.id) === String(id));
    return a ? `${a.nom || ''} ${a.prenoms || ''}${a.matricule ? ' (' + a.matricule + ')' : ''}`.trim() : id;
};
const toggleApprenant = (id) => {
    if (!Array.isArray(props.form.apprenants)) props.form.apprenants = [];
    const i = props.form.apprenants.findIndex((x) => String(x) === String(id));
    if (i === -1) props.form.apprenants.push(id);
    else props.form.apprenants.splice(i, 1);
};
const isSelected = (id) => (props.form.apprenants || []).map(String).includes(String(id));

// Cascade : Classe → École / Campus / Année (auto, modifiables).
watch(() => props.form.classe_id, (id) => {
    const c = props.classes.find((x) => String(x.id) === String(id));
    if (!c) return;
    if (c.ecole_id) props.form.ecole_id = c.ecole_id;
    if (c.campus_id) props.form.campus_id = c.campus_id;
    if (c.annee_scolaire_id) props.form.annee_scolaire_id = c.annee_scolaire_id;
});

// Durée auto depuis les dates.
const calculateHeures = () => {
    if (props.form.date_debut && props.form.date_fin) {
        const h = (new Date(props.form.date_fin) - new Date(props.form.date_debut)) / 3600000;
        props.form.nombre_heures = Math.max(0, parseFloat(h.toFixed(2)));
    }
};
watch(() => [props.form.date_debut, props.form.date_fin], calculateHeures);

// Synchronise les lignes Justificatifs avec les apprenants cochés.
if (!props.form.justificatifs || typeof props.form.justificatifs !== 'object') props.form.justificatifs = {};
watch(() => [...(props.form.apprenants || [])], (ids) => {
    const j = props.form.justificatifs || {};
    ids.forEach((id) => { if (!j[id]) j[id] = { commentaire: '', etat: 'actif', files: null }; });
    Object.keys(j).forEach((k) => { if (!ids.map(String).includes(String(k))) delete j[k]; });
    props.form.justificatifs = { ...j };
}, { deep: true });

const onFiles = (id, e) => {
    if (!props.form.justificatifs[id]) props.form.justificatifs[id] = { commentaire: '', etat: 'actif', files: null };
    props.form.justificatifs[id].files = Array.from(e.target.files || []);
};
</script>

<template>
    <FormStepper v-model="currentStep" :steps="steps" :form="form" persist-key="absence-apprenant-batch" @submit="$emit('submit')">
        <!-- ONGLET ABSENCE -->
        <template #absence>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="fw-medium">Année scolaire</label>
                    <SearchableSelect v-model="form.annee_scolaire_id" :options="anneesScolaires" option-value="id" option-label="libelle" placeholder="--" />
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Classe</label>
                    <SearchableSelect v-model="form.classe_id" :options="classes" option-value="id" option-label="libelle" placeholder="-- Choisir la classe --" />
                    <small class="text-muted">Filtre les apprenants et remonte École/Campus/Année.</small>
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">École</label>
                    <SearchableSelect v-model="form.ecole_id" :options="ecoles" option-value="id" option-label="libelle" placeholder="--" />
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Campus</label>
                    <SearchableSelect v-model="form.campus_id" :options="campuses" option-value="id" option-label="libelle" placeholder="--" />
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Matière</label>
                    <SearchableSelect v-model="form.matiere_id" :options="matieres" option-value="id" option-label="libelle" placeholder="--" />
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Enseignant</label>
                    <SearchableSelect v-model="form.enseignant_id" :options="enseignants" option-value="id" option-label="libelle" placeholder="--" />
                </div>

                <!-- Apprenants à cocher -->
                <div class="col-12">
                    <label class="fw-medium">Apprenant(s) <span class="text-danger">*</span></label>
                    <input v-model="apprenantsQuery" type="text" class="form-control mb-2" placeholder="Rechercher un apprenant…" />
                    <div class="apprenants-box">
                        <label v-for="a in filteredApprenants" :key="a.id" class="apprenant-item" :class="{ 'is-checked': isSelected(a.id) }">
                            <input type="checkbox" :checked="isSelected(a.id)" @change="toggleApprenant(a.id)" />
                            <span>{{ a.nom }} {{ a.prenoms }} <small class="text-muted">{{ a.matricule }}</small></span>
                        </label>
                        <div v-if="filteredApprenants.length === 0" class="text-muted p-2">Aucun apprenant (choisissez une classe).</div>
                    </div>
                    <small v-if="form.apprenants?.length" class="text-success"><i class="fa fa-check-circle"></i> {{ form.apprenants.length }} apprenant(s) sélectionné(s)</small>
                    <span v-if="form.errors?.apprenants" class="text-danger small d-block">{{ form.errors.apprenants }}</span>
                </div>

                <div class="col-md-4">
                    <label class="fw-medium">Date et heure de début <span class="text-danger">*</span></label>
                    <input v-model="form.date_debut" type="datetime-local" class="form-control" @change="calculateHeures" />
                    <span v-if="form.errors?.date_debut" class="text-danger small d-block">{{ form.errors.date_debut }}</span>
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Date et heure de fin <span class="text-danger">*</span></label>
                    <input v-model="form.date_fin" type="datetime-local" class="form-control" @change="calculateHeures" />
                    <span v-if="form.errors?.date_fin" class="text-danger small d-block">{{ form.errors.date_fin }}</span>
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Durée (en heures)</label>
                    <input v-model.number="form.nombre_heures" type="number" step="0.01" min="0" class="form-control" disabled placeholder="Auto" />
                </div>
                <div class="col-md-4">
                    <label class="fw-medium">Statut <span class="text-danger">*</span></label>
                    <SearchableSelect v-model="form.statut" :options="statutOptions" option-value="id" option-label="libelle" placeholder="--" />
                </div>
            </div>
        </template>

        <!-- ONGLET JUSTIFICATIFS -->
        <template #justificatifs>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="min-width:200px">Apprenant</th>
                            <th style="min-width:220px">Choisir des fichiers</th>
                            <th>Commentaire</th>
                            <th style="width:140px">État</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="id in (form.apprenants || [])" :key="id">
                            <td>{{ apprenantLabel(id) }}</td>
                            <td>
                                <input type="file" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" @change="onFiles(id, $event)" />
                                <small v-if="form.justificatifs[id]?.files?.length" class="text-muted">{{ form.justificatifs[id].files.length }} fichier(s)</small>
                            </td>
                            <td><input v-model="form.justificatifs[id].commentaire" type="text" class="form-control" /></td>
                            <td>
                                <select v-model="form.justificatifs[id].etat" class="form-control">
                                    <option v-for="o in etatOptions" :key="o.id" :value="o.id">{{ o.libelle }}</option>
                                </select>
                            </td>
                        </tr>
                        <tr v-if="!(form.apprenants || []).length">
                            <td colspan="4" class="text-center text-muted">Cochez d'abord des apprenants dans l'onglet Absence.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </FormStepper>
</template>

<style scoped>
label.fw-medium { font-weight: 500; color: #374151; font-size: .9rem; margin-bottom: .35rem; display: block; }
.apprenants-box { max-height: 240px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; background: #f9fafb; }
.apprenant-item { display: flex; align-items: center; gap: 10px; padding: 7px 10px; border-radius: 6px; cursor: pointer; margin: 0; font-weight: 500; }
.apprenant-item:hover { background: #f0f7ff; }
.apprenant-item.is-checked { background: #eff6ff; color: #1e40af; }
.apprenant-item input { width: 17px; height: 17px; }
</style>
