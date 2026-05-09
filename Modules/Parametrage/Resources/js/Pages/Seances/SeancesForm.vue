<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
defineProps({
    form: Object,
    classes: Array,
    matieres: Array,
    enseignants: Array,
    error: Object,
});
const statusOptions = [
    { id: 'actif', libelle: t('common.active') || 'Actif' },
    { id: 'non_actif', libelle: t('common.inactive') || 'Inactif' },
];
</script>
<template>
    <div class="form-container">
        <div class="form-group">
            <label>{{ t('fields.code') || 'Code' }}*</label>
            <input type="text" v-model="form.code" class="form-control" :class="{ 'is-invalid': error?.code }" />
            <div class="invalid-feedback" v-if="error?.code">{{ error.code[0] }}</div>
        </div>
        <div class="form-group">
            <label>{{ t('fields.titre') || 'Titre' }}*</label>
            <input type="text" v-model="form.titre" class="form-control" :class="{ 'is-invalid': error?.titre }" />
            <div class="invalid-feedback" v-if="error?.titre">{{ error.titre[0] }}</div>
        </div>
        <div class="form-group">
            <label>{{ t('fields.date') || 'Date' }}</label>
            <input type="date" v-model="form.date" class="form-control" />
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>{{ t('fields.heure_debut') || 'Heure début' }}</label>
                <input type="time" v-model="form.heure_debut" class="form-control" />
            </div>
            <div class="form-group col-md-6">
                <label>{{ t('fields.heure_fin') || 'Heure fin' }}</label>
                <input type="time" v-model="form.heure_fin" class="form-control" />
            </div>
        </div>
        <div class="form-group">
            <label>{{ t('fields.classe') || 'Classe' }}</label>
            <SearchableSelect v-model="form.classe_id" :options="classes" optionValue="id" optionLabel="nom" />
        </div>
        <div class="form-group">
            <label>{{ t('fields.matiere') || 'Matière' }}</label>
            <SearchableSelect v-model="form.matiere_id" :options="matieres" optionValue="id" optionLabel="titre" />
        </div>
        <div class="form-group">
            <label>{{ t('fields.enseignant') || 'Enseignant' }}</label>
            <SearchableSelect v-model="form.enseignant_id" :options="enseignants" optionValue="id"
                :optionLabel="(item) => item.nom + ' ' + (item.prenoms || '')" />
        </div>
        <div class="form-group">
            <label>{{ t('fields.salle') || 'Salle' }}</label>
            <input type="text" v-model="form.salle" class="form-control" />
        </div>
        <div class="form-group">
            <label>{{ t('fields.statut') || 'Statut' }}</label>
            <select v-model="form.statut" class="form-control">
                <option v-for="status in statusOptions" :key="status.id" :value="status.id">{{ status.libelle }}</option>
            </select>
        </div>
    </div>
</template>
<style scoped>
.form-container { display: flex; flex-direction: column; gap: 1rem; }
.form-group { display: flex; flex-direction: column; }
.form-group label { margin-bottom: 0.5rem; font-weight: 500; }
.form-control { padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.25rem; }
.form-control:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25); }
.form-control.is-invalid { border-color: #dc3545; }
.invalid-feedback { display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem; }
.form-row { display: flex; gap: 1rem; }
.form-row .form-group { flex: 1; }
</style>
