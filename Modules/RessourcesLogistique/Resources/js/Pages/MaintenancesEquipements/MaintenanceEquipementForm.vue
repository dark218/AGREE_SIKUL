<!--
  MaintenanceEquipementForm.vue — Fix Phase 4.6 (§11.8).
  Historique : form envoyait {nom, code, statut} — décorrélé du fillable réel
  {equipement_id, date_maintenance, type_maintenance, description, cout_cents, technicien_id}.

  Refonte : aligné exactement sur schéma DB + validator du controller.
  Note : le controller demandait titre/date_debut/date_fin/observations/statut/type
  qui n'existent pas en DB. Enum type_maintenance : preventive/corrective/inspection.
-->

<template>
    <form @submit.prevent="submit" class="maintenance-equipement-form">
        <div class="row g-3">
            <div class="col-md-8">
                <label>Équipement <span class="text-danger">*</span></label>
                <select
                    v-model.number="form.equipement_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.equipement_id }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="e in equipements" :key="e.id" :value="e.id">{{ e.nom }}</option>
                </select>
                <div v-if="errors.equipement_id" class="invalid-feedback">{{ errors.equipement_id[0] || errors.equipement_id }}</div>
            </div>
            <div class="col-md-4">
                <label>Type <span class="text-danger">*</span></label>
                <select
                    v-model="form.type_maintenance"
                    class="form-control"
                    :class="{ 'is-invalid': errors.type_maintenance }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="preventive">Préventive</option>
                    <option value="corrective">Corrective</option>
                    <option value="inspection">Inspection</option>
                </select>
                <div v-if="errors.type_maintenance" class="invalid-feedback">{{ errors.type_maintenance[0] || errors.type_maintenance }}</div>
            </div>

            <div class="col-md-6">
                <label>Date de maintenance <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_maintenance"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_maintenance }"
                    :disabled="isReadOnly"
                    required
                />
                <div v-if="errors.date_maintenance" class="invalid-feedback">{{ errors.date_maintenance[0] || errors.date_maintenance }}</div>
            </div>
            <div class="col-md-6">
                <label>Technicien</label>
                <select
                    v-model.number="form.technicien_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.technicien_id }"
                    :disabled="isReadOnly"
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="t in techniciens" :key="t.id" :value="t.id">{{ t.nom }} {{ t.prenoms || '' }}</option>
                </select>
                <div v-if="errors.technicien_id" class="invalid-feedback">{{ errors.technicien_id[0] || errors.technicien_id }}</div>
            </div>

            <div class="col-md-4">
                <label>Coût (€)</label>
                <input
                    v-model.number="coutEuros"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :class="{ 'is-invalid': errors.cout_cents }"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">{{ form.cout_cents || 0 }} centimes</small>
                <div v-if="errors.cout_cents" class="invalid-feedback">{{ errors.cout_cents[0] || errors.cout_cents }}</div>
            </div>
            <div class="col-md-8">
                <label>Description</label>
                <textarea
                    v-model="form.description"
                    class="form-control"
                    :class="{ 'is-invalid': errors.description }"
                    :disabled="isReadOnly"
                    rows="2"
                    placeholder="Description de l'intervention…"
                ></textarea>
                <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] || errors.description }}</div>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('maintenances-equipements.index')" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    maintenance:       { type: Object,  default: () => ({}) },
    equipements:       { type: Array,   default: () => [] },
    techniciens:       { type: Array,   default: () => [] },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

const form = ref({
    equipement_id:    props.maintenance?.equipement_id    || '',
    date_maintenance: props.maintenance?.date_maintenance
        ? String(props.maintenance.date_maintenance).split('T')[0].split(' ')[0]
        : new Date().toISOString().split('T')[0],
    type_maintenance: props.maintenance?.type_maintenance || 'corrective',
    description:      props.maintenance?.description      || '',
    cout_cents:       props.maintenance?.cout_cents       || 0,
    technicien_id:    props.maintenance?.technicien_id    || '',
});

const coutEuros = computed({
    get: () => form.value.cout_cents ? form.value.cout_cents / 100 : 0,
    set: (v) => { form.value.cout_cents = Math.round((Number(v) || 0) * 100); },
});

function submit() { emit('submit', form.value); }

defineExpose({ getFormData: () => form.value, form });
</script>

<style scoped>
.maintenance-equipement-form {
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
