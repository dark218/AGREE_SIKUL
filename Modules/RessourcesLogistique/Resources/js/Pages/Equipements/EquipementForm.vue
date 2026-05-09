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
    categories: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
});

const isReadOnly = props.mode === 'show';

const etatOptions = [
    { id: 'excellent', libelle: 'Excellent' },
    { id: 'bon', libelle: 'Bon' },
    { id: 'moyen', libelle: 'Moyen' },
    { id: 'mauvais', libelle: 'Mauvais' },
    { id: 'non_fonctionnel', libelle: 'Non fonctionnel' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Catégorie -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.categorie') || 'Catégorie' }} <span class="text-danger">*</span></label>
                <select
                    v-model="form.categorie_id"
                    class="form-control"
                    required
                    :disabled="isReadOnly"
                >
                    <option value="">{{ t('common.select') || 'Sélectionner...' }}</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.libelle || cat.nom }}
                    </option>
                </select>
                <span v-if="form.errors?.categorie_id" class="text-danger">
                    <strong>{{ form.errors.categorie_id }}</strong>
                </span>
            </div>
        </div>
        <!-- École -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.ecole') || 'École' }} <span class="text-danger">*</span></label>
                <select
                    v-model="form.ecole_id"
                    class="form-control"
                    required
                    :disabled="isReadOnly"
                >
                    <option value="">{{ t('common.select') || 'Sélectionner...' }}</option>
                    <option v-for="ecole in ecoles" :key="ecole.id" :value="ecole.id">
                        {{ ecole.nom }}
                    </option>
                </select>
                <span v-if="form.errors?.ecole_id" class="text-danger">
                    <strong>{{ form.errors.ecole_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Nom -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.nom') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.nom"
                    type="text"
                    class="form-control"
                    :placeholder="t('common.nom')"
                    required
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom" class="text-danger">
                    <strong>{{ form.errors.nom }}</strong>
                </span>
            </div>
        </div>
        <!-- Référence -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.reference') || 'Référence' }}</label>
                <input
                    v-model="form.reference"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.reference') || 'Référence'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.reference" class="text-danger">
                    <strong>{{ form.errors.reference }}</strong>
                </span>
            </div>
        </div>
        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') || 'État' }} <span class="text-danger">*</span></label>
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
                <label>{{ t('common.date_acquisition') || 'Date d\'acquisition' }}</label>
                <input
                    v-model="form.date_acquisition"
                    type="date"
                    class="form-control"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_acquisition" class="text-danger">
                    <strong>{{ form.errors.date_acquisition }}</strong>
                </span>
            </div>
        </div>
        <!-- Prix -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.prix') || 'Prix (en centimes)' }}</label>
                <input
                    v-model.number="form.prix_cents"
                    type="number"
                    class="form-control"
                    min="0"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.prix_cents" class="text-danger">
                    <strong>{{ form.errors.prix_cents }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
