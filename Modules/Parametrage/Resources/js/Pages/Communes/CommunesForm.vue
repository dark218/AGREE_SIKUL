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
    departements: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Array,
        default: () => [],
    },
    pays: {
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
const statusOptions = computed(() => [
    { id: 'actif', libelle: t('common.actif') || 'Actif' },
    { id: 'inactif', libelle: t('common.inactif') || 'Inactif' },
]);
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.code" maxlength="100" :class="['form-control', { 'is-invalid': form.errors?.code }]" :placeholder="t('fields.code') || 'Code'" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.code) ? form.errors.code[0] : form.errors.code }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') || 'Libellé' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" maxlength="255" :class="['form-control', { 'is-invalid': form.errors?.libelle }]" :placeholder="t('fields.libelle') || 'Libellé'" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.libelle) ? form.errors.libelle[0] : form.errors.libelle }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Departement -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.departement') || 'Département' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.departement_id"
                    :options="props.departements"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.departement_id" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.departement_id) ? form.errors.departement_id[0] : form.errors.departement_id }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Region -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.region') || 'Région' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.region_id"
                    :options="props.regions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.region_id" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.region_id) ? form.errors.region_id[0] : form.errors.region_id }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Pays -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.country') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.pays_id"
                    :options="props.pays"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.pays_id" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.pays_id) ? form.errors.pays_id[0] : form.errors.pays_id }}</strong></small>
                </span>
            </div>
        </div>
        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.etat) ? form.errors.etat[0] : form.errors.etat }}</strong></small>
                </span>
            </div>
        </div>
    </div>
</template>
