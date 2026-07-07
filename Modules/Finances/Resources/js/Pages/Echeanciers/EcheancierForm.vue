<!--
  EcheancierForm.vue — Fix Phase 4.6 (§11.8).
  Historique : form envoyait {nom, code, statut} — décorrélé du fillable réel
  {frais_id, numero_echeance, montant_cents, date_echeance, date_paiement, statut}.

  Refonte : aligné exactement sur schéma DB + validator du controller.
-->

<template>
    <form @submit.prevent="submit" class="echeancier-form">
        <div class="row g-3">
            <div class="col-md-8">
                <label>Frais rattaché <span class="text-danger">*</span></label>
                <select
                    v-model.number="form.frais_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.frais_id }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="f in frais" :key="f.id" :value="f.id">
                        {{ f.type_frais?.libelle || 'Frais' }} —
                        {{ f.apprenant?.nom }} {{ f.apprenant?.prenoms || '' }} —
                        reste {{ ((f.montant_cents - f.montant_paye_cents) / 100).toFixed(2) }} €
                    </option>
                </select>
                <div v-if="errors.frais_id" class="invalid-feedback">{{ errors.frais_id[0] || errors.frais_id }}</div>
            </div>
            <div class="col-md-4">
                <label>N° échéance <span class="text-danger">*</span></label>
                <input
                    v-model.number="form.numero_echeance"
                    type="number"
                    min="1"
                    class="form-control"
                    :class="{ 'is-invalid': errors.numero_echeance }"
                    :disabled="isReadOnly"
                    placeholder="1, 2, 3…"
                    required
                />
                <div v-if="errors.numero_echeance" class="invalid-feedback">{{ errors.numero_echeance[0] || errors.numero_echeance }}</div>
            </div>

            <div class="col-md-4">
                <label>Montant (€) <span class="text-danger">*</span></label>
                <input
                    v-model.number="montantEuros"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :class="{ 'is-invalid': errors.montant_cents }"
                    :disabled="isReadOnly"
                    required
                />
                <small class="text-muted">{{ form.montant_cents || 0 }} centimes</small>
                <div v-if="errors.montant_cents" class="invalid-feedback">{{ errors.montant_cents[0] || errors.montant_cents }}</div>
            </div>
            <div class="col-md-4">
                <label>Date d'échéance <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_echeance"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_echeance }"
                    :disabled="isReadOnly"
                    required
                />
                <div v-if="errors.date_echeance" class="invalid-feedback">{{ errors.date_echeance[0] || errors.date_echeance }}</div>
            </div>
            <div class="col-md-4">
                <label>Date de paiement effectif</label>
                <input
                    v-model="form.date_paiement"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_paiement }"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">Laisser vide si non payé</small>
                <div v-if="errors.date_paiement" class="invalid-feedback">{{ errors.date_paiement[0] || errors.date_paiement }}</div>
            </div>

            <div class="col-md-6">
                <label>Statut <span class="text-danger">*</span></label>
                <select
                    v-model="form.statut"
                    class="form-control"
                    :class="{ 'is-invalid': errors.statut }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="en_attente">En attente</option>
                    <option value="paye">Payé</option>
                    <option value="retard">En retard</option>
                </select>
                <div v-if="errors.statut" class="invalid-feedback">{{ errors.statut[0] || errors.statut }}</div>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('finances.echeanciers.index')" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    echeancier:        { type: Object,  default: () => ({}) },
    frais:             { type: Array,   default: () => [] },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

const form = ref({
    frais_id:        props.echeancier?.frais_id        || '',
    numero_echeance: props.echeancier?.numero_echeance || 1,
    montant_cents:   props.echeancier?.montant_cents   || 0,
    date_echeance:   props.echeancier?.date_echeance
        ? String(props.echeancier.date_echeance).split('T')[0].split(' ')[0]
        : '',
    date_paiement:   props.echeancier?.date_paiement
        ? String(props.echeancier.date_paiement).split('T')[0].split(' ')[0]
        : '',
    statut:          props.echeancier?.statut          || 'en_attente',
});

const montantEuros = computed({
    get: () => form.value.montant_cents ? form.value.montant_cents / 100 : 0,
    set: (v) => { form.value.montant_cents = Math.round((Number(v) || 0) * 100); },
});

function submit() { emit('submit', form.value); }

defineExpose({ getFormData: () => form.value, form });
</script>

<style scoped>
.echeancier-form {
    background: white;
    padding: 20px;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}
label {
    font-weight: 500;
    color: #374151;
    font-size: 0.9rem;
    margin-bottom: 0.4rem;
    display: block;
}
.form-control:disabled {
    background-color: #f1f5f9;
    cursor: not-allowed;
}
.form-actions {
    display: flex;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}
</style>
