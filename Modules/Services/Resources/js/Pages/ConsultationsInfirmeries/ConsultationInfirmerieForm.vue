<!--
  ConsultationInfirmerieForm.vue — Fix Phase 4.6 (§11.8).
  Historique : form envoyait {nom, code, statut} — décorrélé du fillable réel
  {apprenant_id, date_consultation, motif, diagnostic, traitement, infirmier_id}.

  Refonte : aligné exactement sur schéma DB + validator du controller.
-->

<template>
    <form @submit.prevent="submit" class="consultation-infirmerie-form">
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
                <label>Infirmier</label>
                <select
                    v-model.number="form.infirmier_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.infirmier_id }"
                    :disabled="isReadOnly"
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="i in infirmiers" :key="i.id" :value="i.id">
                        {{ i.nom }} {{ i.prenoms || '' }}
                    </option>
                </select>
                <div v-if="errors.infirmier_id" class="invalid-feedback">{{ errors.infirmier_id[0] || errors.infirmier_id }}</div>
            </div>

            <div class="col-md-6">
                <label>Date et heure de consultation <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_consultation"
                    type="datetime-local"
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_consultation }"
                    :disabled="isReadOnly"
                    required
                />
                <div v-if="errors.date_consultation" class="invalid-feedback">{{ errors.date_consultation[0] || errors.date_consultation }}</div>
            </div>
            <div class="col-md-6">
                <label>Motif <span class="text-danger">*</span></label>
                <input
                    v-model="form.motif"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.motif }"
                    :disabled="isReadOnly"
                    placeholder="Ex : Fièvre, migraine, blessure…"
                    required
                />
                <div v-if="errors.motif" class="invalid-feedback">{{ errors.motif[0] || errors.motif }}</div>
            </div>

            <div class="col-md-6">
                <label>Diagnostic</label>
                <textarea
                    v-model="form.diagnostic"
                    class="form-control"
                    :class="{ 'is-invalid': errors.diagnostic }"
                    :disabled="isReadOnly"
                    rows="3"
                    placeholder="Observations cliniques…"
                ></textarea>
                <div v-if="errors.diagnostic" class="invalid-feedback">{{ errors.diagnostic[0] || errors.diagnostic }}</div>
            </div>
            <div class="col-md-6">
                <label>Traitement</label>
                <textarea
                    v-model="form.traitement"
                    class="form-control"
                    :class="{ 'is-invalid': errors.traitement }"
                    :disabled="isReadOnly"
                    rows="3"
                    placeholder="Médicaments, soins, orientations…"
                ></textarea>
                <div v-if="errors.traitement" class="invalid-feedback">{{ errors.traitement[0] || errors.traitement }}</div>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('consultations-infirmerie.index')" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    consultation:      { type: Object,  default: () => ({}) },
    apprenants:        { type: Array,   default: () => [] },
    infirmiers:        { type: Array,   default: () => [] },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

// Format datetime pour input datetime-local (YYYY-MM-DDTHH:MM).
const formatForInput = (v) => {
    if (!v) return '';
    const s = String(v).replace('Z', '');
    return s.length >= 16 ? s.substring(0, 16) : s;
};

const form = ref({
    apprenant_id:      props.consultation?.apprenant_id      || '',
    infirmier_id:      props.consultation?.infirmier_id      || '',
    date_consultation: formatForInput(props.consultation?.date_consultation),
    motif:             props.consultation?.motif             || '',
    diagnostic:        props.consultation?.diagnostic        || '',
    traitement:        props.consultation?.traitement        || '',
});

function submit() { emit('submit', form.value); }

defineExpose({ getFormData: () => form.value, form });
</script>

<style scoped>
.consultation-infirmerie-form {
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
