<!--
  BibliothequeForm.vue — Fix Phase 4.6 (§11.8).
  Historique : form envoyait {nom, code, statut} — décorrélé du fillable réel
  {ecole_id, nom, adresse, capacite, responsable_id, etat}. Les colonnes
  code/localisation/horaire_ouverture/horaire_fermeture inventées par le controller
  n'existent pas dans la table `bibliotheques` (recréée pour RL — voir §11.1).

  Refonte : aligné exactement sur schéma DB.
-->

<template>
    <form @submit.prevent="submit" class="bibliotheque-form">
        <div class="row g-3">
            <div class="col-md-6">
                <label>Nom <span class="text-danger">*</span></label>
                <input
                    v-model="form.nom"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.nom }"
                    :disabled="isReadOnly"
                    maxlength="125"
                    required
                />
                <div v-if="errors.nom" class="invalid-feedback">{{ errors.nom[0] || errors.nom }}</div>
            </div>
            <div class="col-md-6">
                <label>École</label>
                <select
                    v-model.number="form.ecole_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.ecole_id }"
                    :disabled="isReadOnly"
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="e in ecoles" :key="e.id" :value="e.id">{{ e.nom }}</option>
                </select>
                <div v-if="errors.ecole_id" class="invalid-feedback">{{ errors.ecole_id[0] || errors.ecole_id }}</div>
            </div>

            <div class="col-md-6">
                <label>Responsable</label>
                <select
                    v-model.number="form.responsable_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.responsable_id }"
                    :disabled="isReadOnly"
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="r in responsables" :key="r.id" :value="r.id">
                        {{ r.nom }} {{ r.prenoms || '' }}
                    </option>
                </select>
                <div v-if="errors.responsable_id" class="invalid-feedback">{{ errors.responsable_id[0] || errors.responsable_id }}</div>
            </div>
            <div class="col-md-6">
                <label>Capacité (places)</label>
                <input
                    v-model.number="form.capacite"
                    type="number"
                    min="0"
                    class="form-control"
                    :class="{ 'is-invalid': errors.capacite }"
                    :disabled="isReadOnly"
                />
                <div v-if="errors.capacite" class="invalid-feedback">{{ errors.capacite[0] || errors.capacite }}</div>
            </div>

            <div class="col-md-8">
                <label>Adresse</label>
                <input
                    v-model="form.adresse"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.adresse }"
                    :disabled="isReadOnly"
                    maxlength="255"
                />
                <div v-if="errors.adresse" class="invalid-feedback">{{ errors.adresse[0] || errors.adresse }}</div>
            </div>
            <div class="col-md-4">
                <label>État <span class="text-danger">*</span></label>
                <select
                    v-model="form.etat"
                    class="form-control"
                    :class="{ 'is-invalid': errors.etat }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="actif">Actif</option>
                    <option value="inactif">Inactif</option>
                </select>
                <div v-if="errors.etat" class="invalid-feedback">{{ errors.etat[0] || errors.etat }}</div>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('bibliotheques.index')" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    bibliotheque:      { type: Object,  default: () => ({}) },
    ecoles:            { type: Array,   default: () => [] },
    responsables:      { type: Array,   default: () => [] },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

const form = ref({
    ecole_id:       props.bibliotheque?.ecole_id       || '',
    nom:            props.bibliotheque?.nom            || '',
    adresse:        props.bibliotheque?.adresse        || '',
    capacite:       props.bibliotheque?.capacite       || 0,
    responsable_id: props.bibliotheque?.responsable_id || '',
    etat:           props.bibliotheque?.etat           || 'actif',
});

function submit() { emit('submit', form.value); }

defineExpose({ getFormData: () => form.value, form });
</script>

<style scoped>
.bibliotheque-form {
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
