<!--
  ParentForm.vue — Refonte Phase 2.2 (Steppers).
  Historique : 761 lignes / 78 champs → 4 steps / ~20 champs effectifs.

  Steps :
    1. Enfants rattachés (apprenant_ids + lien_parente)
    2. Père (5 champs : nom complet, profession, tel, email, adresse)
    3. Mère (5 champs) + case "Même adresse que le père"
    4. Tuteur(s) — Tuteur 1 optionnel, Tuteur 2 masqué par défaut

  Redondances éliminées :
    - Blocs quartier/commune/departement/region/pays/code_postal/boite_postal
      par personne (adresse texte libre suffit)
    - whatsapp_1/2, telephone_2, email_2 (garder 1 tel + 1 email)
    - organisation_travail + ville_travail + pays_travail fusionnés en "Employeur"

  Auto-fill préservé : apprenant sélectionné → nom_complet père/mère/tuteur,
  école commune de la fratrie.
-->

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import FormStepper from '@/Components/Common/FormStepper.vue';
import ApprenantsPicker from '@/Components/Common/ApprenantsPicker.vue';

const { t } = useI18n();

const props = defineProps({
    form:         { type: Object, required: true },
    apprenants:   { type: Array,  default: () => [] },
    classes:      { type: Array,  default: () => [] },
    ecoles:       { type: Array,  default: () => [] },
    institutions: { type: Array,  default: () => [] },
    campuses:     { type: Array,  default: () => [] },
    mode:         {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const emit = defineEmits(['submit']);

const isReadOnly = props.mode === 'show';
const currentStep = ref(0);

// Toggle affichage Tuteur 2 (masqué par défaut, s'ouvre au clic).
const showTuteur2 = ref(!!props.form.tuteur2_nom_complet);

// Init des listes (défensif).
if (!props.form.apprenant_ids)  props.form.apprenant_ids = [];
if (!props.form.lien_parente)   props.form.lien_parente = [];

const steps = [
    { key: 'enfants', label: 'Enfants',    icon: 'fas fa-child',        requiredFields: ['apprenant_ids'] },
    { key: 'pere',    label: 'Père',       icon: 'fas fa-user' },
    { key: 'mere',    label: 'Mère',       icon: 'fas fa-user' },
    { key: 'tuteurs', label: 'Tuteur(s)',  icon: 'fas fa-user-shield' },
];

// Un seul champ "Nom(s) et prénom(s)" par personne — auto-fill depuis
// l'apprenant sélectionné (nom_pere/mere/tuteur/responsable_legal).
const onApprenantSelected = ({ apprenant, isFirst }) => {
    if (!apprenant || !isFirst || isReadOnly) return;

    const fillIfEmpty = (target, value) => {
        if (value && (!props.form[target] || String(props.form[target]).trim() === '')) {
            props.form[target] = value;
        }
    };

    fillIfEmpty('pere_nom_complet',     apprenant.nom_pere);
    fillIfEmpty('mere_nom_complet',     apprenant.nom_mere);
    fillIfEmpty('tuteur1_nom_complet',  apprenant.nom_tuteur);

    if (apprenant.nom_responsable_legal) {
        const targetKey = props.form.tuteur1_nom_complet
            ? 'tuteur2_nom_complet'
            : 'tuteur1_nom_complet';
        fillIfEmpty(targetKey, apprenant.nom_responsable_legal);
    }
};

// Auto-fill école commune de la fratrie.
watch(() => props.form.apprenant_ids, (ids) => {
    if (!ids || ids.length === 0) return;
    const first = props.apprenants.find(a => String(a.id) === String(ids[0]));
    if (first?.ecole_id) props.form.ecole_id = first.ecole_id;
}, { deep: true, immediate: true });

// Case "Même adresse que le père" pour la mère.
function copyAdresseFromPere() {
    if (isReadOnly) return;
    props.form.mere_adresse_residence = props.form.pere_adresse_residence || '';
}

function addTuteur2() {
    showTuteur2.value = true;
}

function removeTuteur2() {
    props.form.tuteur2_nom_complet = '';
    props.form.tuteur2_profession = '';
    props.form.tuteur2_telephone_1 = '';
    props.form.tuteur2_email = '';
    props.form.tuteur2_adresse_residence = '';
    showTuteur2.value = false;
}
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="parent-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : ENFANTS RATTACHÉS -->
        <template #enfants>
            <div class="mb-3">
                <label class="fw-medium">
                    {{ t('fields.apprenants') || 'Apprenants rattachés' }}
                    <small class="text-muted">— fratrie dans la même école</small>
                </label>
                <ApprenantsPicker
                    v-model="form.apprenant_ids"
                    v-model:lien-parente="form.lien_parente"
                    :apprenants="apprenants"
                    :show-lien="true"
                    :disabled="isReadOnly"
                    @apprenant-selected="onApprenantSelected"
                />
                <span v-if="form.errors?.apprenant_ids" class="text-danger d-block mt-1">
                    <strong>{{ Array.isArray(form.errors.apprenant_ids) ? form.errors.apprenant_ids[0] : form.errors.apprenant_ids }}</strong>
                </span>
            </div>
        </template>

        <!-- STEP 2 : PÈRE (5 champs) -->
        <template #pere>
            <div class="row g-3">
                <div class="col-12">
                    <label>Nom(s) et prénom(s) du père</label>
                    <input v-model="form.pere_nom_complet" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom(s) et prénom(s) du père" />
                </div>
                <div class="col-md-6">
                    <label>Profession</label>
                    <input v-model="form.pere_profession" :disabled="isReadOnly" type="text" class="form-control" placeholder="Profession" />
                </div>
                <div class="col-md-6">
                    <label>Téléphone</label>
                    <input v-model="form.pere_telephone_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone" />
                </div>
                <div class="col-md-6">
                    <label>Email</label>
                    <input v-model="form.pere_email_1" :disabled="isReadOnly" type="email" class="form-control" placeholder="Email" />
                </div>
                <div class="col-md-6">
                    <label>Adresse de résidence</label>
                    <input v-model="form.pere_adresse_residence" :disabled="isReadOnly" type="text" class="form-control" placeholder="Adresse complète" />
                </div>
            </div>
        </template>

        <!-- STEP 3 : MÈRE (5 champs + "Même adresse que père") -->
        <template #mere>
            <div class="row g-3">
                <div class="col-12">
                    <label>Nom(s) et prénom(s) de la mère</label>
                    <input v-model="form.mere_nom_complet" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom(s) et prénom(s) de la mère" />
                </div>
                <div class="col-md-6">
                    <label>Profession</label>
                    <input v-model="form.mere_profession" :disabled="isReadOnly" type="text" class="form-control" placeholder="Profession" />
                </div>
                <div class="col-md-6">
                    <label>Téléphone</label>
                    <input v-model="form.mere_telephone_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone" />
                </div>
                <div class="col-md-6">
                    <label>Email</label>
                    <input v-model="form.mere_email_1" :disabled="isReadOnly" type="email" class="form-control" placeholder="Email" />
                </div>
                <div class="col-md-6">
                    <label>
                        Adresse de résidence
                        <button v-if="!isReadOnly && form.pere_adresse_residence"
                                type="button"
                                class="btn btn-link btn-sm p-0 ms-2"
                                @click="copyAdresseFromPere">
                            <i class="fa fa-clone"></i> Copier depuis le père
                        </button>
                    </label>
                    <input v-model="form.mere_adresse_residence" :disabled="isReadOnly" type="text" class="form-control" placeholder="Adresse complète" />
                </div>
            </div>
        </template>

        <!-- STEP 4 : TUTEUR(S) -->
        <template #tuteurs>
            <div class="row g-3">
                <!-- Tuteur 1 : optionnel -->
                <div class="col-12">
                    <h6 class="text-primary mb-2">
                        <i class="fa fa-user-shield me-1"></i> Tuteur 1
                        <small class="text-muted">(optionnel)</small>
                    </h6>
                </div>
                <div class="col-12">
                    <label>Nom(s) et prénom(s) du tuteur</label>
                    <input v-model="form.tuteur1_nom_complet" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom(s) et prénom(s) du tuteur" />
                </div>
                <div class="col-md-6">
                    <label>Profession</label>
                    <input v-model="form.tuteur1_profession" :disabled="isReadOnly" type="text" class="form-control" placeholder="Profession" />
                </div>
                <div class="col-md-6">
                    <label>Téléphone</label>
                    <input v-model="form.tuteur1_telephone_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone" />
                </div>
                <div class="col-md-6">
                    <label>Email</label>
                    <input v-model="form.tuteur1_email" :disabled="isReadOnly" type="email" class="form-control" placeholder="Email" />
                </div>
                <div class="col-md-6">
                    <label>Adresse de résidence</label>
                    <input v-model="form.tuteur1_adresse_residence" :disabled="isReadOnly" type="text" class="form-control" placeholder="Adresse complète" />
                </div>

                <!-- Tuteur 2 : masqué par défaut -->
                <div class="col-12" v-if="!showTuteur2 && !isReadOnly">
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="addTuteur2">
                        <i class="fa fa-plus"></i> Ajouter un 2ᵉ tuteur
                    </button>
                </div>

                <template v-if="showTuteur2">
                    <div class="col-12 mt-4">
                        <h6 class="text-primary mb-2 d-flex align-items-center">
                            <i class="fa fa-user-shield me-1"></i> Tuteur 2
                            <button v-if="!isReadOnly" type="button" class="btn btn-link text-danger p-0 ms-auto" @click="removeTuteur2">
                                <i class="fa fa-times"></i> Retirer
                            </button>
                        </h6>
                    </div>
                    <div class="col-12">
                        <label>Nom(s) et prénom(s) du tuteur</label>
                        <input v-model="form.tuteur2_nom_complet" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom(s) et prénom(s) du tuteur" />
                    </div>
                    <div class="col-md-6">
                        <label>Profession</label>
                        <input v-model="form.tuteur2_profession" :disabled="isReadOnly" type="text" class="form-control" placeholder="Profession" />
                    </div>
                    <div class="col-md-6">
                        <label>Téléphone</label>
                        <input v-model="form.tuteur2_telephone_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone" />
                    </div>
                    <div class="col-md-6">
                        <label>Email</label>
                        <input v-model="form.tuteur2_email" :disabled="isReadOnly" type="email" class="form-control" placeholder="Email" />
                    </div>
                    <div class="col-md-6">
                        <label>Adresse de résidence</label>
                        <input v-model="form.tuteur2_adresse_residence" :disabled="isReadOnly" type="text" class="form-control" placeholder="Adresse complète" />
                    </div>
                </template>
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
