<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    exemplaires: {
        type: Array,
        default: () => [],
    },
    apprenants: {
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
    { id: 'en_cours', libelle: 'En cours' },
    { id: 'en_retard', libelle: 'En retard' },
    { id: 'retourne', libelle: 'Retourné' },
    { id: 'perdu', libelle: 'Perdu' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Exemplaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.exemplaire') || 'Exemplaire' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.exemplaire_id"
                    :options="exemplaires"
                    optionValue="id"
                    optionLabel="code_exemplaire"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.exemplaire_id" class="text-danger">
                    <strong>{{ form.errors.exemplaire_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.apprenant') || 'Apprenant' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.apprenant_id"
                    :options="apprenants"
                    optionValue="id"
                    optionLabel="name"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.apprenant_id" class="text-danger">
                    <strong>{{ form.errors.apprenant_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Date d'emprunt -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_emprunt') || 'Date d\'emprunt' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_emprunt"
                    type="date"
                    class="form-control"
                    :disabled="isReadOnly"
                    required
                />
                <span v-if="form.errors?.date_emprunt" class="text-danger">
                    <strong>{{ form.errors.date_emprunt }}</strong>
                </span>
            </div>
        </div>
        <!-- Date retour prévue -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_retour_prevue') || 'Date retour prévue' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_retour_prevue"
                    type="date"
                    class="form-control"
                    :disabled="isReadOnly"
                    required
                />
                <span v-if="form.errors?.date_retour_prevue" class="text-danger">
                    <strong>{{ form.errors.date_retour_prevue }}</strong>
                </span>
            </div>
        </div>
        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }} <span class="text-danger">*</span></label>
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
