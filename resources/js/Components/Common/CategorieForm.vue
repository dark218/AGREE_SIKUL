<!--
  CategorieForm.vue — Composant partagé §12.
  Form générique pour tout référentiel "Catégorie" simple (code, libellé,
  description, ordre, etat).

  Utilisation :
    <CategorieForm
      :form="form"
      titre="Catégorie d'équipement"
      :errors="form.errors"
      :is-read-only="isReadOnly"
      route-index="parametrage.categories-equipements.index"
      @submit="submitForm"
    />

  Utilisé par : CategorieEquipement, CategorieFourniture, CategorieDocument.
  Économise ~100 lignes.
-->

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    form:              { type: Object,  required: true },
    titre:             { type: String,  default: 'Catégorie' },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    routeIndex:        { type: String,  required: true },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
    // Certains référentiels n'ont pas de `code` (ex: CategorieEquipement),
    // dans ce cas passer `show-code=false`.
    showCode:          { type: Boolean, default: true },
    showOrdre:         { type: Boolean, default: false },
    showEtat:          { type: Boolean, default: false },
});

const emit = defineEmits(['submit']);

function submit() { emit('submit', props.form); }
</script>

<template>
    <form @submit.prevent="submit" class="categorie-form">
        <div class="row g-3">
            <div v-if="showCode" class="col-md-4">
                <label>Code <span class="text-danger">*</span></label>
                <input
                    v-model="form.code"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.code }"
                    :disabled="isReadOnly"
                    placeholder="Code unique"
                    required
                />
                <div v-if="errors.code" class="invalid-feedback">{{ errors.code[0] || errors.code }}</div>
            </div>
            <div :class="showCode ? 'col-md-8' : 'col-12'">
                <label>Libellé <span class="text-danger">*</span></label>
                <input
                    v-model="form.libelle"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.libelle }"
                    :disabled="isReadOnly"
                    :placeholder="`Libellé du ${titre.toLowerCase()}`"
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
                    rows="2"
                    placeholder="Description (optionnel)"
                ></textarea>
                <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] || errors.description }}</div>
            </div>

            <div v-if="showOrdre" class="col-md-4">
                <label>Ordre d'affichage</label>
                <input v-model.number="form.ordre" :disabled="isReadOnly" type="number" min="0" class="form-control" />
            </div>
            <div v-if="showEtat" class="col-md-4">
                <label>État</label>
                <select v-model="form.etat" :disabled="isReadOnly" class="form-control">
                    <option value="actif">Actif</option>
                    <option value="inactif">Inactif</option>
                </select>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route(routeIndex)" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<style scoped>
.categorie-form {
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
