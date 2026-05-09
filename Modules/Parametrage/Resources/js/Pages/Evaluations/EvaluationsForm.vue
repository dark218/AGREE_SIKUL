<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
defineProps({ form: Object, classes: Array, matieres: Array, error: Object });
const typeOptions = [
    { id: 'devoir', libelle: t('fields.devoir') || 'Devoir' },
    { id: 'controle', libelle: t('fields.controle') || 'Contrôle' },
    { id: 'examen', libelle: t('fields.examen') || 'Examen' },
];
const statusOptions = [
    { id: 'actif', libelle: t('common.active') },
    { id: 'non_actif', libelle: t('common.inactive') },
];
</script>
<template>
    <div class="form-container">
        <div class="form-group"><label>{{ t('fields.code') }}*</label><input type="text" v-model="form.code" class="form-control" :class="{ 'is-invalid': error?.code }" /></div>
        <div class="form-group"><label>{{ t('fields.titre') }}*</label><input type="text" v-model="form.titre" class="form-control" :class="{ 'is-invalid': error?.titre }" /></div>
        <div class="form-group"><label>{{ t('fields.type') }}*</label><select v-model="form.type" class="form-control"><option v-for="t in typeOptions" :key="t.id" :value="t.id">{{ t.libelle }}</option></select></div>
        <div class="form-group"><label>{{ t('fields.classe') }}</label><SearchableSelect v-model="form.classe_id" :options="classes" optionValue="id" optionLabel="nom" /></div>
        <div class="form-group"><label>{{ t('fields.matiere') }}</label><SearchableSelect v-model="form.matiere_id" :options="matieres" optionValue="id" optionLabel="titre" /></div>
        <div class="form-group"><label>{{ t('fields.date') }}</label><input type="date" v-model="form.date" class="form-control" /></div>
        <div class="form-row">
            <div class="form-group col-md-6"><label>{{ t('fields.coefficient') }}</label><input type="number" v-model="form.coefficient" step="0.01" class="form-control" /></div>
            <div class="form-group col-md-6"><label>{{ t('fields.sur') }}</label><input type="number" v-model="form.sur" step="0.01" class="form-control" /></div>
        </div>
        <div class="form-group"><label>{{ t('fields.statut') }}</label><select v-model="form.statut" class="form-control"><option v-for="s in statusOptions" :key="s.id" :value="s.id">{{ s.libelle }}</option></select></div>
    </div>
</template>
<style scoped>
.form-container { display: flex; flex-direction: column; gap: 1rem; }
.form-group { display: flex; flex-direction: column; }
.form-group label { margin-bottom: 0.5rem; font-weight: 500; }
.form-control { padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; }
.form-control:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25); }
.form-row { display: flex; gap: 1rem; }
.form-row .form-group { flex: 1; }
</style>
