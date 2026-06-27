<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useClasseCascade } from '@/Composables/useClasseCascade';
const { t } = useI18n();
const props = defineProps({
    form: Object,
    matieres: Array,
    enseignants: Array,
    classes: Array,
    error: Object,
});

useClasseCascade(props.form, () => props.classes);
const statusOptions = [
    { id: 'actif', libelle: t('common.active') || 'Actif' },
    { id: 'non_actif', libelle: t('common.inactive') || 'Inactif' },
    { id: 'suspendu', libelle: t('common.suspended') || 'Suspendu' },
    { id: 'exclus', libelle: t('common.excluded') || 'Exclus' },
];
const typeOptions = [
    { id: 'devoir', libelle: 'Devoir' },
    { id: 'controle', libelle: 'Contrôle' },
    { id: 'examen', libelle: 'Examen' },
];
</script>
<template>
    <div class="form-container">
        <!-- Code -->
        <div class="form-group">
            <label>{{ t('fields.code') || 'Code' }}*</label>
            <input
                type="text"
                v-model="form.code"
                class="form-control"
                :class="{ 'is-invalid': error?.code }"
                :placeholder="t('fields.code') || 'Code'"
            />
            <div class="invalid-feedback" v-if="error?.code">{{ error.code[0] }}</div>
        </div>
        <!-- Titre -->
        <div class="form-group">
            <label>{{ t('fields.titre') || 'Titre' }}*</label>
            <input
                type="text"
                v-model="form.titre"
                class="form-control"
                :class="{ 'is-invalid': error?.titre }"
                :placeholder="t('fields.titre') || 'Titre'"
            />
            <div class="invalid-feedback" v-if="error?.titre">{{ error.titre[0] }}</div>
        </div>
        <!-- Description -->
        <div class="form-group">
            <label>{{ t('fields.description') || 'Description' }}</label>
            <textarea
                v-model="form.description"
                class="form-control"
                :placeholder="t('fields.description') || 'Description'"
                rows="4"
            ></textarea>
        </div>
        <!-- Matière -->
        <div class="form-group">
            <label>{{ t('fields.matiere') || 'Matière' }}</label>
            <SearchableSelect
                v-model="form.matiere_id"
                :options="matieres"
                :placeholder="t('fields.select_matiere') || 'Sélectionner une matière'"
                optionValue="id"
                optionLabel="titre"
            />
        </div>
        <!-- Enseignant -->
        <div class="form-group">
            <label>{{ t('fields.enseignant') || 'Enseignant' }}</label>
            <SearchableSelect
                v-model="form.enseignant_id"
                :options="enseignants"
                :placeholder="t('fields.select_enseignant') || 'Sélectionner un enseignant'"
                optionValue="id"
                :optionLabel="(item) => item.nom + ' ' + (item.prenoms || '')"
            />
        </div>
        <!-- Classe -->
        <div class="form-group">
            <label>{{ t('fields.classe') || 'Classe' }}</label>
            <SearchableSelect
                v-model="form.classe_id"
                :options="classes"
                :placeholder="t('fields.select_classe') || 'Sélectionner une classe'"
                optionValue="id"
                optionLabel="nom"
            />
        </div>
        <InheritedContextBar :source="classes?.find(c => String(c.id) === String(form.classe_id)) || null" title="Hérité de la classe" />
        <!-- Dates -->
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>{{ t('fields.date_debut') || 'Date début' }}</label>
                <input
                    type="date"
                    v-model="form.date_debut"
                    class="form-control"
                />
            </div>
            <div class="form-group col-md-6">
                <label>{{ t('fields.date_fin') || 'Date fin' }}</label>
                <input
                    type="date"
                    v-model="form.date_fin"
                    class="form-control"
                />
            </div>
        </div>
        <!-- Statut -->
        <div class="form-group">
            <label>{{ t('fields.statut') || 'Statut' }}</label>
            <select v-model="form.statut" class="form-control">
                <option value="">{{ t('fields.select_statut') || 'Sélectionner un statut' }}</option>
                <option v-for="status in statusOptions" :key="status.id" :value="status.id">
                    {{ status.libelle }}
                </option>
            </select>
        </div>
    </div>
</template>
<style scoped>
.form-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.form-group {
    display: flex;
    flex-direction: column;
}
.form-group label {
    margin-bottom: 0.5rem;
    font-weight: 500;
    font-size: 0.9rem;
}
.form-control {
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    font-size: 0.9rem;
}
.form-control:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.form-control.is-invalid {
    border-color: #dc3545;
}
.invalid-feedback {
    display: block;
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
.form-row {
    display: flex;
    gap: 1rem;
}
.form-row .form-group {
    flex: 1;
}
</style>
