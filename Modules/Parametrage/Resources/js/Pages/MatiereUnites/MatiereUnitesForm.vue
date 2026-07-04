<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    ecoles: { type: Array, default: () => [] },
    institutions: { type: Array, default: () => [] },
    niveaux: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
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
const institutionLabel = computed(() => autoLabel(props.institutions, props.form.institution_id));
const sectionLabel = computed(() => autoLabel(props.sections, props.form.section_id));
const cycleLabel = computed(() => autoLabel(props.cycles, props.form.cycle_id));
const ecoleSelected = computed(() => !!props.form.ecole_id);
const niveauSelected = computed(() => !!props.form.niveau_id);

// HÉRITAGE École → Institution + Pays
watch(() => props.form.ecole_id, (newEcoleId) => {
    if (isReadOnly.value) return;
    if (!newEcoleId) {
        props.form.institution_id = null;
        return;
    }
    const ecole = props.ecoles.find(e => String(e.id) === String(newEcoleId));
    if (!ecole) return;
    if (ecole.institution_id) props.form.institution_id = ecole.institution_id;
    if (ecole.pays_id) props.form.pays_id = ecole.pays_id;
});

// HÉRITAGE Niveau → Section + Cycle
watch(() => props.form.niveau_id, (newNiveauId) => {
    if (isReadOnly.value) return;
    if (!newNiveauId) {
        props.form.section_id = null;
        props.form.cycle_id = null;
        return;
    }
    const niveau = props.niveaux.find(n => String(n.id) === String(newNiveauId));
    if (niveau) {
        props.form.section_id = niveau.section_id ?? null;
        props.form.cycle_id = niveau.cycle_id ?? null;
    }
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- LIGNE 1 : Code | Libellé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code') || 'Code'" :disabled="isReadOnly" />
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') || 'Libellé' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle') || 'Libellé'" :disabled="isReadOnly" />
                <span v-if="form.errors?.libelle" class="text-danger"><strong>{{ form.errors.libelle }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 2 : École | Institution (auto readonly) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ecole') || 'École' }}</label>
                <SearchableSelect
                    v-model.number="form.ecole_id"
                    :options="ecoles"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ecole_id" class="text-danger"><strong>{{ form.errors.ecole_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.institution') || 'Institution' }}
                    <span v-if="ecoleSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
                </label>
                <input
                    type="text"
                    class="form-control"
                    :value="institutionLabel"
                    disabled
                    style="background:#eef2f7; color:#64748b;"
                />
                <span v-if="form.errors?.institution_id" class="text-danger"><strong>{{ form.errors.institution_id }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 3 : Niveau | Section (auto readonly) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.niveau') || 'Niveau' }}</label>
                <SearchableSelect
                    v-model.number="form.niveau_id"
                    :options="niveaux"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.niveau_id" class="text-danger"><strong>{{ form.errors.niveau_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }}
                    <span v-if="niveauSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
                </label>
                <input
                    type="text"
                    class="form-control"
                    :value="sectionLabel"
                    disabled
                    style="background:#eef2f7; color:#64748b;"
                />
                <span v-if="form.errors?.section_id" class="text-danger"><strong>{{ form.errors.section_id }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 4 : Cycle (auto readonly) | Coefficient -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.cycle') || 'Cycle' }}
                    <span v-if="niveauSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
                </label>
                <input
                    type="text"
                    class="form-control"
                    :value="cycleLabel"
                    disabled
                    style="background:#eef2f7; color:#64748b;"
                />
                <span v-if="form.errors?.cycle_id" class="text-danger"><strong>{{ form.errors.cycle_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.coefficient') || 'Coefficient' }}</label>
                <input type="number" step="0.01" min="0" max="10" v-model="form.coefficient" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.coefficient" class="text-danger"><strong>{{ form.errors.coefficient }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 5 : État physique -->
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
