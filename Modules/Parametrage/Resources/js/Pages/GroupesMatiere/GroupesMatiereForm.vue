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
    ecoles: { type: Array, default: () => [] },
    institutions: { type: Array, default: () => [] },
    niveaux: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
});

const isReadOnly = computed(() => props.mode === 'show');
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find((i) => String(i.id) === String(id));
    return found?.libelle || found?.nom || '—';
};
const sectionLabel = computed(() => autoLabel(props.sections, props.form.section_id));
const cycleLabel = computed(() => autoLabel(props.cycles, props.form.cycle_id));
const niveauSelected = computed(() => !!props.form.niveau_id);

// HÉRITAGE depuis École : Institution remonte automatiquement
watch(() => props.form.ecole_id, (newEcoleId) => {
    if (!newEcoleId || isReadOnly.value) return;
    const ecole = props.ecoles.find((e) => String(e.id) === String(newEcoleId));
    if (!ecole) return;
    if (ecole.institution_id) props.form.institution_id = ecole.institution_id;
});

// Cascade Niveau → Section + Cycle (auto-fill depuis niveau_etudes)
watch(() => props.form.niveau_id, (newNiveauId) => {
    if (isReadOnly.value) return;
    if (!newNiveauId) {
        props.form.section_id = null;
        props.form.cycle_id = null;
        return;
    }
    const niveau = props.niveaux.find((n) => String(n.id) === String(newNiveauId));
    if (niveau) {
        props.form.section_id = niveau.section_id ?? null;
        props.form.cycle_id = niveau.cycle_id ?? null;
    }
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- LIGNE 1 : Code | Libellé -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle')" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger"><strong>{{ form.errors.libelle }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 2 : École | Institution (auto) -->
        <div class="col-md-4">
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
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.institution') || 'Institution' }}
                    <small class="text-muted">(auto depuis école)</small>
                </label>
                <SearchableSelect
                    v-model.number="form.institution_id"
                    :options="institutions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.institution_id" class="text-danger"><strong>{{ form.errors.institution_id }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 3 : Niveau | Section (auto lecture seule) -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.niveau') || 'Niveau' }} <span class="text-danger">*</span></label>
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
        <div class="col-md-4">
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

        <!-- LIGNE 4 : Cycle (auto lecture seule) | Matière 1 -->
        <div class="col-md-4">
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
        <!-- Matières du groupe (multi-select via pivot groupe_matiere_items).
             Remplace les 10 slots hardcodés matiere1_id..matiere10_id. -->
        <div class="col-12">
            <div class="mb-3">
                <label class="mb-2">
                    Matières du groupe
                    <small class="text-muted">— sélectionne autant de matières que nécessaire</small>
                </label>
                <SearchableSelect
                    v-model="form.matieres"
                    :options="matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :multiple="true"
                    :placeholder="t('actions.select') || 'Cliquer pour ajouter…'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matieres" class="text-danger"><strong>{{ form.errors.matieres }}</strong></span>
            </div>
        </div>

        <!-- État -->
        <div class="col-md-4">
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
