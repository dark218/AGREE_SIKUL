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
    unites_organisationnelles: {
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
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.code" :class="['form-control', { 'is-invalid': form.errors?.code }]" :placeholder="t('fields.code')" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.code) ? form.errors.code[0] : form.errors.code }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" :class="['form-control', { 'is-invalid': form.errors?.libelle }]" :placeholder="t('fields.libelle')" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.libelle) ? form.errors.libelle[0] : form.errors.libelle }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Unite Organisationnelle -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.organizational_unit') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.unite_organisationnelle_id"
                    :options="props.unites_organisationnelles"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.unite_organisationnelle_id" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.unite_organisationnelle_id) ? form.errors.unite_organisationnelle_id[0] : form.errors.unite_organisationnelle_id }}</strong></small>
                </span>
            </div>
        </div>
        <!-- État -->
        <div class="col-md-4">
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
