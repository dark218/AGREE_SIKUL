<!--
  AutreRevenuForm.vue — Refonte §10.4.
  Historique : 282 lignes / 13 champs dont 9 lignes hardcodées
  (uniforme, tenue_mercredi, tenue_sport, autre1..6) → 2 steps + composant
  ChampsPaiement dynamique pour les "autres revenus".

  Steps :
    1. Contexte scolarité (année, niveau → école/campus auto)
    2. Revenus (lignes dynamiques par catégorie + montant, statut)

  Le mapping array → slots hardcodés est fait au submit dans Create/Edit.
-->

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import FormStepper from '@/Components/Common/FormStepper.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import ChampsPaiement from '@/Components/Common/ChampsPaiement.vue';

const { t } = useI18n();

const props = defineProps({
    form:            { type: Object, required: true },
    anneesScolaires: { type: Array,  default: () => [] },
    niveauxEtudes:   { type: Array,  default: () => [] },
    ecoles:          { type: Array,  default: () => [] },
    campuses:        { type: Array,  default: () => [] },
    mode:            { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
});

const emit = defineEmits(['submit']);

const isReadOnly = computed(() => props.mode === 'show');
const currentStep = ref(0);

if (!Array.isArray(props.form.revenus)) props.form.revenus = [];

const autoLabel = (list, id, key = 'nom') => {
    if (!id || !list?.length) return '—';
    const f = list.find(x => String(x.id) === String(id));
    return f?.libelle || f?.[key] || '—';
};
const ecoleLabel  = computed(() => autoLabel(props.ecoles,   props.form.ecole_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

watch(() => props.form.niveau_id, (id) => {
    if (!id) return;
    const n = props.niveauxEtudes?.find(x => x.id == id);
    if (n?.ecole_id) props.form.ecole_id = n.ecole_id;
});

const totalRevenus = computed(() =>
    (props.form.revenus || []).reduce((s, r) => s + (Number(r.montant) || 0), 0)
);

const typesRevenu = [
    { code: 'uniforme',       libelle: 'Uniforme' },
    { code: 'tenue_mercredi', libelle: 'Tenue mercredi' },
    { code: 'tenue_sport',    libelle: 'Tenue sport' },
    { code: 'inscription',    libelle: 'Frais d\'inscription' },
    { code: 'don',            libelle: 'Don / mécénat' },
    { code: 'evenement',      libelle: 'Événement' },
    { code: 'autre',          libelle: 'Autre revenu' },
];

const etatOptions = [
    { id: 'actif',   libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const steps = [
    { key: 'contexte', label: 'Contexte scolarité', icon: 'fas fa-calendar-check' },
    { key: 'revenus',  label: 'Revenus',           icon: 'fas fa-coins', requiredFields: ['etat'] },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="autre-revenu-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : CONTEXTE -->
        <template #contexte>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Année scolaire</label>
                    <SearchableSelect
                        v-model="form.annee_scolaire_id"
                        :options="anneesScolaires"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-6">
                    <label>Niveau d'étude</label>
                    <SearchableSelect
                        v-model="form.niveau_id"
                        :options="niveauxEtudes"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-6">
                    <label>École <span class="badge bg-secondary">auto</span></label>
                    <input :value="ecoleLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-6">
                    <label>Campus <span class="badge bg-secondary">auto</span></label>
                    <input :value="campusLabel" type="text" class="form-control" readonly disabled />
                </div>
            </div>
        </template>

        <!-- STEP 2 : REVENUS -->
        <template #revenus>
            <div class="row g-3">
                <div class="col-12">
                    <ChampsPaiement
                        v-model="form.revenus"
                        :types-versement="typesRevenu"
                        :disabled="isReadOnly"
                        :max-lignes="9"
                        label-singulier="Revenu"
                    />
                </div>

                <hr class="mt-3" />
                <div class="col-md-6">
                    <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                        <strong class="text-success">Total revenus : {{ totalRevenus.toFixed(2) }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <label>État <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.etat"
                        :options="etatOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
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
