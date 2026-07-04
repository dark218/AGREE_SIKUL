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

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find((i) => String(i.id) === String(id));
    return found?.libelle || '—';
};
const departementLabel = computed(() => autoLabel(props.departements, props.form.departement_id));
const regionLabel = computed(() => autoLabel(props.regions, props.form.region_id));
const communeSelected = computed(() => !!props.form.commune_id);

// CASCADE Commune → Département + Région + Pays
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
                <label>{{ t('fields.commune') || 'Commune' }} <span class="text-danger">*</span></label>
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

        <!-- LIGNE 3 : Département (readonly auto) | Région (readonly auto) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.departement') || 'Département' }}
                    <span v-if="communeSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
                </label>
                <input
                    type="text"
                    class="form-control"
                    :value="departementLabel"
                    disabled
                    style="background:#eef2f7; color:#64748b;"
                />
                <span v-if="form.errors?.departement_id" class="text-danger"><strong>{{ form.errors.departement_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.region') || 'Région/Province' }}
                    <span v-if="communeSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
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

        <!-- LIGNE 4 : État physique seul -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') || 'État' }}</label>
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
