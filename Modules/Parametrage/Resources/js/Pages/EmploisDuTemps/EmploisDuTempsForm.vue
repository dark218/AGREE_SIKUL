<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
defineProps({ form: Object, classes: Array, matieres: Array, enseignants: Array, error: Object });
const joursOptions = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'].map(j => ({ id: j, libelle: j.charAt(0).toUpperCase() + j.slice(1) }));
const statusOptions = [
    { id: 'actif', libelle: t('common.active') },
    { id: 'non_actif', libelle: t('common.inactive') },
];
</script>
<template>
    <div class="form-container">
        <div class="form-group"><label>{{ t('fields.classe') }}*</label><SearchableSelect v-model="form.classe_id" :options="classes" optionValue="id" optionLabel="nom" /></div>
        <div class="form-group"><label>{{ t('fields.matiere') }}</label><SearchableSelect v-model="form.matiere_id" :options="matieres" optionValue="id" optionLabel="titre" /></div>
        <div class="form-group"><label>{{ t('fields.enseignant') }}</label><SearchableSelect v-model="form.enseignant_id" :options="enseignants" optionValue="id" :optionLabel="(item) => item.nom + ' ' + (item.prenoms || '')" /></div>
        <div class="form-group"><label>{{ t('fields.jour_semaine') }}*</label><select v-model="form.jour_semaine" class="form-control" :class="{ 'is-invalid': error?.jour_semaine }"><option v-for="j in joursOptions" :key="j.id" :value="j.id">{{ j.libelle }}</option></select></div>
        <div class="form-row">
            <div class="form-group col-md-6"><label>{{ t('fields.heure_debut') }}</label><input type="time" v-model="form.heure_debut" class="form-control" /></div>
            <div class="form-group col-md-6"><label>{{ t('fields.heure_fin') }}</label><input type="time" v-model="form.heure_fin" class="form-control" /></div>
        </div>
        <div class="form-group"><label>{{ t('fields.salle') }}</label><input type="text" v-model="form.salle" class="form-control" /></div>
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
