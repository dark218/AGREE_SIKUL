<!--
  BibliothequeStructureForm.vue — Bibliothèque › Liste.
  Champs (spec) : Code, Libellé, Localisation, Campus, Responsable, Statut de disponibilité, État.
-->
<template>
    <form @submit.prevent="submit" class="rl-form">
        <div class="row g-3">
            <div class="col-md-4">
                <label>Code</label>
                <input v-model="form.code" type="text" class="form-control" maxlength="100" :disabled="isReadOnly" placeholder="Ex : BIB-01" />
                <small v-if="errors.code" class="text-danger">{{ errors.code[0] || errors.code }}</small>
            </div>
            <div class="col-md-8">
                <label>Libellé <span class="text-danger">*</span></label>
                <input v-model="form.libelle" type="text" class="form-control" maxlength="255" required :disabled="isReadOnly" placeholder="Nom de la bibliothèque" />
                <small v-if="errors.libelle" class="text-danger">{{ errors.libelle[0] || errors.libelle }}</small>
            </div>

            <div class="col-md-6">
                <label>Localisation</label>
                <input v-model="form.localisation" type="text" class="form-control" maxlength="255" :disabled="isReadOnly" placeholder="Bâtiment / salle / adresse" />
                <small v-if="errors.localisation" class="text-danger">{{ errors.localisation[0] || errors.localisation }}</small>
            </div>
            <div class="col-md-6">
                <label>Campus</label>
                <SearchableSelect
                    v-model="form.campus_id"
                    :options="campuses"
                    option-value="id"
                    option-label="libelle"
                    placeholder="-- Sélectionner un campus --"
                    :disabled="isReadOnly"
                />
                <small v-if="errors.campus_id" class="text-danger">{{ errors.campus_id[0] || errors.campus_id }}</small>
            </div>

            <div class="col-md-6">
                <label>Responsable</label>
                <input v-model="form.responsable" type="text" class="form-control" maxlength="255" :disabled="isReadOnly" placeholder="Nom du responsable" />
                <small v-if="errors.responsable" class="text-danger">{{ errors.responsable[0] || errors.responsable }}</small>
            </div>
            <div class="col-md-3">
                <label>Statut de disponibilité</label>
                <SearchableSelect
                    v-model="form.statut_disponibilite"
                    :options="dispoOptions"
                    option-value="id"
                    option-label="libelle"
                    :disabled="isReadOnly"
                />
            </div>
            <div class="col-md-3">
                <label>État <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="etatOptions"
                    option-value="id"
                    option-label="libelle"
                    :disabled="isReadOnly"
                />
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ submitButtonLabel }}</button>
            <Link :href="route('bibliotheque-structures.index')" class="btn btn-outline-secondary ms-2">Annuler</Link>
        </div>
    </form>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const props = defineProps({
    structure:         { type: Object,  default: () => ({}) },
    campuses:          { type: Array,   default: () => [] },
    errors:            { type: Object,  default: () => ({}) },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});
const emit = defineEmits(['submit']);

const dispoOptions = [
    { id: 'disponible',   libelle: 'Disponible' },
    { id: 'indisponible', libelle: 'Indisponible' },
    { id: 'maintenance',  libelle: 'Maintenance' },
];
const etatOptions = [
    { id: 'actif',   libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const form = ref({
    code:                 props.structure?.code || '',
    libelle:              props.structure?.libelle || '',
    localisation:         props.structure?.localisation || '',
    campus_id:            props.structure?.campus_id || '',
    responsable:          props.structure?.responsable || '',
    statut_disponibilite: props.structure?.statut_disponibilite || 'disponible',
    etat:                 props.structure?.etat || 'actif',
});

function submit() { emit('submit', form.value); }
</script>

<style scoped>
.rl-form { background:#fff; padding:20px; border-radius:6px; box-shadow:0 2px 4px rgba(0,0,0,.08); }
label { font-weight:500; color:#374151; font-size:.9rem; margin-bottom:.4rem; display:block; }
.form-actions { display:flex; gap:10px; padding-top:20px; border-top:1px solid #dee2e6; }
</style>
