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
    regions: {
        type: Array,
        default: () => [],
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
const typeZoneOptions = [
    { id: 'URBAIN', libelle: 'Urbain' },
    { id: 'RURAL', libelle: 'Rural' },
    { id: 'SEMI_URBAIN', libelle: 'Semi-urbain' },
    { id: 'PERIPHERIQUE', libelle: 'Périphérique' },
    { id: 'INDUSTRIEL', libelle: 'Industriel' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle')" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Type Zone (liste fixe) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type_zone') || 'Type de Zone' }}</label>
                <SearchableSelect
                    v-model="form.type_zone"
                    :options="typeZoneOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_zone" class="text-danger">
                    <strong>{{ form.errors.type_zone }}</strong>
                </span>
            </div>
        </div>
        <!-- Coordonnées (optionnel) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.coordinates') || 'Coordonnées' }}
                    <small class="text-muted">(optionnel — GPS : lat,lng)</small>
                </label>
                <input type="text" v-model="form.coordinates" class="form-control" placeholder="Ex: 4.0511,9.7679" :disabled="isReadOnly">
                <span v-if="form.errors?.coordinates" class="text-danger">
                    <strong>{{ form.errors.coordinates }}</strong>
                </span>
            </div>
        </div>
        <!-- Description -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.description') }}</label>
                <textarea v-model="form.description" class="form-control" :placeholder="t('fields.description')" :disabled="isReadOnly" rows="2"></textarea>
                <span v-if="form.errors?.description" class="text-danger">
                    <strong>{{ form.errors.description }}</strong>
                </span>
            </div>
        </div>
        <!-- Region -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.region') }}</label>
                <SearchableSelect
                    v-model="form.region_id"
                    :options="props.regions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.region_id" class="text-danger">
                    <strong>{{ form.errors.region_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Pays -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.pays') }}</label>
                <SearchableSelect
                    v-model="form.pays_id"
                    :options="props.pays"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.pays_id" class="text-danger">
                    <strong>{{ form.errors.pays_id }}</strong>
                </span>
            </div>
        </div>
        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
