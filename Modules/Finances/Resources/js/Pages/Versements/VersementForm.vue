<!--
  VersementForm.vue — Refonte §10.4 (Phase 4 additionnelle).
  Historique : 450 lignes / 37 champs dont 24 versements hardcodés
  (nature_versement_1..12 + montant_versement_1..12) → 3 steps + composant
  ChampsPaiement dynamique.

  Steps :
    1. Apprenant & classe (année, apprenant, classe → école/campus/niveau auto)
    2. Frais (dossier + inscription + scolarité → totaux auto)
    3. Versements (ChampsPaiement dynamique max 12 lignes)

  Le mapping array → 12 slots hardcodés est fait au submit dans Create/Edit
  (fonction versementsToSlots).
-->

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import FormStepper from '@/Components/Common/FormStepper.vue';
import ApprenantSelect from '@/Components/Common/ApprenantSelect.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import ChampsPaiement from '@/Components/Common/ChampsPaiement.vue';

const { t } = useI18n();

const props = defineProps({
    form:            { type: Object, required: true },
    apprenants:      { type: Array,  default: () => [] },
    anneesScolaires: { type: Array,  default: () => [] },
    niveaux:         { type: Array,  default: () => [] },
    classes:         { type: Array,  default: () => [] },
    ecoles:          { type: Array,  default: () => [] },
    campuses:        { type: Array,  default: () => [] },
    mode:            { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
});

const emit = defineEmits(['submit']);

const isReadOnly = computed(() => props.mode === 'show');
const currentStep = ref(0);

// Init défensif de la liste des versements pour ChampsPaiement.
// La transformation array ↔ slots 12 se fait au submit dans Create.vue.
if (!Array.isArray(props.form.versements)) props.form.versements = [];

// Auto-fill hiérarchie depuis classe sélectionnée.
watch(() => props.form.classe_id, (id) => {
    if (!id) return;
    const c = props.classes.find(x => String(x.id) === String(id));
    if (c) {
        if (c.ecole_id)          props.form.ecole_id          = c.ecole_id;
        if (c.campus_id)         props.form.campus_id         = c.campus_id;
        if (c.niveau_id)         props.form.niveau_id         = c.niveau_id;
        if (c.annee_scolaire_id) props.form.annee_scolaire_id = c.annee_scolaire_id;
    }
});

const autoLabel = (list, id, key = 'nom') => {
    if (!id || !list?.length) return '—';
    const f = list.find(x => String(x.id) === String(id));
    return f?.libelle || f?.[key] || '—';
};
const niveauLabel = computed(() => autoLabel(props.niveaux,  props.form.niveau_id, 'libelle'));
const ecoleLabel  = computed(() => autoLabel(props.ecoles,   props.form.ecole_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

// Dérivations financières (client-side, à valider serveur).
const num = (v) => Number(v) || 0;
const totalFrais = computed(() =>
    num(props.form.frais_dossier) + num(props.form.frais_inscription) + num(props.form.frais_scolarite)
);
const totalVersements = computed(() =>
    (props.form.versements || []).reduce((s, v) => s + num(v.montant), 0)
);
const restantAPayer = computed(() => Math.max(0, totalFrais.value - totalVersements.value));

// Persiste les dérivés dans le form pour le submit.
watch(totalVersements, (v) => { props.form.total_paye     = v; });
watch(restantAPayer,   (v) => { props.form.restant_a_payer = v; });

const etatOptions = [
    { id: 'actif',   libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Types de versement — pré-remplis, ajustables par le référentiel si besoin.
const typesVersement = [
    { code: 'especes',        libelle: 'Espèces' },
    { code: 'cheque',         libelle: 'Chèque' },
    { code: 'virement',       libelle: 'Virement' },
    { code: 'mobile_money',   libelle: 'Mobile Money' },
    { code: 'carte',          libelle: 'Carte bancaire' },
    { code: 'autre',          libelle: 'Autre' },
];

const steps = [
    { key: 'apprenant',  label: 'Apprenant & Classe', icon: 'fas fa-user-graduate', requiredFields: ['annee_scolaire_id', 'apprenant_id'] },
    { key: 'frais',      label: 'Frais',              icon: 'fas fa-file-invoice-dollar' },
    { key: 'versements', label: 'Versements',         icon: 'fas fa-money-bill-transfer', requiredFields: ['etat'] },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="versement-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : APPRENANT & CLASSE -->
        <template #apprenant>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Année scolaire <span class="text-danger">*</span></label>
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
                    <label>Apprenant <span class="text-danger">*</span></label>
                    <ApprenantSelect
                        v-model="form.apprenant_id"
                        :apprenants="apprenants"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-6">
                    <label>Classe</label>
                    <SearchableSelect
                        v-model="form.classe_id"
                        :options="classes"
                        optionValue="id"
                        optionLabel="nom"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-12">
                    <HierarchyContextBar
                        v-if="form.classe_id"
                        :form="form"
                        :ecoles="ecoles"
                        :campuses="campuses"
                        :niveaux="niveaux"
                    />
                </div>
                <div class="col-md-4">
                    <label>Niveau <span class="badge bg-secondary">auto</span></label>
                    <input :value="niveauLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>École <span class="badge bg-secondary">auto</span></label>
                    <input :value="ecoleLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>Campus <span class="badge bg-secondary">auto</span></label>
                    <input :value="campusLabel" type="text" class="form-control" readonly disabled />
                </div>
            </div>
        </template>

        <!-- STEP 2 : FRAIS -->
        <template #frais>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Frais de dossier</label>
                    <input v-model.number="form.frais_dossier" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" placeholder="0" />
                </div>
                <div class="col-md-4">
                    <label>Frais d'inscription</label>
                    <input v-model.number="form.frais_inscription" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" placeholder="0" />
                </div>
                <div class="col-md-4">
                    <label>Frais de scolarité</label>
                    <input v-model.number="form.frais_scolarite" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" placeholder="0" />
                </div>

                <hr class="mt-4" />
                <div class="col-md-4">
                    <div class="p-3 bg-info bg-opacity-10 rounded border border-info">
                        <strong class="text-info">Total dû : {{ totalFrais.toFixed(2) }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                        <strong class="text-success">Total versé : {{ totalVersements.toFixed(2) }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-warning bg-opacity-10 rounded border border-warning">
                        <strong class="text-warning">Restant : {{ restantAPayer.toFixed(2) }}</strong>
                    </div>
                </div>
            </div>
        </template>

        <!-- STEP 3 : VERSEMENTS (dynamic) -->
        <template #versements>
            <div class="row g-3">
                <div class="col-12">
                    <ChampsPaiement
                        v-model="form.versements"
                        :types-versement="typesVersement"
                        :disabled="isReadOnly"
                        :max-lignes="12"
                        label-singulier="Versement"
                    />
                </div>
                <hr class="mt-3" />
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
