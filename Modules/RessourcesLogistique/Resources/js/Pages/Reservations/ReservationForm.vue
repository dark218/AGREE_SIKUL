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

const prioriteOptions = [
    { id: 'basse', libelle: 'Basse' },
    { id: 'normale', libelle: 'Normale' },
    { id: 'haute', libelle: 'Haute' },
];

const statutOptions = [
    { id: 'en_attente', libelle: 'En attente' },
    { id: 'confirmee', libelle: 'Confirmée' },
    { id: 'annulee', libelle: 'Annulée' },
    { id: 'retiree', libelle: 'Retirée' },
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

        <!-- Date de réservation -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_reservation') || 'Date de réservation' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_reservation"
                    type="date"
                    class="form-control"
                    :disabled="isReadOnly"
                    required
                />
                <span v-if="form.errors?.date_reservation" class="text-danger">
                    <strong>{{ form.errors.date_reservation }}</strong>
                </span>
            </div>
        </div>

        <!-- Date d'expiration -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_expiration') || 'Date d\'expiration' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_expiration"
                    type="date"
                    class="form-control"
                    :disabled="isReadOnly"
                    required
                />
                <span v-if="form.errors?.date_expiration" class="text-danger">
                    <strong>{{ form.errors.date_expiration }}</strong>
                </span>
            </div>
        </div>

        <!-- Date de notification (optionnel) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_notification') || 'Date de notification' }}</label>
                <input
                    v-model="form.date_notification"
                    type="date"
                    class="form-control"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_notification" class="text-danger">
                    <strong>{{ form.errors.date_notification }}</strong>
                </span>
            </div>
        </div>

        <!-- Priorité -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.priorite') || 'Priorité' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.priorite"
                    :options="prioriteOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.priorite" class="text-danger">
                    <strong>{{ form.errors.priorite }}</strong>
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
