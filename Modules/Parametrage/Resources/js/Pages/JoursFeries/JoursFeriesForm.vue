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
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
    pays: {
        type: Array,
        default: () => [],
    },
});
const isReadOnly = computed(() => props.mode === 'show');
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.code" :class="['form-control', { 'is-invalid': form.errors?.code }]" :placeholder="t('fields.code')" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.code) ? form.errors.code[0] : form.errors.code }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" :class="['form-control', { 'is-invalid': form.errors?.libelle }]" :placeholder="t('fields.libelle')" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.libelle) ? form.errors.libelle[0] : form.errors.libelle }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Jour -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.jour') }}</label>
                <input type="number" v-model.number="form.jour" :class="['form-control', { 'is-invalid': form.errors?.jour }]" :disabled="isReadOnly">
                <span v-if="form.errors?.jour" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.jour) ? form.errors.jour[0] : form.errors.jour }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Mois -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.mois') }}</label>
                <input type="number" v-model.number="form.mois" :class="['form-control', { 'is-invalid': form.errors?.mois }]" :disabled="isReadOnly">
                <span v-if="form.errors?.mois" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.mois) ? form.errors.mois[0] : form.errors.mois }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Annee -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee') }}</label>
                <input type="number" v-model.number="form.annee" :class="['form-control', { 'is-invalid': form.errors?.annee }]" :disabled="isReadOnly">
                <span v-if="form.errors?.annee" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.annee) ? form.errors.annee[0] : form.errors.annee }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Date -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date') }}</label>
                <input type="date" v-model="form.date" :class="['form-control', { 'is-invalid': form.errors?.date }]" :disabled="isReadOnly">
                <span v-if="form.errors?.date" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.date) ? form.errors.date[0] : form.errors.date }}</strong></small>
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
                    :placeholder="t('common.select') || '-- Sélectionner --'"
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
                <label>{{ t('fields.status') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
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
