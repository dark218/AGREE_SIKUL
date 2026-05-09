<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import { usePage } from '@inertiajs/vue3';
const { t } = useI18n();
const page = usePage();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    fournisseurs: {
        type: Array,
        default: () => [],
    },
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
const typeModeOptions = [
    { id: 'especes', libelle: 'Espèces' },
    { id: 'cheque', libelle: 'Chèque' },
    { id: 'virement', libelle: 'Virement' },
    { id: 'mobile_money', libelle: 'Mobile Money' },
    { id: 'carte', libelle: 'Carte Bancaire' },
];
const fournisseurs = computed(() => props.fournisseurs || []);
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle')" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Type de mode -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type_mode') || 'Type de mode' }}</label>
                <SearchableSelect
                    v-model="form.type_mode"
                    :options="typeModeOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_mode" class="text-danger">
                    <strong>{{ form.errors.type_mode }}</strong>
                </span>
            </div>
        </div>
        <!-- Nécessite référence -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label class="form-check-label">
                    <input type="checkbox" v-model="form.necessite_reference" class="form-check-input" :disabled="isReadOnly">
                    {{ t('fields.necessite_reference') || 'Nécessite numéro de transaction' }}
                </label>
                <span v-if="form.errors?.necessite_reference" class="text-danger">
                    <strong>{{ form.errors.necessite_reference }}</strong>
                </span>
            </div>
        </div>
        <!-- Délai de compensation (jours) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.delai_compensation_jours') || 'Délai compensation (jours)' }}</label>
                <input type="number" v-model.number="form.delai_compensation_jours" class="form-control" min="0" :disabled="isReadOnly">
                <span v-if="form.errors?.delai_compensation_jours" class="text-danger">
                    <strong>{{ form.errors.delai_compensation_jours }}</strong>
                </span>
            </div>
        </div>
        <!-- Frais pourcentage -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.frais_pourcentage') || 'Frais (%)' }}</label>
                <input type="number" v-model.number="form.frais_pourcentage" class="form-control" step="0.01" min="0" max="100" :disabled="isReadOnly">
                <span v-if="form.errors?.frais_pourcentage" class="text-danger">
                    <strong>{{ form.errors.frais_pourcentage }}</strong>
                </span>
            </div>
        </div>
        <!-- Frais fixe -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.frais_fixe') || 'Frais fixe' }}</label>
                <input type="number" v-model.number="form.frais_fixe" class="form-control" step="0.01" min="0" :disabled="isReadOnly">
                <span v-if="form.errors?.frais_fixe" class="text-danger">
                    <strong>{{ form.errors.frais_fixe }}</strong>
                </span>
            </div>
        </div>
        <!-- Montant minimum -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.montant_min') || 'Montant minimum' }}</label>
                <input type="number" v-model.number="form.montant_min" class="form-control" step="0.01" min="0" :disabled="isReadOnly">
                <span v-if="form.errors?.montant_min" class="text-danger">
                    <strong>{{ form.errors.montant_min }}</strong>
                </span>
            </div>
        </div>
        <!-- Montant maximum -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.montant_max') || 'Montant maximum' }}</label>
                <input type="number" v-model.number="form.montant_max" class="form-control" step="0.01" min="0" :disabled="isReadOnly">
                <span v-if="form.errors?.montant_max" class="text-danger">
                    <strong>{{ form.errors.montant_max }}</strong>
                </span>
            </div>
        </div>
        <!-- Fournisseur de paiement -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.fournisseur_paiement') || 'Fournisseur de paiement' }}</label>
                <SearchableSelect
                    v-model.number="form.fournisseur_paiement_id"
                    :options="fournisseurs"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.fournisseur_paiement_id" class="text-danger">
                    <strong>{{ form.errors.fournisseur_paiement_id }}</strong>
                </span>
            </div>
        </div>
        <!-- État -->
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
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
