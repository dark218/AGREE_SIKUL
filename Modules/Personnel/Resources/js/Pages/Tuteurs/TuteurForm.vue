<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import ApprenantsPicker from '@/Components/Common/ApprenantsPicker.vue';
const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    mode: { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
    apprenants: { type: Array, default: () => [] },
});
const isReadOnly = props.mode === 'show';

/**
 * Auto-fill : quand un apprenant est sélectionné, on utilise son
 * `nom_tuteur` (ou nom_responsable_legal en fallback) pour pré-remplir
 * les champs nom/prénoms du tuteur en cours de création.
 * N'écrase que les champs vides pour préserver la saisie manuelle.
 */
const onApprenantSelected = ({ apprenant, isFirst }) => {
    if (!apprenant || !isFirst) return;
    const source = apprenant.nom_tuteur || apprenant.nom_responsable_legal;
    if (!source) return;

    const parts = source.trim().split(/\s+/);
    const nom = parts.length > 1 ? parts.pop() : parts[0];
    const prenoms = parts.join(' ');

    if (!props.form.nom || String(props.form.nom).trim() === '') props.form.nom = nom;
    if (!props.form.prenoms || String(props.form.prenoms).trim() === '') props.form.prenoms = prenoms;
    if (apprenant.telephone && (!props.form.telephone || String(props.form.telephone).trim() === '')) {
        props.form.telephone = apprenant.telephone;
    }
    if (apprenant.email && (!props.form.email || String(props.form.email).trim() === '')) {
        props.form.email = apprenant.email;
    }
};
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
        <div class="col-12">
            <label class="form-label fw-medium">
                Apprenants suivis <small class="text-muted">— fratrie dans la même école</small>
            </label>
            <ApprenantsPicker
                v-model="form.apprenant_ids"
                :apprenants="apprenants"
                :disabled="isReadOnly"
                @apprenant-selected="onApprenantSelected"
            />
            <div v-if="form.errors?.apprenant_ids" class="text-danger small mt-1">
                {{ Array.isArray(form.errors.apprenant_ids) ? form.errors.apprenant_ids[0] : form.errors.apprenant_ids }}
            </div>
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
