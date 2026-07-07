<!--
  PassageCantineForm.vue — Fix Phase 4.6 (§11.8).
  Historique : form envoyait {nom, code, statut} — décorrélé du fillable réel
  {inscription_cantine_id, date_passage, heure_passage}. Aucun de ces champs
  n'existait — plus les colonnes menu_id/apprenant_id/montant_cents inventées
  par le controller.

  Refonte : aligné exactement sur schéma DB.
-->

<template>
    <form @submit.prevent="submit" class="passage-cantine-form">
        <div class="row g-3">
            <div class="col-12">
                <label>Inscription cantine <span class="text-danger">*</span></label>
                <select
                    v-model.number="form.inscription_cantine_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.inscription_cantine_id }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="">-- Sélectionner l'inscription concernée --</option>
                    <option v-for="i in inscriptions" :key="i.id" :value="i.id">
                        {{ i.apprenant?.nom }} {{ i.apprenant?.prenoms || '' }}
                        <template v-if="i.service_cantine?.nom">— {{ i.service_cantine.nom }}</template>
                    </option>
                </select>
                <div v-if="errors.inscription_cantine_id" class="invalid-feedback">{{ errors.inscription_cantine_id[0] || errors.inscription_cantine_id }}</div>
            </div>

            <div class="col-md-6">
                <label>Date du passage <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_passage"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_passage }"
                    :disabled="isReadOnly"
                    required
                />
                <div v-if="errors.date_passage" class="invalid-feedback">{{ errors.date_passage[0] || errors.date_passage }}</div>
            </div>
            <div class="col-md-6">
                <label>Heure du passage</label>
                <input
                    v-model="form.heure_passage"
                    type="time"
                    class="form-control"
                    :class="{ 'is-invalid': errors.heure_passage }"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">Facultatif</small>
                <div v-if="errors.heure_passage" class="invalid-feedback">{{ errors.heure_passage[0] || errors.heure_passage }}</div>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('passages-cantine.index')" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    passage:           { type: Object,  default: () => ({}) },
    inscriptions:      { type: Array,   default: () => [] },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

const form = ref({
    inscription_cantine_id: props.passage?.inscription_cantine_id || '',
    date_passage:           props.passage?.date_passage
        ? String(props.passage.date_passage).split('T')[0].split(' ')[0]
        : new Date().toISOString().split('T')[0],
    heure_passage:          props.passage?.heure_passage
        ? String(props.passage.heure_passage).substring(0, 5)
        : '',
});

function submit() { emit('submit', form.value); }

defineExpose({ getFormData: () => form.value, form });
</script>

<style scoped>
.passage-cantine-form {
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
