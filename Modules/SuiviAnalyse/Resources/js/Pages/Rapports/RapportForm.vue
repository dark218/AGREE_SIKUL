<!--
  RapportForm.vue — Fix Phase 4.6 (§11.8).
  Historique : form envoyait {nom, code, statut} — décorrélé du fillable réel
  {modele_rapport_id, titre, parametres_utilises (json), fichier_id, genere_par, date_generation}.

  Refonte : aligné exactement sur schéma DB. `genere_par` = utilisateur courant,
  injecté côté serveur. `parametres_utilises` en JSON libre.
-->

<template>
    <form @submit.prevent="submit" class="rapport-form">
        <div class="row g-3">
            <div class="col-md-8">
                <label>Titre <span class="text-danger">*</span></label>
                <input
                    v-model="form.titre"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.titre }"
                    :disabled="isReadOnly"
                    placeholder="Titre du rapport généré"
                    required
                />
                <div v-if="errors.titre" class="invalid-feedback">{{ errors.titre[0] || errors.titre }}</div>
            </div>
            <div class="col-md-4">
                <label>Modèle <span class="text-danger">*</span></label>
                <select
                    v-model.number="form.modele_rapport_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.modele_rapport_id }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="">-- Sélectionner --</option>
                    <option v-for="m in modeles" :key="m.id" :value="m.id">
                        {{ m.titre }} <span v-if="m.code">({{ m.code }})</span>
                    </option>
                </select>
                <div v-if="errors.modele_rapport_id" class="invalid-feedback">{{ errors.modele_rapport_id[0] || errors.modele_rapport_id }}</div>
            </div>

            <div class="col-md-6">
                <label>Date de génération</label>
                <input
                    v-model="form.date_generation"
                    type="datetime-local"
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_generation }"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">Vide = maintenant (côté serveur)</small>
                <div v-if="errors.date_generation" class="invalid-feedback">{{ errors.date_generation[0] || errors.date_generation }}</div>
            </div>
            <div class="col-md-6">
                <label>Fichier attaché (ID)</label>
                <input
                    v-model.number="form.fichier_id"
                    type="number"
                    min="1"
                    class="form-control"
                    :class="{ 'is-invalid': errors.fichier_id }"
                    :disabled="isReadOnly"
                    placeholder="Optionnel"
                />
                <div v-if="errors.fichier_id" class="invalid-feedback">{{ errors.fichier_id[0] || errors.fichier_id }}</div>
            </div>

            <div class="col-12">
                <label>
                    Paramètres utilisés (JSON)
                    <small class="text-muted">— valeurs des variables du modèle pour cette génération</small>
                </label>
                <textarea
                    v-model="parametresJson"
                    class="form-control font-monospace"
                    :class="{ 'is-invalid': errors.parametres_utilises || parametresError }"
                    :disabled="isReadOnly"
                    rows="4"
                    placeholder='{ "date_debut": "2026-01-01", "date_fin": "2026-06-30" }'
                ></textarea>
                <small v-if="parametresError" class="text-danger d-block">JSON invalide : {{ parametresError }}</small>
                <div v-if="errors.parametres_utilises" class="invalid-feedback">{{ errors.parametres_utilises[0] || errors.parametres_utilises }}</div>
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary" :disabled="!!parametresError">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('suivi-analyse.rapports.index')" class="btn btn-outline-secondary ms-2">
                Annuler
            </Link>
        </div>
    </form>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    rapport:           { type: Object,  default: () => ({}) },
    modeles:           { type: Array,   default: () => [] },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});

const emit = defineEmits(['submit']);

const formatDT = (v) => {
    if (!v) return '';
    const s = String(v).replace('Z', '');
    return s.length >= 16 ? s.substring(0, 16) : s;
};

const form = ref({
    modele_rapport_id:   props.rapport?.modele_rapport_id   || '',
    titre:               props.rapport?.titre               || '',
    parametres_utilises: props.rapport?.parametres_utilises || {},
    fichier_id:          props.rapport?.fichier_id          || '',
    date_generation:     formatDT(props.rapport?.date_generation),
});

const parametresJson = ref(JSON.stringify(form.value.parametres_utilises, null, 2));
const parametresError = ref('');

watch(parametresJson, (v) => {
    if (!v.trim()) {
        form.value.parametres_utilises = {};
        parametresError.value = '';
        return;
    }
    try {
        form.value.parametres_utilises = JSON.parse(v);
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
.rapport-form {
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
