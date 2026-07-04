<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    departements: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const isReadOnly = computed(() => props.mode === 'show');
const statusOptions = computed(() => [
    { id: 'actif', libelle: t('common.actif') || 'Actif' },
    { id: 'inactif', libelle: t('common.inactif') || 'Inactif' },
]);

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find((i) => String(i.id) === String(id));
    return found?.libelle || '—';
};
const regionLabel = computed(() => autoLabel(props.regions, props.form.region_id));
const departementSelected = computed(() => !!props.form.departement_id);

// CASCADE Département → Région + Pays (Pays et Région remplis auto)
watch(() => props.form.departement_id, (newDeptId) => {
    if (!newDeptId || isReadOnly.value) return;
    const dept = props.departements.find(d => String(d.id) === String(newDeptId));
    if (!dept) return;
    if (dept.region_id) props.form.region_id = dept.region_id;
    if (dept.pays_id) props.form.pays_id = dept.pays_id;
    if (!dept.pays_id && dept.region_id) {
        const region = props.regions.find(r => String(r.id) === String(dept.region_id));
        if (region?.pays_id) props.form.pays_id = region.pays_id;
    }
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.code" maxlength="100" class="form-control" :placeholder="t('fields.code') || 'Code'" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.libelle') || 'Libellé' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" maxlength="255" class="form-control" :placeholder="t('fields.libelle') || 'Libellé'" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger"><strong>{{ form.errors.libelle }}</strong></span>
            </div>
        </div>
        <!-- Departement -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.departement') || 'Département' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.departement_id"
                    :options="departements"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.departement_id" class="text-danger"><strong>{{ form.errors.departement_id }}</strong></span>
            </div>
        </div>
        <!-- Region (readonly auto) -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.region') || 'Région' }}
                    <span v-if="departementSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
                </label>
                <input
                    type="text"
                    class="form-control"
                    :value="regionLabel"
                    disabled
                    style="background:#eef2f7; color:#64748b;"
                />
                <span v-if="form.errors?.region_id" class="text-danger"><strong>{{ form.errors.region_id }}</strong></span>
            </div>
        </div>
        <!-- État -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger"><strong>{{ form.errors.etat }}</strong></span>
            </div>
        </div>
    </div>
</template>
