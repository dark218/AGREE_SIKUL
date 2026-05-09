<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    pointsVente: {
        type: Array,
        default: () => [],
    },
    devise: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
    defaultDevise: {
        type: String,
        default: 'XOF',
    },
});
const isReadOnly = computed(() => props.mode === 'show');
</script>
<template>
    <div class="custom-input" @keydown.enter.prevent>
        <div class="row g-3">
            <!-- Point de Vente -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('modules.business.pointsvente.singular') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <StylishSelect
                        v-model="form.points_vente_id"
                        :options="pointsVente"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('actions.select') + ' ' + t('modules.business.pointsvente.singular')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.points_vente_id" class="text-danger">
                        <strong>{{ form.errors.points_vente_id }}</strong>
                    </span>
                </div>
            </div>
            <!-- SKU -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('modules.business.articles.sku') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <input
                        type="text"
                        v-model="form.sku"
                        class="form-control"
                        :placeholder="t('modules.business.articles.sku')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.sku" class="text-danger">
                        <strong>{{ form.errors.sku }}</strong>
                    </span>
                </div>
            </div>
            <!-- Nom -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('fields.name') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <input
                        type="text"
                        v-model="form.nom"
                        class="form-control"
                        :placeholder="t('fields.name')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.nom" class="text-danger">
                        <strong>{{ form.errors.nom }}</strong>
                    </span>
                </div>
            </div>
            <!-- Marque -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('modules.business.articles.brand') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <input
                        type="text"
                        v-model="form.marque"
                        class="form-control"
                        :placeholder="t('modules.business.articles.brand')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.marque" class="text-danger">
                        <strong>{{ form.errors.marque }}</strong>
                    </span>
                </div>
            </div>
            <!-- Prix -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('modules.business.articles.price') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    
                    <input
                        type="number"
                        v-model="form.prix_cents"
                        class="form-control"
                        :placeholder="t('modules.business.articles.price')"
                        :disabled="isReadOnly"
                        min="0"
                        step="0.01"
                    />
                    <span v-if="form.errors?.prix_cents" class="text-danger">
                        <strong>{{ form.errors.prix_cents }}</strong>
                    </span>
                </div>
            </div>
            <!-- Devise -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('modules.business.articles.currency') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <StylishSelect
                        v-model="form.devise"
                        :options="devise"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('actions.select') + ' ' + t('modules.business.articles.currency')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.devise" class="text-danger">
                        <strong>{{ form.errors.devise }}</strong>
                    </span>
                </div>
            </div>
            <!-- Description -->
            <div class="col-md-12">
                <div class="mb-3">
                    <label>{{ t('fields.description') }}</label>
                    <textarea
                        v-model="form.description"
                        class="form-control"
                        :placeholder="t('fields.description')"
                        :disabled="isReadOnly"
                        rows="3"
                    ></textarea>
                    <span v-if="form.errors?.description" class="text-danger">
                        <strong>{{ form.errors.description }}</strong>
                    </span>
                </div>
            </div>
            <!-- Quantite Stock -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('modules.business.articles.stock') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <input
                        type="number"
                        v-model="form.quantite_stock"
                        class="form-control"
                        :placeholder="t('modules.business.articles.stock')"
                        :disabled="isReadOnly"
                        min="0"
                    />
                    <span v-if="form.errors?.quantite_stock" class="text-danger">
                        <strong>{{ form.errors.quantite_stock }}</strong>
                    </span>
                </div>
            </div>
            <!-- Seuil Alert Stock -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('modules.business.articles.stockThreshold') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <input
                        type="number"
                        v-model="form.seuil_alert_stock"
                        class="form-control"
                        :placeholder="t('modules.business.articles.stockThreshold')"
                        :disabled="isReadOnly"
                        min="0"
                    />
                    <span v-if="form.errors?.seuil_alert_stock" class="text-danger">
                        <strong>{{ form.errors.seuil_alert_stock }}</strong>
                    </span>
                </div>
            </div>
            <!-- Taxes JSON -->
            <div class="col-md-12">
                <div class="mb-3">
                    <label>{{ t('modules.business.articles.taxes') }}</label>
                    <textarea
                        v-model="form.taxes_json"
                        class="form-control"
                        placeholder='{"tva": 18, "autres": 0}'
                        :disabled="isReadOnly"
                        rows="3"
                    ></textarea>
                    <small class="text-muted">{{ t('modules.business.articles.taxesHelp') }}</small>
                    <span v-if="form.errors?.taxes_json" class="text-danger">
                        <strong>{{ form.errors.taxes_json }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
