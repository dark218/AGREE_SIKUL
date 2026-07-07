<!--
  PaiementForm.vue — Fix Phase 4.6 (§11.8).
  Historique : form envoyait {nom, code, statut} — décorrélé du fillable réel
  {frais_id, apprenant_id, montant_cents, mode_paiement, reference, date_paiement, recu_par}.

  Refonte : aligné exactement sur schéma DB + validator du controller.
  `recu_par` = utilisateur courant, injecté côté serveur.
-->

<template>
    <form @submit.prevent="submit" class="paiement-form">
        <div class="row g-3">
            <div class="col-md-6">
                <label>Apprenant <span class="text-danger">*</span></label>
                <select
                    v-model.number="form.apprenant_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.apprenant_id }"
                    :disabled="isReadOnly"
                    @change="onApprenantChange"
                    required
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="a in apprenants" :key="a.id" :value="a.id">
                        {{ a.nom }} {{ a.prenoms || '' }}
                    </option>
                </select>
                <div v-if="errors.apprenant_id" class="invalid-feedback">{{ errors.apprenant_id[0] || errors.apprenant_id }}</div>
            </div>
            <div class="col-md-6">
                <label>Frais <span class="text-danger">*</span></label>
                <select
                    v-model.number="form.frais_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.frais_id }"
                    :disabled="isReadOnly"
                    @change="onFraisChange"
                    required
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="f in fraisFiltered" :key="f.id" :value="f.id">
                        {{ f.type_frais?.libelle || 'Frais' }} — reste {{ ((f.montant_cents - f.montant_paye_cents) / 100).toFixed(2) }} €
                    </option>
                </select>
                <small v-if="form.apprenant_id" class="text-muted">Liste filtrée sur l'apprenant.</small>
                <div v-if="errors.frais_id" class="invalid-feedback">{{ errors.frais_id[0] || errors.frais_id }}</div>
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
                <label>Mode de paiement <span class="text-danger">*</span></label>
                <select
                    v-model="form.mode_paiement"
                    class="form-control"
                    :class="{ 'is-invalid': errors.mode_paiement }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="espece">Espèces</option>
                    <option value="cheque">Chèque</option>
                    <option value="virement">Virement</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="carte">Carte bancaire</option>
                </select>
                <div v-if="errors.mode_paiement" class="invalid-feedback">{{ errors.mode_paiement[0] || errors.mode_paiement }}</div>
            </div>
            <div class="col-md-4">
                <label>Date du paiement <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_paiement"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_paiement }"
                    :disabled="isReadOnly"
                    required
                />
                <div v-if="errors.date_paiement" class="invalid-feedback">{{ errors.date_paiement[0] || errors.date_paiement }}</div>
            </div>

            <div class="col-12">
                <label>Référence <small class="text-muted">(N° chèque, ID transaction Mobile Money…)</small></label>
                <input
                    v-model="form.reference"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.reference }"
                    :disabled="isReadOnly"
                    placeholder="Optionnel — mais unique si renseigné"
                />
                <div v-if="errors.reference" class="invalid-feedback">{{ errors.reference[0] || errors.reference }}</div>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('finances.paiements.index')" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    paiement:          { type: Object,  default: () => ({}) },
    frais:             { type: Array,   default: () => [] },
    apprenants:        { type: Array,   default: () => [] },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

const form = ref({
    frais_id:      props.paiement?.frais_id      || '',
    apprenant_id:  props.paiement?.apprenant_id  || '',
    montant_cents: props.paiement?.montant_cents || 0,
    mode_paiement: props.paiement?.mode_paiement || 'espece',
    reference:     props.paiement?.reference     || '',
    date_paiement: props.paiement?.date_paiement
        ? String(props.paiement.date_paiement).split('T')[0].split(' ')[0]
        : new Date().toISOString().split('T')[0],
});

const montantEuros = computed({
    get: () => form.value.montant_cents ? form.value.montant_cents / 100 : 0,
    set: (v) => { form.value.montant_cents = Math.round((Number(v) || 0) * 100); },
});

// Filtre les frais selon l'apprenant sélectionné (mieux : pas de frais non-relatifs).
const fraisFiltered = computed(() => {
    if (!form.value.apprenant_id) return props.frais;
    return props.frais.filter(f => String(f.apprenant_id) === String(form.value.apprenant_id));
});

// Reset frais si l'apprenant change (car frais lié à un apprenant précis).
function onApprenantChange() {
    if (!fraisFiltered.value.some(f => f.id === form.value.frais_id)) {
        form.value.frais_id = '';
    }
}

// Pré-remplit le montant depuis le reste dû sur le frais sélectionné.
function onFraisChange() {
    const f = props.frais.find(x => x.id === form.value.frais_id);
    if (f && !form.value.montant_cents) {
        form.value.montant_cents = Math.max(0, f.montant_cents - f.montant_paye_cents);
    }
}

function submit() { emit('submit', form.value); }

defineExpose({ getFormData: () => form.value, form });
</script>

<style scoped>
.paiement-form {
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
