<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
    annees_scolaires: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
});
const isReadOnly = computed(() => props.mode === 'show');
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const typePeriodeOptions = [
    { id: 'trimestre', libelle: 'Trimestre' },
    { id: 'semestre', libelle: 'Semestre' },
    { id: 'quadrimestre', libelle: 'Quadrimestre' },
    { id: 'annuel', libelle: 'Annuel' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Année scolaire (obligatoire) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.annee_scolaire') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.annee_scolaire_id"
                    :options="props.annees_scolaires"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.annee_scolaire_id" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.annee_scolaire_id) ? form.errors.annee_scolaire_id[0] : form.errors.annee_scolaire_id }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Type de période (obligatoire) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type_periode') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.type_periode"
                    :options="typePeriodeOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_periode" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.type_periode) ? form.errors.type_periode[0] : form.errors.type_periode }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.code" :class="['form-control', { 'is-invalid': form.errors?.code }]" :placeholder="t('fields.code')" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.code) ? form.errors.code[0] : form.errors.code }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" :class="['form-control', { 'is-invalid': form.errors?.libelle }]" :placeholder="t('fields.libelle')" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.libelle) ? form.errors.libelle[0] : form.errors.libelle }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Numéro d'ordre -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.numero_ordre') }}</label>
                <input type="number" v-model.number="form.numero_ordre" :class="['form-control', { 'is-invalid': form.errors?.numero_ordre }]" placeholder="1, 2, 3..." :disabled="isReadOnly" min="1">
                <span v-if="form.errors?.numero_ordre" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.numero_ordre) ? form.errors.numero_ordre[0] : form.errors.numero_ordre }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Ecole (optionnel) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.ecole') }}</label>
                <SearchableSelect
                    v-model="form.ecole_id"
                    :options="props.ecoles"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ecole_id" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.ecole_id) ? form.errors.ecole_id[0] : form.errors.ecole_id }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Date début (obligatoire) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_debut') }} <span class="text-danger">*</span></label>
                <input type="date" v-model="form.date_debut" :class="['form-control', { 'is-invalid': form.errors?.date_debut }]" :disabled="isReadOnly" required>
                <span v-if="form.errors?.date_debut" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.date_debut) ? form.errors.date_debut[0] : form.errors.date_debut }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Date fin (obligatoire) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_fin') }} <span class="text-danger">*</span></label>
                <input type="date" v-model="form.date_fin" :class="['form-control', { 'is-invalid': form.errors?.date_fin }]" :disabled="isReadOnly" required>
                <span v-if="form.errors?.date_fin" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.date_fin) ? form.errors.date_fin[0] : form.errors.date_fin }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Est période d'évaluation -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label class="form-check-label">
                    <input type="checkbox" v-model="form.est_periode_evaluation" class="form-check-input" :disabled="isReadOnly">
                    {{ t('fields.est_periode_evaluation') || 'Période d\'évaluation' }}
                </label>
                <span v-if="form.errors?.est_periode_evaluation" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.est_periode_evaluation) ? form.errors.est_periode_evaluation[0] : form.errors.est_periode_evaluation }}</strong></small>
                </span>
            </div>
        </div>
        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="d-block text-danger mt-1">
                    <small><strong>{{ Array.isArray(form.errors.etat) ? form.errors.etat[0] : form.errors.etat }}</strong></small>
                </span>
            </div>
        </div>
    </div>
</template>
