<script setup>
import { useI18n } from 'vue-i18n';

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
    ouvrages: {
        type: Array,
        default: () => [],
    },
});

const isReadOnly = props.mode === 'show';

const statusOptions = [
    { id: 'disponible', libelle: 'Disponible' },
    { id: 'prete', libelle: 'Prêtée' },
    { id: 'maintenance', libelle: 'Maintenance' },
    { id: 'retire', libelle: 'Retirée' },
];

const etatOptions = [
    { id: 'neuf', libelle: 'Neuf' },
    { id: 'bon', libelle: 'Bon' },
    { id: 'use', libelle: 'Usé' },
    { id: 'tres_use', libelle: 'Très usé' },
    { id: 'endommagé', libelle: 'Endommagé' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Code Exemplaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code_exemplaire') || 'Code Exemplaire' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.code_exemplaire"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.code_exemplaire') || 'Code Exemplaire'"
                    required
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.code_exemplaire" class="text-danger">
                    <strong>{{ form.errors.code_exemplaire }}</strong>
                </span>
            </div>
        </div>
        <!-- Numéro de série -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.numero_serie') || 'Numéro de série' }}</label>
                <input
                    v-model="form.numero_serie"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.numero_serie') || 'Numéro de série'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.numero_serie" class="text-danger">
                    <strong>{{ form.errors.numero_serie }}</strong>
                </span>
            </div>
        </div>
        <!-- Ouvrage -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ouvrage_id') || 'Ouvrage' }} <span class="text-danger">*</span></label>
                <select
                    v-model="form.ouvrage_id"
                    class="form-control"
                    required
                    :disabled="isReadOnly"
                >
                    <option value="">{{ t('common.select') || 'Sélectionner...' }}</option>
                    <option v-for="ouvrage in ouvrages" :key="ouvrage.id" :value="ouvrage.id">
                        {{ ouvrage.titre }}
                    </option>
                </select>
                <span v-if="form.errors?.ouvrage_id" class="text-danger">
                    <strong>{{ form.errors.ouvrage_id }}</strong>
                </span>
            </div>
        </div>
        <!-- État physique -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') || 'État physique' }} <span class="text-danger">*</span></label>
                <select
                    v-model="form.etat"
                    class="form-control"
                    required
                    :disabled="isReadOnly"
                >
                    <option value="">{{ t('common.select') || 'Sélectionner...' }}</option>
                    <option v-for="etat in etatOptions" :key="etat.id" :value="etat.id">
                        {{ etat.libelle }}
                    </option>
                </select>
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
        <!-- Localisation -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.localisation') || 'Localisation' }}</label>
                <input
                    v-model="form.localisation"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.localisation') || 'Localisation'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.localisation" class="text-danger">
                    <strong>{{ form.errors.localisation }}</strong>
                </span>
            </div>
        </div>
        <!-- Date d'acquisition -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_acquisition') || 'Date d\'acquisition' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_acquisition"
                    type="date"
                    class="form-control"
                    required
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_acquisition" class="text-danger">
                    <strong>{{ form.errors.date_acquisition }}</strong>
                </span>
            </div>
        </div>
        <!-- Date dernière maintenance -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_derniere_maintenance') || 'Date dernière maintenance' }}</label>
                <input
                    v-model="form.date_derniere_maintenance"
                    type="date"
                    class="form-control"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_derniere_maintenance" class="text-danger">
                    <strong>{{ form.errors.date_derniere_maintenance }}</strong>
                </span>
            </div>
        </div>
        <!-- Statut de disponibilité -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut de disponibilité' }} <span class="text-danger">*</span></label>
                <select
                    v-model="form.statut"
                    class="form-control"
                    required
                    :disabled="isReadOnly"
                >
                    <option value="">{{ t('common.select') || 'Sélectionner...' }}</option>
                    <option v-for="status in statusOptions" :key="status.id" :value="status.id">
                        {{ status.libelle }}
                    </option>
                </select>
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
