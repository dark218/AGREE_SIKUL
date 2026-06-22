<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
    regions: { type: Array, default: () => [] },
    pays: { type: Array, default: () => [] },
});

const isReadOnly = computed(() => props.mode === 'show');
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// CASCADE Région → Pays : quand on sélectionne une région, le pays se remplit
watch(() => props.form.region_id, (newRegionId) => {
    if (!newRegionId || isReadOnly.value) return;
    const region = props.regions.find(r => String(r.id) === String(newRegionId));
    if (region?.pays_id) {
        props.form.pays_id = region.pays_id;
    }
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code') || 'Code'" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.label') || 'Libellé' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" :class="['form-control', { 'is-invalid': form.errors?.libelle }]" :placeholder="t('fields.label') || 'Libellé'" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.libelle) ? form.errors.libelle[0] : form.errors.libelle }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Region -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.region') || 'Région' }} <small class="text-muted">(le pays se remplit auto)</small> <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.region_id"
                    :options="regions"
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
                <label>{{ t('fields.country') || 'Pays' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.pays_id"
                    :options="pays"
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
