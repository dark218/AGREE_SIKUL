<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useEcoleCascade } from '@/Composables/useEcoleCascade';
const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    ecoles: { type: Array, default: () => [] },
    mode: { type: String, default: 'create', validator: (value) => ['create', 'edit', 'show'].includes(value) },
});
const isReadOnly = props.mode === 'show';

// Cascade : École → Pays + Institution + Campus
useEcoleCascade(props.form, () => props.ecoles);
const statusOptions = [
    { id: 'actif', libelle: t('common.active') || 'Actif' },
    { id: 'non_actif', libelle: t('common.inactive') || 'Inactif' },
    { id: 'archive', libelle: 'Archivé' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <div class="col-12"><h5 class="section-title">{{ t('fields.basic_info') || 'Informations de base' }}</h5></div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input v-model="form.code" type="text" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly" />
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') || 'Libellé' }} <span class="text-danger">*</span></label>
                <input v-model="form.libelle" type="text" class="form-control" :placeholder="t('fields.libelle')" :disabled="isReadOnly" />
                <span v-if="form.errors?.libelle" class="text-danger"><strong>{{ form.errors.libelle }}</strong></span>
            </div>
        </div>
        <div class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.description') || 'Description' }}</label>
                <textarea v-model="form.description" class="form-control" :placeholder="t('fields.description')" :disabled="isReadOnly" rows="3"></textarea>
            </div>
        </div>
        <div class="col-12"><h5 class="section-title">{{ t('fields.academic') || 'Académique' }}</h5></div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.coefficient') || 'Coefficient' }}</label>
                <input v-model.number="form.coefficient" type="number" class="form-control" :placeholder="t('fields.coefficient')" :disabled="isReadOnly" min="0.5" max="10" step="0.5" />
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ecole') || 'École' }}</label>
                <SearchableSelect v-model="form.ecole_id" :options="ecoles" optionValue="id" optionLabel="nom" :placeholder="t('actions.select')" :disabled="isReadOnly" />
            </div>
        </div>
        <div class="col-12"><h5 class="section-title">{{ t('fields.status') || 'Statut' }}</h5></div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }}</label>
                <SearchableSelect v-model="form.statut" :options="statusOptions" optionValue="id" optionLabel="libelle" :disabled="isReadOnly" />
            </div>
        </div>
    </div>
</template>
<style scoped>
.section-title {
    font-weight: 600;
    margin-top: 1rem;
    margin-bottom: 1rem;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
}
</style>
