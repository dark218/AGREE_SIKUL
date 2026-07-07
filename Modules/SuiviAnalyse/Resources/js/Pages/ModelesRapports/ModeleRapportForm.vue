<!--
  ModeleRapportForm.vue — Fix Phase 4.6 (§11.8).
  Historique : form envoyait {nom, code, statut} — décorrélé du fillable réel
  {code, titre, description, type, parametres (json)}.

  Refonte : aligné exactement sur schéma DB. `parametres` est un JSON — saisi
  en texte libre au format JSON, parsé côté client.
-->

<template>
    <form @submit.prevent="submit" class="modele-rapport-form">
        <div class="row g-3">
            <div class="col-md-4">
                <label>Code <span class="text-danger">*</span></label>
                <input
                    v-model="form.code"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.code }"
                    :disabled="isReadOnly"
                    placeholder="Code unique (ex : RAPPORT_MENSUEL)"
                    required
                />
                <div v-if="errors.code" class="invalid-feedback">{{ errors.code[0] || errors.code }}</div>
            </div>
            <div class="col-md-8">
                <label>Titre <span class="text-danger">*</span></label>
                <input
                    v-model="form.titre"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.titre }"
                    :disabled="isReadOnly"
                    placeholder="Nom lisible"
                    required
                />
                <div v-if="errors.titre" class="invalid-feedback">{{ errors.titre[0] || errors.titre }}</div>
            </div>

            <div class="col-md-4">
                <label>Type <span class="text-danger">*</span></label>
                <select
                    v-model="form.type"
                    class="form-control"
                    :class="{ 'is-invalid': errors.type }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                    <option value="csv">CSV</option>
                    <option value="html">HTML</option>
                </select>
                <div v-if="errors.type" class="invalid-feedback">{{ errors.type[0] || errors.type }}</div>
            </div>
            <div class="col-md-8">
                <label>Description</label>
                <textarea
                    v-model="form.description"
                    class="form-control"
                    :class="{ 'is-invalid': errors.description }"
                    :disabled="isReadOnly"
                    rows="2"
                ></textarea>
                <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] || errors.description }}</div>
            </div>

            <div class="col-12">
                <label>
                    Paramètres (JSON)
                    <small class="text-muted">— définitions des variables du modèle</small>
                </label>
                <textarea
                    v-model="parametresJson"
                    class="form-control font-monospace"
                    :class="{ 'is-invalid': errors.parametres || parametresError }"
                    :disabled="isReadOnly"
                    rows="4"
                    placeholder='{ "date_debut": "date", "date_fin": "date", "ecole_id": "integer" }'
                ></textarea>
                <small v-if="parametresError" class="text-danger d-block">JSON invalide : {{ parametresError }}</small>
                <div v-if="errors.parametres" class="invalid-feedback">{{ errors.parametres[0] || errors.parametres }}</div>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary" :disabled="!!parametresError">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('suivi-analyse.modeles-rapports.index')" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    modele:            { type: Object,  default: () => ({}) },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

const form = ref({
    code:        props.modele?.code        || '',
    titre:       props.modele?.titre       || '',
    description: props.modele?.description || '',
    type:        props.modele?.type        || 'pdf',
    parametres:  props.modele?.parametres  || {},
});

const parametresJson = ref(JSON.stringify(form.value.parametres, null, 2));
const parametresError = ref('');

watch(parametresJson, (v) => {
    if (!v.trim()) {
        form.value.parametres = {};
        parametresError.value = '';
        return;
    }
    try {
        form.value.parametres = JSON.parse(v);
        parametresError.value = '';
    } catch (e) {
        parametresError.value = e.message;
    }
});

function submit() {
    if (parametresError.value) return;
    emit('submit', form.value);
}

defineExpose({ getFormData: () => form.value, form });
</script>

<style scoped>
.modele-rapport-form {
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
