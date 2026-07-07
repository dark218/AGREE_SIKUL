<!--
  TypeFraisForm.vue — Fix Phase 4.6 (§11.8).
  Historique : form envoyait {nom, code, statut} — décorrélé du fillable réel
  {code, libelle, description, montant_cents, obligatoire}. La colonne `statut`
  n'existe pas dans la table `types_frais`.

  Refonte : aligné exactement sur le schéma DB + validator du controller.
-->

<template>
    <form @submit.prevent="submit" class="types-frais-form">
        <div class="row g-3">
            <div class="col-md-4">
                <label>Code <span class="text-danger">*</span></label>
                <input
                    v-model="form.code"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.code }"
                    :disabled="isReadOnly"
                    placeholder="Ex : SCOL, INSC, CANT…"
                    required
                />
                <div v-if="errors.code" class="invalid-feedback">{{ errors.code[0] || errors.code }}</div>
            </div>
            <div class="col-md-8">
                <label>Libellé <span class="text-danger">*</span></label>
                <input
                    v-model="form.libelle"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.libelle }"
                    :disabled="isReadOnly"
                    placeholder="Nom lisible du type de frais"
                    required
                />
                <div v-if="errors.libelle" class="invalid-feedback">{{ errors.libelle[0] || errors.libelle }}</div>
            </div>

            <div class="col-md-6">
                <label>Montant par défaut (€)</label>
                <input
                    v-model.number="montantEuros"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :class="{ 'is-invalid': errors.montant_cents }"
                    :disabled="isReadOnly"
                    placeholder="0,00"
                />
                <small class="text-muted">Enregistré en centimes ({{ form.montant_cents || 0 }} c.)</small>
                <div v-if="errors.montant_cents" class="invalid-feedback">{{ errors.montant_cents[0] || errors.montant_cents }}</div>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input
                        v-model="form.obligatoire"
                        type="checkbox"
                        class="form-check-input"
                        id="obligatoire"
                        :disabled="isReadOnly"
                    />
                    <label class="form-check-label" for="obligatoire">
                        <i class="fa fa-check-circle me-1"></i> Frais obligatoire
                    </label>
                </div>
            </div>

            <div class="col-12">
                <label>Description</label>
                <textarea
                    v-model="form.description"
                    class="form-control"
                    :class="{ 'is-invalid': errors.description }"
                    :disabled="isReadOnly"
                    rows="2"
                    placeholder="Détails complémentaires (optionnel)"
                ></textarea>
                <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] || errors.description }}</div>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('finances.types-frais.index')" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    typeFrais:         { type: Object,  default: () => ({}) },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

const form = ref({
    code:          props.typeFrais?.code          || '',
    libelle:       props.typeFrais?.libelle       || '',
    description:   props.typeFrais?.description   || '',
    montant_cents: props.typeFrais?.montant_cents || 0,
    obligatoire:   props.typeFrais?.obligatoire ?? true,
});

const montantEuros = computed({
    get: () => form.value.montant_cents ? (form.value.montant_cents / 100) : 0,
    set: (v) => { form.value.montant_cents = Math.round((Number(v) || 0) * 100); },
});

function submit() { emit('submit', form.value); }

defineExpose({ getFormData: () => form.value, form });
</script>

<style scoped>
.types-frais-form {
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
