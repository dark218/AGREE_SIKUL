<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    unites: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    responsables: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});
const isReadOnly = computed(() => props.mode === 'show');
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const typeUniteOptions = [
    { id: 'direction', libelle: 'Direction' },
    { id: 'departement', libelle: 'Département' },
    { id: 'service', libelle: 'Service' },
    { id: 'bureau', libelle: 'Bureau' },
    { id: 'autre', libelle: 'Autre' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code') || 'Code'" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ Array.isArray(form.errors.code) ? form.errors.code[0] : form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') || 'Libellé' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle') || 'Libellé'" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ Array.isArray(form.errors.libelle) ? form.errors.libelle[0] : form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Unite Mère (Unité parente) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.unite_mere') || 'Unité mère' }}</label>
                <SearchableSelect
                    v-model="form.unite_mere_id"
                    :options="props.unites"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.unite_mere_id" class="text-danger">
                    <strong>{{ Array.isArray(form.errors.unite_mere_id) ? form.errors.unite_mere_id[0] : form.errors.unite_mere_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Type d'unité -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type_unite') || 'Type d\'unité' }}</label>
                <SearchableSelect
                    v-model="form.type_unite"
                    :options="typeUniteOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_unite" class="text-danger">
                    <strong>{{ Array.isArray(form.errors.type_unite) ? form.errors.type_unite[0] : form.errors.type_unite }}</strong>
                </span>
            </div>
        </div>
        <!-- Responsable -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.responsable') || 'Responsable' }}</label>
                <SearchableSelect
                    v-model="form.responsable_id"
                    :options="props.responsables"
                    optionValue="id"
                    optionLabel="name"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.responsable_id" class="text-danger">
                    <strong>{{ Array.isArray(form.errors.responsable_id) ? form.errors.responsable_id[0] : form.errors.responsable_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Budget annuel -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.budget_annuel') || 'Budget annuel' }}</label>
                <input type="number" v-model.number="form.budget_annuel" class="form-control" step="0.01" min="0" :disabled="isReadOnly">
                <span v-if="form.errors?.budget_annuel" class="text-danger">
                    <strong>{{ Array.isArray(form.errors.budget_annuel) ? form.errors.budget_annuel[0] : form.errors.budget_annuel }}</strong>
                </span>
            </div>
        </div>
        <!-- Effectif maximum -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.effectif_max') || 'Effectif maximum' }}</label>
                <input type="number" v-model.number="form.effectif_max" class="form-control" min="0" :disabled="isReadOnly">
                <small class="text-muted">Nombre de personnes</small>
                <span v-if="form.errors?.effectif_max" class="text-danger">
                    <strong>{{ Array.isArray(form.errors.effectif_max) ? form.errors.effectif_max[0] : form.errors.effectif_max }}</strong>
                </span>
            </div>
        </div>
        <!-- Niveau hiérarchique -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.niveau_hierarchique') || 'Niveau hiérarchique' }}</label>
                <input type="number" v-model.number="form.niveau_hierarchique" class="form-control" min="1" :disabled="isReadOnly">
                <small class="text-muted">Pour l'organigramme</small>
                <span v-if="form.errors?.niveau_hierarchique" class="text-danger">
                    <strong>{{ Array.isArray(form.errors.niveau_hierarchique) ? form.errors.niveau_hierarchique[0] : form.errors.niveau_hierarchique }}</strong>
                </span>
            </div>
        </div>
        <!-- Ecole -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.school') || 'École' }}</label>
                <SearchableSelect
                    v-model="form.ecole_id"
                    :options="props.ecoles"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ecole_id" class="text-danger">
                    <strong>{{ Array.isArray(form.errors.ecole_id) ? form.errors.ecole_id[0] : form.errors.ecole_id }}</strong>
                </span>
            </div>
        </div>
        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') || 'Statut' }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ Array.isArray(form.errors.etat) ? form.errors.etat[0] : form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
