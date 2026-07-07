<!--
  FraisForm.vue — Fix Phase 4.6 (§11.8).
  Historique : form envoyait {nom, code, statut} — décorrélé du fillable réel
  {apprenant_id, annee_scolaire_id, type_frais_id, montant_cents, montant_paye_cents, statut}.
  Résultat : SQL crash car apprenant_id / annee_scolaire_id / type_frais_id NOT NULL.

  Refonte : aligné exactement sur le schéma DB. Le montant par défaut se remplit
  depuis le TypeFrais sélectionné (comportement métier attendu).
-->

<template>
    <form @submit.prevent="submit" class="frais-form">
        <div class="row g-3">
            <div class="col-md-6">
                <label>Apprenant <span class="text-danger">*</span></label>
                <select
                    v-model.number="form.apprenant_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.apprenant_id }"
                    :disabled="isReadOnly"
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
                <label>Année scolaire <span class="text-danger">*</span></label>
                <select
                    v-model.number="form.annee_scolaire_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.annee_scolaire_id }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="a in anneesScolaires" :key="a.id" :value="a.id">{{ a.libelle }}</option>
                </select>
                <div v-if="errors.annee_scolaire_id" class="invalid-feedback">{{ errors.annee_scolaire_id[0] || errors.annee_scolaire_id }}</div>
            </div>

            <div class="col-md-6">
                <label>Type de frais <span class="text-danger">*</span></label>
                <select
                    v-model.number="form.type_frais_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.type_frais_id }"
                    :disabled="isReadOnly"
                    @change="onTypeFraisChange"
                    required
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="t in typesFrais" :key="t.id" :value="t.id">
                        {{ t.libelle }} ({{ t.code }})
                    </option>
                </select>
                <small class="text-muted">Le montant se pré-remplit depuis le type de frais.</small>
                <div v-if="errors.type_frais_id" class="invalid-feedback">{{ errors.type_frais_id[0] || errors.type_frais_id }}</div>
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
                    <option value="non_paye">Non payé</option>
                    <option value="partiellement_paye">Partiellement payé</option>
                    <option value="paye">Payé</option>
                </select>
                <div v-if="errors.statut" class="invalid-feedback">{{ errors.statut[0] || errors.statut }}</div>
            </div>

            <div class="col-md-6">
                <label>Montant total (€) <span class="text-danger">*</span></label>
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
            <div class="col-md-6">
                <label>Montant déjà payé (€)</label>
                <input
                    v-model.number="montantPayeEuros"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :class="{ 'is-invalid': errors.montant_paye_cents }"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">Reste : {{ resteEuros.toFixed(2) }} €</small>
                <div v-if="errors.montant_paye_cents" class="invalid-feedback">{{ errors.montant_paye_cents[0] || errors.montant_paye_cents }}</div>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('finances.frais.index')" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    frais:             { type: Object,  default: () => ({}) },
    typesFrais:        { type: Array,   default: () => [] },
    apprenants:        { type: Array,   default: () => [] },
    anneesScolaires:   { type: Array,   default: () => [] },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

const form = ref({
    apprenant_id:       props.frais?.apprenant_id       || '',
    annee_scolaire_id:  props.frais?.annee_scolaire_id  || '',
    type_frais_id:      props.frais?.type_frais_id      || '',
    montant_cents:      props.frais?.montant_cents      || 0,
    montant_paye_cents: props.frais?.montant_paye_cents || 0,
    statut:             props.frais?.statut             || 'non_paye',
});

const montantEuros = computed({
    get: () => form.value.montant_cents ? form.value.montant_cents / 100 : 0,
    set: (v) => { form.value.montant_cents = Math.round((Number(v) || 0) * 100); },
});
const montantPayeEuros = computed({
    get: () => form.value.montant_paye_cents ? form.value.montant_paye_cents / 100 : 0,
    set: (v) => { form.value.montant_paye_cents = Math.round((Number(v) || 0) * 100); },
});
const resteEuros = computed(() => Math.max(0, montantEuros.value - montantPayeEuros.value));

// Pré-remplit le montant depuis le type de frais sélectionné (si vide).
function onTypeFraisChange() {
    const t = props.typesFrais.find(x => x.id === form.value.type_frais_id);
    if (t?.montant_cents && !form.value.montant_cents) {
        form.value.montant_cents = t.montant_cents;
    }
}

function submit() { emit('submit', form.value); }

defineExpose({ getFormData: () => form.value, form });
</script>

<style scoped>
.frais-form {
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
