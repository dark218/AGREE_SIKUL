<script setup>
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();

// DEBUG
onMounted(() => {
    console.log('PersonnelAdministratifForm - Mounted');
});
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
    departements: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});
const isReadOnly = props.mode === 'show';
const statutOptions = [
    { id: 'actif', libelle: t('common.actif') || 'Actif' },
    { id: 'suspendu', libelle: t('common.suspendu') || 'Suspendu' },
    { id: 'conge', libelle: t('common.conge') || 'Congé' },
    { id: 'retraite', libelle: t('common.retraite') || 'Retraite' },
];

const typeContratOptions = [
    { id: 'cdi', libelle: 'CDI' },
    { id: 'cdd', libelle: 'CDD' },
    { id: 'vacataire', libelle: 'Vacataire' },
    { id: 'autre', libelle: 'Autre' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Utilisateur -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.user') || 'Utilisateur' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.user_id"
                    :options="users"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.user_id" class="text-danger">
                    <strong>{{ form.errors.user_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Matricule -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.matricule') || 'Matricule' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.matricule" class="form-control" :placeholder="t('fields.matricule')" :disabled="isReadOnly">
                <span v-if="form.errors?.matricule" class="text-danger">
                    <strong>{{ form.errors.matricule }}</strong>
                </span>
            </div>
        </div>
        <!-- Poste -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.poste') || 'Poste' }}</label>
                <input type="text" v-model="form.poste" class="form-control" :placeholder="t('fields.poste')" :disabled="isReadOnly">
                <span v-if="form.errors?.poste" class="text-danger">
                    <strong>{{ form.errors.poste }}</strong>
                </span>
            </div>
        </div>
        <!-- Département -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.departement') || 'Département' }}</label>
                <SearchableSelect
                    v-model="form.departement_id"
                    :options="departements"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.departement_id" class="text-danger">
                    <strong>{{ form.errors.departement_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Date d'embauche -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_embauche') || 'Date d\'embauche' }}</label>
                <input type="date" v-model="form.date_embauche" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_embauche" class="text-danger">
                    <strong>{{ form.errors.date_embauche }}</strong>
                </span>
            </div>
        </div>
        <!-- Type de contrat -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type_contrat') || 'Type de contrat' }}</label>
                <SearchableSelect
                    v-model="form.type_contrat"
                    :options="typeContratOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_contrat" class="text-danger">
                    <strong>{{ form.errors.type_contrat }}</strong>
                </span>
            </div>
        </div>
        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.statut"
                    :options="statutOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
