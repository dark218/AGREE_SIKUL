<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    communes: { type: Array, default: () => [] },
    departements: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    pays: { type: Array, default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const isReadOnly = computed(() => props.mode === 'show');
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// CASCADE COMPLÈTE : Commune → Département + Région + Pays
// (l'utilisateur peut toujours modifier ensuite — pas de verrouillage)
watch(() => props.form.commune_id, (newCommuneId) => {
    if (!newCommuneId || isReadOnly.value) return;
    const commune = props.communes.find(c => String(c.id) === String(newCommuneId));
    if (!commune) return;
    if (commune.departement_id) {
        props.form.departement_id = commune.departement_id;
        const dept = props.departements.find(d => String(d.id) === String(commune.departement_id));
        if (dept) {
            if (dept.region_id) props.form.region_id = dept.region_id;
            if (dept.pays_id) props.form.pays_id = dept.pays_id;
            if (!dept.pays_id && dept.region_id) {
                const region = props.regions.find(r => String(r.id) === String(dept.region_id));
                if (region?.pays_id) props.form.pays_id = region.pays_id;
            }
        }
    }
});

// CASCADE Département → Région + Pays
watch(() => props.form.departement_id, (newDeptId) => {
    if (!newDeptId || isReadOnly.value) return;
    const dept = props.departements.find(d => String(d.id) === String(newDeptId));
    if (dept) {
        if (dept.region_id) props.form.region_id = dept.region_id;
        if (dept.pays_id) props.form.pays_id = dept.pays_id;
        if (!dept.pays_id && dept.region_id) {
            const region = props.regions.find(r => String(r.id) === String(dept.region_id));
            if (region?.pays_id) props.form.pays_id = region.pays_id;
        }
    }
});

// CASCADE Région → Pays
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
        <!-- LIGNE 1 : Code | Quartier (libellé) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code') || 'Code'" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.quartier') || 'Quartier' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" placeholder="Quartier" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger"><strong>{{ form.errors.libelle }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 2 : Commune | Ville -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.commune') || 'Commune' }} <small class="text-muted">(département/région/pays auto)</small> <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model.number="form.commune_id"
                    :options="communes"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.commune_id" class="text-danger"><strong>{{ form.errors.commune_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ville') || 'Ville' }}</label>
                <input type="text" v-model="form.ville" class="form-control" :placeholder="t('fields.ville') || 'Ville'" :disabled="isReadOnly">
                <span v-if="form.errors?.ville" class="text-danger"><strong>{{ form.errors.ville }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 3 : Département | Région/Province -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.departement') || 'Département' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model.number="form.departement_id"
                    :options="departements"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.departement_id" class="text-danger"><strong>{{ form.errors.departement_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.region') || 'Région/Province' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model.number="form.region_id"
                    :options="regions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.region_id" class="text-danger"><strong>{{ form.errors.region_id }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 4 : Pays | État physique -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.pays') || 'Pays' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model.number="form.pays_id"
                    :options="pays"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.pays_id" class="text-danger"><strong>{{ form.errors.pays_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') || 'État physique' }}</label>
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
