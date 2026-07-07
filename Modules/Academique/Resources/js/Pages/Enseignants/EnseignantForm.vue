<!--
  EnseignantForm.vue — Refonte Phase 2.4 (Steppers).
  Historique : 462 lignes monolithiques / 18 slots hardcodés (7 matières + 2 cycles +
                4 niveaux + 5 classes) → 5 steps / champs multi-select n-n propres.

  Steps :
    1. Identité         (nom, prenoms, nom_restituer, nom_jeune_fille, genre, marital, date_naissance)
    2. Naissance        (place_of_birth, commune → dept/region/pays auto, nationalite)
    3. Formation        (diplome, specialite, annee, langues, teaching_speciality)
    4. Emploi & Enseignement (matricule, contrat, embauche, catégorie, fonction, statut,
                              matières/cycles/niveaux/classes en multi-select n-n)
    5. Contact & Photo  (email, telephone, photo)

  Redondances éliminées :
    - matiere_1..7_id  → matieres_ids[]  (multi-select, sync BelongsToMany)
    - cycle_1..2_id    → cycles_ids[]
    - niveau_1..4_id   → niveaux_ids[]
    - classe_1..5_id   → classes_ids[]
    - department_id, region_id, country_id → auto via useGeoCascade sur commune_id

  Le controller EnseignantController accepte désormais matieres_ids[], cycles_ids[],
  niveaux_ids[], classes_ids[] directement (Phase 3.1 pivot tables).
-->

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import Select2Multiple from '@/Components/Common/Select2Multiple.vue';
import FormStepper from '@/Components/Common/FormStepper.vue';
import { useGeoCascade } from '@/Composables/useGeoCascade';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    mode: { type: String, default: 'create' },
    communes: { type: Array, default: () => [] },
    departements: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    pays: { type: Array, default: () => [] },
    categoriesEnseignant: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    niveaux: { type: Array, default: () => [] },
    classes: { type: Array, default: () => [] },
    genres: { type: Array, default: () => [] },
    naturesContrat: { type: Array, default: () => [] },
    situationsMatrimoniales: { type: Array, default: () => [] },
    langues: { type: Array, default: () => [] },
    statutsEmployes: { type: Array, default: () => [] },
    fonctions: { type: Array, default: () => [] },
});

const emit = defineEmits(['submit']);

const isReadOnly = computed(() => props.mode === 'show');
const currentStep = ref(0);
const photoPreview = ref(null);

// Init défensif des tableaux multi-select.
if (!Array.isArray(props.form.matieres_ids)) props.form.matieres_ids = [];
if (!Array.isArray(props.form.cycles_ids)) props.form.cycles_ids = [];
if (!Array.isArray(props.form.niveaux_ids)) props.form.niveaux_ids = [];
if (!Array.isArray(props.form.classes_ids)) props.form.classes_ids = [];
if (!Array.isArray(props.form.languages)) props.form.languages = [];

// §UX robuste multi-select : purge les IDs qui n'existent plus dans les
// options fournies par le backend (ex : matière archivée, cycle supprimé).
// Évite l'erreur Laravel `exists:matieres_unites,id` sur un ID orphelin
// silencieusement présent dans le form legacy.
function pruneMissing(formKey, options, optionKey = 'id') {
    const list = props.form[formKey];
    if (!Array.isArray(list) || !list.length) return;
    const valid = new Set((options || []).map(o => String(o?.[optionKey])));
    const cleaned = list.filter(v => valid.has(String(v)));
    if (cleaned.length !== list.length) {
        props.form[formKey] = cleaned;
    }
}
watch(
    () => [props.matieres, props.cycles, props.niveaux, props.classes],
    () => {
        pruneMissing('matieres_ids', props.matieres);
        pruneMissing('cycles_ids',   props.cycles);
        pruneMissing('niveaux_ids',  props.niveaux);
        pruneMissing('classes_ids',  props.classes);
    },
    { immediate: true, deep: true }
);

// §UX : `languages` stocke des libellés (pas d'id). On dédoublonne et on trim
// pour éviter les doublons "Français " / "Français" créés par edit successifs.
watch(
    () => props.form?.languages,
    (list) => {
        if (!Array.isArray(list) || !list.length) return;
        const uniq = [...new Set(list.map(v => (v ?? '').toString().trim()).filter(Boolean))];
        if (uniq.length !== list.length || uniq.some((v, i) => v !== list[i])) {
            props.form.languages = uniq;
        }
    },
    { immediate: true }
);

// §UX robuste : on garde le code référentiel EXACT (pas de lowercase forcé).
// Le controller EnseignantController valide via allowedStatutCodes() qui
// génère les 4 variantes de casse (original / lower / UPPER / Title).
const statutsEmployesNormalises = computed(() =>
    (props.statutsEmployes || [])
        .filter(s => s && s.code)
        .map(s => ({ ...s, code: String(s.code) }))
);

// §UX : auto-heal — si form.statut ne matche exactement aucun code (ex 'actif'
// alors que le référentiel a 'ACTIF'), on remplace par la valeur exacte du
// référentiel (match case-insensitive). Le SearchableSelect affichera dès lors
// le bon libellé au lieu d'un select vide.
watch(
    () => [props.form?.statut, statutsEmployesNormalises.value],
    ([current, options]) => {
        if (!current || !options?.length) return;
        const match = options.find(o => String(o.code).toLowerCase() === String(current).toLowerCase());
        if (match && match.code !== current) {
            props.form.statut = match.code;
        }
    },
    { immediate: true }
);

// Cascade géo : commune → département → région → pays (remplit auto le form).
useGeoCascade(props.form, {
    quartiers: () => [],
    communes: () => props.communes,
    departements: () => props.departements,
    regions: () => props.regions,
});

// Photo : preview immédiat via FileReader.
const handlePhotoChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        props.form.photo = file;
        const reader = new FileReader();
        reader.onload = (ev) => (photoPreview.value = ev.target?.result || null);
        reader.readAsDataURL(file);
    } else {
        props.form.photo = null;
        photoPreview.value = null;
    }
};

// Age auto depuis date_of_birth.
const age = computed(() => {
    if (!props.form.date_of_birth) return null;
    const b = new Date(props.form.date_of_birth);
    const t0 = new Date();
    let a = t0.getFullYear() - b.getFullYear();
    const m = t0.getMonth() - b.getMonth();
    if (m < 0 || (m === 0 && t0.getDate() < b.getDate())) a--;
    return a >= 0 ? a : null;
});

// Libellé du dept/région/pays auto — pour affichage lecture seule dans le step 2.
const departementLabel = computed(() => {
    const d = props.departements.find(x => String(x.id) === String(props.form.department_id));
    return d?.libelle || '';
});
const regionLabel = computed(() => {
    const r = props.regions.find(x => String(x.id) === String(props.form.region_id));
    return r?.libelle || '';
});
const paysLabel = computed(() => {
    const p = props.pays.find(x => String(x.id) === String(props.form.country_id));
    return p?.libelle || '';
});

const steps = [
    { key: 'identite',   label: 'Identité',            icon: 'fas fa-id-badge',       requiredFields: ['nom', 'prenoms'] },
    { key: 'naissance',  label: 'Naissance',           icon: 'fas fa-map-marker-alt' },
    { key: 'formation',  label: 'Formation',           icon: 'fas fa-graduation-cap' },
    { key: 'emploi',     label: 'Emploi & Enseignement', icon: 'fas fa-briefcase',    requiredFields: ['statut'] },
    { key: 'contact',    label: 'Contact & Photo',     icon: 'fas fa-address-card' },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="enseignant-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : IDENTITÉ -->
        <template #identite>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>{{ t('fields.nom') }} *</label>
                    <input v-model="form.nom" :disabled="isReadOnly" type="text" class="form-control" />
                    <span v-if="form.errors?.nom" class="text-danger small">{{ form.errors.nom }}</span>
                </div>
                <div class="col-md-6">
                    <label>{{ t('fields.prenoms') }} *</label>
                    <input v-model="form.prenoms" :disabled="isReadOnly" type="text" class="form-control" />
                    <span v-if="form.errors?.prenoms" class="text-danger small">{{ form.errors.prenoms }}</span>
                </div>
                <div class="col-md-6">
                    <label>{{ t('fields.nom_restituer') }}</label>
                    <input v-model="form.nom_restituer" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label>{{ t('fields.nom_jeune_fille') }}</label>
                    <input v-model="form.nom_jeune_fille" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.gender') || 'Genre' }}</label>
                    <SearchableSelect
                        v-model="form.genre_id"
                        :options="genres"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.marital_status') }}</label>
                    <SearchableSelect
                        v-model="form.marital_status"
                        :options="situationsMatrimoniales"
                        optionValue="code"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.date_of_birth') }}</label>
                    <input v-model="form.date_of_birth" :disabled="isReadOnly" type="date" class="form-control" />
                    <small v-if="age !== null" class="text-muted">{{ age }} ans</small>
                </div>
            </div>
        </template>

        <!-- STEP 2 : NAISSANCE & GÉO -->
        <template #naissance>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>{{ t('fields.place_of_birth') }}</label>
                    <input v-model="form.place_of_birth" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ville / village de naissance" />
                </div>
                <div class="col-md-6">
                    <label>{{ t('fields.commune_naissance') }}</label>
                    <SearchableSelect
                        v-model="form.commune_id"
                        :options="communes"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                    <small class="text-muted">Département, région et pays se remplissent automatiquement.</small>
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.departement_naissance') }} <span class="badge bg-secondary">auto</span></label>
                    <input :value="departementLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.region_naissance') }} <span class="badge bg-secondary">auto</span></label>
                    <input :value="regionLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.pays_naissance') }} <span class="badge bg-secondary">auto</span></label>
                    <input :value="paysLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-6">
                    <label>{{ t('fields.nationalite') }}</label>
                    <input v-model="form.nationalite" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
            </div>
        </template>

        <!-- STEP 3 : FORMATION ACADÉMIQUE -->
        <template #formation>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>{{ t('fields.highest_diploma') }}</label>
                    <input v-model="form.highest_diploma" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label>{{ t('fields.speciality') }}</label>
                    <input v-model="form.speciality" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.year_obtained') }}</label>
                    <input v-model="form.year_obtained" :disabled="isReadOnly" type="number" min="1900" :max="new Date().getFullYear()" class="form-control" />
                </div>
                <div class="col-md-8">
                    <label>{{ t('fields.languages') }}</label>
                    <Select2Multiple
                        v-model="form.languages"
                        placeholder="Sélectionner une ou plusieurs langues..."
                    >
                        <option v-for="l in langues" :key="l.code" :value="l.libelle">{{ l.libelle }}</option>
                    </Select2Multiple>
                </div>
                <div class="col-12">
                    <label>{{ t('fields.teaching_speciality') }}</label>
                    <textarea v-model="form.teaching_speciality" :disabled="isReadOnly" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </template>

        <!-- STEP 4 : EMPLOI & ENSEIGNEMENT (multi-select n-n) -->
        <template #emploi>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>{{ t('fields.matricule') }}</label>
                    <input v-model="form.matricule" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.type_contrat') }}</label>
                    <SearchableSelect
                        v-model="form.nature_contrat_id"
                        :options="naturesContrat"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.date_embauche') }}</label>
                    <input v-model="form.date_embauche" :disabled="isReadOnly" type="date" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.categorie_enseignant') }}</label>
                    <SearchableSelect
                        v-model="form.categorie_enseignant_id"
                        :options="categoriesEnseignant"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.fonction') || 'Fonction' }}</label>
                    <SearchableSelect
                        v-model="form.fonction_id"
                        :options="fonctions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4">
                    <label>{{ t('fields.statut') }} *</label>
                    <SearchableSelect
                        v-model="form.statut"
                        :options="statutsEmployesNormalises"
                        optionValue="code"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                </div>

                <hr class="mt-4" />
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-chalkboard-teacher me-1"></i> Domaines d'enseignement</h6>
                    <small class="text-muted d-block mb-2">Sélectionnez toutes les matières, cycles, niveaux et salles concernés.</small>
                </div>
                <div class="col-md-6">
                    <label>Matières enseignées</label>
                    <Select2Multiple
                        v-model="form.matieres_ids"
                        placeholder="Sélectionner une ou plusieurs matières..."
                    >
                        <option v-for="m in matieres" :key="m.id" :value="m.id">{{ m.libelle }}</option>
                    </Select2Multiple>
                </div>
                <div class="col-md-6">
                    <label>Cycles d'enseignement</label>
                    <Select2Multiple
                        v-model="form.cycles_ids"
                        placeholder="Sélectionner un ou plusieurs cycles..."
                    >
                        <option v-for="c in cycles" :key="c.id" :value="c.id">{{ c.libelle }}</option>
                    </Select2Multiple>
                </div>
                <div class="col-md-6">
                    <label>Niveaux d'étude</label>
                    <Select2Multiple
                        v-model="form.niveaux_ids"
                        placeholder="Sélectionner un ou plusieurs niveaux..."
                    >
                        <option v-for="n in niveaux" :key="n.id" :value="n.id">{{ n.libelle }}</option>
                    </Select2Multiple>
                </div>
                <div class="col-md-6">
                    <label>Salles de cours</label>
                    <Select2Multiple
                        v-model="form.classes_ids"
                        placeholder="Sélectionner une ou plusieurs salles..."
                    >
                        <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nom }}</option>
                    </Select2Multiple>
                </div>
            </div>
        </template>

        <!-- STEP 5 : CONTACT & PHOTO -->
        <template #contact>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>{{ t('fields.email') }}</label>
                    <input v-model="form.email" :disabled="isReadOnly" type="email" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label>{{ t('fields.telephone') }}</label>
                    <input v-model="form.telephone" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label>Photo</label>
                    <div v-if="!isReadOnly" class="mb-2">
                        <input type="file" class="form-control" accept="image/*" @change="handlePhotoChange" />
                    </div>
                    <div class="photo-preview mt-2 p-3 bg-light rounded text-center">
                        <img v-if="photoPreview" :src="photoPreview" class="img-thumbnail" style="max-width: 180px; max-height: 180px; object-fit: cover;" />
                        <img v-else-if="form.photo && typeof form.photo === 'string'" :src="'/storage/' + form.photo" class="img-thumbnail" style="max-width: 180px; max-height: 180px; object-fit: cover;" />
                        <div v-else class="text-muted">
                            <i class="fa fa-image fa-4x"></i>
                            <div class="mt-2">Aucune photo</div>
                        </div>
                    </div>
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
.photo-preview {
    border: 2px dashed #e0e0e0;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
