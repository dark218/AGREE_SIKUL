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
});

const isReadOnly = props.mode === 'show';

const getPrixEnEuros = () => {
    return (props.form.prix_unitaire_cents / 100).toFixed(2);
};
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Catégorie -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.categorie') || 'Catégorie' }} <span class="text-danger">*</span></label>
                <select v-model="form.categorie_fourniture_id" class="form-control" required :disabled="isReadOnly">
                    <option value="">{{ t('common.select') || 'Sélectionner...' }}</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.libelle }}
                    </option>
                </select>
                <span v-if="form.errors?.categorie_fourniture_id" class="text-danger">
                    <strong>{{ form.errors.categorie_fourniture_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Libellé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.libelle') || 'Libellé' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.libelle"
                    type="text"
                    class="form-control"
                    :placeholder="t('common.libelle') || 'Libellé'"
                    required
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>

        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }}</label>
                <input
                    v-model="form.code"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.code') || 'Code'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>

        <!-- Quantité -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.quantite') || 'Quantité' }} <span class="text-danger">*</span></label>
                <input
                    v-model.number="form.quantite"
                    type="number"
                    class="form-control"
                    :placeholder="t('fields.quantite') || 'Quantité'"
                    min="0"
                    required
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.quantite" class="text-danger">
                    <strong>{{ form.errors.quantite }}</strong>
                </span>
            </div>
        </div>

        <!-- Prix unitaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.prix_unitaire') || 'Prix unitaire' }}</label>
                <input
                    v-model.number="form.prix_unitaire_cents"
                    type="number"
                    class="form-control"
                    :placeholder="t('fields.prix_unitaire') || 'Prix unitaire'"
                    step="100"
                    min="0"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">{{ getPrixEnEuros() }} €</small>
                <span v-if="form.errors?.prix_unitaire_cents" class="text-danger">
                    <strong>{{ form.errors.prix_unitaire_cents }}</strong>
                </span>
            </div>
        </div>

        <!-- Fournisseur -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.fournisseur') || 'Fournisseur' }}</label>
                <input
                    v-model="form.fournisseur"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.fournisseur') || 'Fournisseur'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.fournisseur" class="text-danger">
                    <strong>{{ form.errors.fournisseur }}</strong>
                </span>
            </div>
        </div>

        <!-- Date d'acquisition -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_acquisition') || 'Date d\'acquisition' }}</label>
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

        <!-- Localisation -->
        <div class="col-sm-12">
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

        <!-- Statut (dernière position) -->
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }} <span class="text-danger">*</span></label>
                <select v-model="form.statut" class="form-control" required :disabled="isReadOnly">
                    <option value="disponible">{{ t('statut.disponible') || 'Disponible' }}</option>
                    <option value="rupture">{{ t('statut.rupture') || 'Rupture' }}</option>
                    <option value="commandee">{{ t('statut.commandee') || 'Commandée' }}</option>
                </select>
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
