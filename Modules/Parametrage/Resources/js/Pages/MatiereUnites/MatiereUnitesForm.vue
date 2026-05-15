<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    matieres: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    niveaux: { type: Array, default: () => [] },
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
</script>
<template>
    <div class="row g-3 custom-input">
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly" />
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle')" :disabled="isReadOnly" />
                <span v-if="form.errors?.libelle" class="text-danger"><strong>{{ form.errors.libelle }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6" v-if="matieres && matieres.length > 0">
            <div class="mb-3">
                <label>{{ t('fields.matiere') || 'Matière' }}</label>
                <SearchableSelect v-model="form.matiere_id" :options="matieres" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.matiere_id" class="text-danger"><strong>{{ form.errors.matiere_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.coefficient') || 'Coefficient' }}</label>
                <input type="number" step="0.01" min="0" max="10" v-model="form.coefficient" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.coefficient" class="text-danger"><strong>{{ form.errors.coefficient }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6" v-if="niveaux && niveaux.length > 0">
            <div class="mb-3">
                <label>{{ t('fields.niveau') || 'Niveau' }} <small class="text-muted">(optionnel — varie selon contexte)</small></label>
                <SearchableSelect v-model="form.niveau_id" :options="niveaux" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.niveau_id" class="text-danger"><strong>{{ form.errors.niveau_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6" v-if="sections && sections.length > 0">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }} <small class="text-muted">(optionnel)</small></label>
                <SearchableSelect v-model="form.section_id" :options="sections" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.section_id" class="text-danger"><strong>{{ form.errors.section_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6" v-if="cycles && cycles.length > 0">
            <div class="mb-3">
                <label>{{ t('fields.cycle') || 'Cycle' }} <small class="text-muted">(optionnel)</small></label>
                <SearchableSelect v-model="form.cycle_id" :options="cycles" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.cycle_id" class="text-danger"><strong>{{ form.errors.cycle_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') || 'État' }}</label>
                <SearchableSelect v-model="form.etat" :options="statusOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.etat" class="text-danger"><strong>{{ form.errors.etat }}</strong></span>
            </div>
        </div>
    </div>
</template>
