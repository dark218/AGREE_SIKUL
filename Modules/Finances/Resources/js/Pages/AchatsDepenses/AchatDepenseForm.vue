<!--
  AchatDepenseForm.vue — Refonte §10.4.
  Historique : 400 lignes / 24 champs dont 12 slots paiements
  (date_paiement_1..6 + montant_paiement_1..6) → 3 steps + ChampsPaiement dynamique.

  Steps :
    1. Contexte scolarité (année, section, école)
    2. Dépense (date, nature, tiers, pièces, intitulé, mode, montant, restant)
    3. Paiements (ChampsPaiement dynamique max 6 lignes + statut)

  Le mapping array → 12 slots hardcodés est fait au submit dans Create/Edit.
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
    sections:        { type: Array,  default: () => [] },
    ecoles:          { type: Array,  default: () => [] },
    mode:            { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
});

const emit = defineEmits(['submit']);

const isReadOnly = computed(() => props.mode === 'show');
const currentStep = ref(0);

if (!Array.isArray(props.form.paiements)) props.form.paiements = [];

const num = (v) => Number(v) || 0;

const totalPaye = computed(() =>
    (props.form.paiements || []).reduce((s, p) => s + num(p.montant), 0)
);
const restantAPayer = computed(() =>
    Math.max(0, num(props.form.montant_total) - totalPaye.value)
);

watch(totalPaye,      (v) => { props.form.total_paye      = v; });
watch(restantAPayer,  (v) => { props.form.restant_a_payer = v; });

const etatOptions = [
    { id: 'actif',   libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const modesPaiement = [
    { code: 'especes',      libelle: 'Espèces' },
    { code: 'cheque',       libelle: 'Chèque' },
    { code: 'virement',     libelle: 'Virement' },
    { code: 'mobile_money', libelle: 'Mobile Money' },
    { code: 'carte',        libelle: 'Carte bancaire' },
];

const steps = [
    { key: 'contexte', label: 'Contexte scolarité', icon: 'fas fa-calendar-check', requiredFields: ['annee_scolaire_id', 'ecole_id'] },
    { key: 'depense',  label: 'Dépense',           icon: 'fas fa-file-invoice',    requiredFields: ['date_depense', 'intitule_operation'] },
    { key: 'paiement', label: 'Paiements & Statut', icon: 'fas fa-money-check-alt', requiredFields: ['etat'] },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="achat-depense-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : CONTEXTE -->
        <template #contexte>
            <div class="row g-3">
                <div class="col-md-4">
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
                <div class="col-md-4">
                    <label>Section</label>
                    <SearchableSelect
                        v-model="form.section_id"
                        :options="sections"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4">
                    <label>École <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.ecole_id"
                        :options="ecoles"
                        optionValue="id"
                        optionLabel="nom"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                </div>
            </div>
        </template>

        <!-- STEP 2 : DÉPENSE -->
        <template #depense>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Date <span class="text-danger">*</span></label>
                    <input v-model="form.date_depense" :disabled="isReadOnly" type="date" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Nature de la dépense</label>
                    <input v-model="form.nature_depense" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Tiers / Fournisseur</label>
                    <input v-model="form.tiers_fournisseur" :disabled="isReadOnly" type="text" class="form-control" />
                </div>

                <div class="col-md-4">
                    <label>N° identifiant</label>
                    <input v-model="form.numero_identifiant" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Type de pièce</label>
                    <input v-model="form.type_piece" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Référence pièce</label>
                    <input v-model="form.reference_piece" :disabled="isReadOnly" type="text" class="form-control" />
                </div>

                <div class="col-12">
                    <label>Intitulé de l'opération <span class="text-danger">*</span></label>
                    <input v-model="form.intitule_operation" :disabled="isReadOnly" type="text" class="form-control" />
                </div>

                <div class="col-md-4">
                    <label>Mode de paiement</label>
                    <select v-model="form.mode_paiement" :disabled="isReadOnly" class="form-control">
                        <option value="">-- Sélectionner --</option>
                        <option v-for="m in modesPaiement" :key="m.code" :value="m.code">{{ m.libelle }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Montant total</label>
                    <input v-model.number="form.montant_total" :disabled="isReadOnly" type="number" step="0.01" min="0" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Restant à payer <span class="badge bg-secondary">auto</span></label>
                    <input :value="restantAPayer.toFixed(2)" type="text" class="form-control" readonly disabled />
                </div>
            </div>
        </template>

        <!-- STEP 3 : PAIEMENTS DYNAMIQUES -->
        <template #paiement>
            <div class="row g-3">
                <div class="col-12">
                    <ChampsPaiement
                        v-model="form.paiements"
                        :types-versement="modesPaiement"
                        :disabled="isReadOnly"
                        :max-lignes="6"
                        label-singulier="Paiement"
                    />
                </div>
                <hr class="mt-3" />
                <div class="col-md-6">
                    <div class="p-3 bg-primary bg-opacity-10 rounded border border-primary">
                        <strong class="text-primary">Total payé : {{ totalPaye.toFixed(2) }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-warning bg-opacity-10 rounded border border-warning">
                        <strong class="text-warning">Restant : {{ restantAPayer.toFixed(2) }}</strong>
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
