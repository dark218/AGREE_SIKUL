<!--
  CategorieEquipementForm.vue — Fix Phase 4.6 (§11.8).
  Historique : form envoyait {nom, code, statut} — décorrélé du fillable réel
  {libelle, description}. La table categories_equipements n'a ni code ni statut.

  Refonte : aligné exactement sur schéma DB.
-->

<template>
    <form @submit.prevent="submit" class="categorie-equipement-form">
        <div class="row g-3">
            <div class="col-12">
                <label>Libellé <span class="text-danger">*</span></label>
                <input
                    v-model="form.libelle"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.libelle }"
                    :disabled="isReadOnly"
                    placeholder="Nom de la catégorie (unique)"
                    required
                />
                <div v-if="errors.libelle" class="invalid-feedback">{{ errors.libelle[0] || errors.libelle }}</div>
            </div>
            <div class="col-12">
                <label>Description</label>
                <textarea
                    v-model="form.description"
                    class="form-control"
                    :class="{ 'is-invalid': errors.description }"
                    :disabled="isReadOnly"
                    rows="3"
                    placeholder="Optionnel"
                ></textarea>
                <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] || errors.description }}</div>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('categories-equipements.index')" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    categorie:         { type: Object,  default: () => ({}) },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

const form = ref({
    libelle:     props.categorie?.libelle     || '',
    description: props.categorie?.description || '',
});

function submit() { emit('submit', form.value); }

defineExpose({ getFormData: () => form.value, form });
</script>

<style scoped>
.categorie-equipement-form {
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
