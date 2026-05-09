<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    communes: {
        type: Array,
        default: () => [],
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
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
// Filter communes by selected departement
const filteredCommunes = computed(() => {
    if (!props.form.departement_id) return props.communes;
    return props.communes.filter(c => c.departement_id === props.form.departement_id);
});
// Filter departements by selected region
const filteredDepartements = computed(() => {
    if (!props.form.region_id) return props.departements;
    return props.departements.filter(d => d.region_id === props.form.region_id);
});
// Filter regions by selected pays
const filteredRegions = computed(() => {
    if (!props.form.pays_id) return props.regions;
    return props.regions.filter(r => r.pays_id === props.form.pays_id);
});
// Auto-populate hierarchy when commune is selected
watch(() => props.form.commune_id, (newCommuneId) => {
    if (!newCommuneId) return;
    const selectedCommune = props.communes.find(c => c.id === newCommuneId);
    if (selectedCommune) {
        props.form.departement_id = selectedCommune.departement_id;
        const dept = props.departements.find(d => d.id === selectedCommune.departement_id);
        if (dept) {
            props.form.region_id = dept.region_id;
            props.form.pays_id = dept.pays_id;
        }
    }
});
// Auto-populate hierarchy when departement is selected
watch(() => props.form.departement_id, (newDepartementId) => {
    if (!newDepartementId) return;
    const selectedDept = props.departements.find(d => d.id === newDepartementId);
    if (selectedDept) {
        props.form.region_id = selectedDept.region_id;
        props.form.pays_id = selectedDept.pays_id;
        if (props.form.commune_id) {
            const commune = props.communes.find(c => c.id === props.form.commune_id);
            if (!commune || commune.departement_id !== newDepartementId) {
                props.form.commune_id = null;
            }
        }
    }
});
// Auto-populate hierarchy when region is selected
watch(() => props.form.region_id, (newRegionId) => {
    if (!newRegionId) return;
    const selectedRegion = props.regions.find(r => r.id === newRegionId);
    if (selectedRegion) {
        props.form.pays_id = selectedRegion.pays_id;
        if (props.form.departement_id) {
            const dept = props.departements.find(d => d.id === props.form.departement_id);
            if (!dept || dept.region_id !== newRegionId) {
                props.form.departement_id = null;
                props.form.commune_id = null;
            }
        }
    }
});
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
        <!-- Quartier -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.quartier') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" placeholder="Quartier" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Pays -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.pays') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model.number="form.pays_id"
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
        <!-- Région -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.region') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model.number="form.region_id"
                    :options="filteredRegions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly || !form.pays_id"
                />
                <span v-if="form.errors?.region_id" class="text-danger">
                    <strong>{{ form.errors.region_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Département -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.departement') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model.number="form.departement_id"
                    :options="filteredDepartements"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly || !form.region_id"
                />
                <span v-if="form.errors?.departement_id" class="text-danger">
                    <strong>{{ form.errors.departement_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Commune -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.commune') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model.number="form.commune_id"
                    :options="filteredCommunes"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly || !form.departement_id"
                />
                <span v-if="form.errors?.commune_id" class="text-danger">
                    <strong>{{ form.errors.commune_id }}</strong>
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
