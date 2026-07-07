<!--
  DepenseForm.vue — Fix Phase 4.5 (§11.7).
  Historique : form envoyait {nom, code, statut} — 0 champ ne matchait
  l'entité (fillable = ecole_id, categorie, libelle, montant_cents,
  date_depense, facture_id, auteur_id). Résultat : SQL error à la création
  ("Field 'libelle' doesn't have a default value").

  Refonte : form aligné exactement sur les vraies colonnes DB.
  auteur_id est injecté côté serveur (= utilisateur courant).
-->

<template>
    <form @submit.prevent="submit" class="depense-form">
        <div class="row g-3">
            <div class="col-md-6">
                <label>{{ t('common.libelle') || 'Libellé' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.libelle"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.libelle }"
                    :disabled="isReadOnly"
                    required
                />
                <div v-if="errors.libelle" class="invalid-feedback">{{ errors.libelle[0] || errors.libelle }}</div>
            </div>
            <div class="col-md-6">
                <label>{{ t('common.categorie') || 'Catégorie' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.categorie"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.categorie }"
                    :disabled="isReadOnly"
                    placeholder="Ex : Fournitures, Salaires, Entretien..."
                    required
                />
                <div v-if="errors.categorie" class="invalid-feedback">{{ errors.categorie[0] || errors.categorie }}</div>
            </div>
            <div class="col-md-4">
                <label>Montant (en €) <span class="text-danger">*</span></label>
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
                <small class="text-muted">Enregistré en centimes en base ({{ form.montant_cents || 0 }} c.)</small>
                <div v-if="errors.montant_cents" class="invalid-feedback">{{ errors.montant_cents[0] || errors.montant_cents }}</div>
            </div>
            <div class="col-md-4">
                <label>Date de la dépense <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_depense"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_depense }"
                    :disabled="isReadOnly"
                    required
                />
                <div v-if="errors.date_depense" class="invalid-feedback">{{ errors.date_depense[0] || errors.date_depense }}</div>
            </div>
            <div class="col-md-4">
                <label>École <span class="text-danger">*</span></label>
                <select
                    v-model.number="form.ecole_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.ecole_id }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="e in ecoles" :key="e.id" :value="e.id">{{ e.nom }}</option>
                </select>
                <div v-if="errors.ecole_id" class="invalid-feedback">{{ errors.ecole_id[0] || errors.ecole_id }}</div>
            </div>
        </div>
        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('finances.depenses.index')" class="btn btn-outline-secondary ms-2">
                {{ t('common.cancel') || 'Annuler' }}
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';

const { t } = useI18n();

const props = defineProps({
    depense:           { type: Object,  default: () => ({}) },
    ecoles:            { type: Array,   default: () => [] },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

const form = ref({
    ecole_id:      props.depense?.ecole_id      || '',
    libelle:       props.depense?.libelle       || '',
    categorie:     props.depense?.categorie     || '',
    montant_cents: props.depense?.montant_cents || 0,
    date_depense:  props.depense?.date_depense
        ? String(props.depense.date_depense).split('T')[0].split(' ')[0]
        : '',
    facture_id:    props.depense?.facture_id    || null,
});

// Bridge montant_cents ↔ euros pour saisie confortable.
const montantEuros = computed({
    get: () => form.value.montant_cents ? (form.value.montant_cents / 100) : 0,
    set: (v) => { form.value.montant_cents = Math.round((Number(v) || 0) * 100); },
});

function submit() {
    emit('submit', form.value);
}

defineExpose({ getFormData: () => form.value, form });
</script>

<style scoped>
.depense-form {
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
