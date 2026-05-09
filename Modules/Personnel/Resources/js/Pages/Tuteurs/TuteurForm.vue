<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    mode: { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
    apprenants: { type: Array, default: () => [] },
});
const isReadOnly = props.mode === 'show';
</script>
<template>
    <div class="row g-3 custom-input">
        <div class="col-sm-6">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input v-model="form.nom" type="text" class="form-control form-control-sm" :disabled="isReadOnly" placeholder="Nom du tuteur" />
            <div v-if="form.errors?.nom" class="text-danger small mt-1">{{ form.errors.nom }}</div>
        </div>
        <div class="col-sm-6">
            <label class="form-label">Prénom(s)</label>
            <input v-model="form.prenoms" type="text" class="form-control form-control-sm" :disabled="isReadOnly" placeholder="Prénom(s)" />
            <div v-if="form.errors?.prenoms" class="text-danger small mt-1">{{ form.errors.prenoms }}</div>
        </div>
        <div class="col-sm-6">
            <label class="form-label">Téléphone</label>
            <input v-model="form.telephone" type="text" class="form-control form-control-sm" :disabled="isReadOnly" placeholder="Numéro de téléphone" />
            <div v-if="form.errors?.telephone" class="text-danger small mt-1">{{ form.errors.telephone }}</div>
        </div>
        <div class="col-sm-6">
            <label class="form-label">Numéro d'urgence</label>
            <input v-model="form.numero_urgence" type="text" class="form-control form-control-sm" :disabled="isReadOnly" placeholder="Numéro d'urgence" />
        </div>
        <div class="col-sm-6">
            <label class="form-label">Email</label>
            <input v-model="form.email" type="email" class="form-control form-control-sm" :disabled="isReadOnly" placeholder="Email" />
            <div v-if="form.errors?.email" class="text-danger small mt-1">{{ form.errors.email }}</div>
        </div>
        <div class="col-sm-6">
            <label class="form-label">Adresse</label>
            <input v-model="form.adresse" type="text" class="form-control form-control-sm" :disabled="isReadOnly" placeholder="Adresse" />
        </div>
        <div class="col-sm-6">
            <label class="form-label">Apprenant</label>
            <SearchableSelect v-model="form.apprenant_id" :options="apprenants" optionValue="id" optionLabel="libelle" placeholder="Sélectionner un apprenant" class="form-control-sm" :disabled="isReadOnly" />
            <div v-if="form.errors?.apprenant_id" class="text-danger small mt-1">{{ form.errors.apprenant_id }}</div>
        </div>
        <div class="col-sm-6">
            <label class="form-label">Relation</label>
            <SearchableSelect v-model="form.relation" :options="[
                { id: 'pere', libelle: 'Père' }, { id: 'mere', libelle: 'Mère' },
                { id: 'tuteur_legal', libelle: 'Tuteur légal' }, { id: 'grand_parent', libelle: 'Grand-parent' },
                { id: 'oncle', libelle: 'Oncle' }, { id: 'tante', libelle: 'Tante' },
                { id: 'frere', libelle: 'Frère' }, { id: 'soeur', libelle: 'Sœur' },
                { id: 'cousin', libelle: 'Cousin' }, { id: 'cousine', libelle: 'Cousine' },
                { id: 'autre', libelle: 'Autre' },
            ]" optionValue="id" optionLabel="libelle" placeholder="Sélectionner la relation" class="form-control-sm" :disabled="isReadOnly" />
            <div v-if="form.errors?.relation" class="text-danger small mt-1">{{ form.errors.relation }}</div>
        </div>
        <div class="col-sm-6">
            <label class="form-label">Profession</label>
            <input v-model="form.profession" type="text" class="form-control form-control-sm" :disabled="isReadOnly" placeholder="Profession" />
        </div>
        <div class="col-sm-6">
            <label class="form-label">Employeur</label>
            <input v-model="form.employeur" type="text" class="form-control form-control-sm" :disabled="isReadOnly" placeholder="Employeur" />
        </div>
    </div>
</template>
