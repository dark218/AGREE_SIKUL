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
    pays: {
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
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.code') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.code"
                    class="form-control"
                    :placeholder="t('fields.code')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.label') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.libelle"
                    class="form-control"
                    :placeholder="t('fields.label')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Code ISO (3 letters, e.g., USD, EUR, XOF) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.code_iso') || 'Code ISO' }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.code_iso"
                    class="form-control"
                    placeholder="USD, EUR, XOF..."
                    :disabled="isReadOnly"
                    maxlength="3"
                    style="text-transform: uppercase;"
                />
                <small class="text-muted">Ex: USD, EUR, XOF (3 caractères)</small>
                <span v-if="form.errors?.code_iso" class="text-danger d-block">
                    <strong>{{ form.errors.code_iso }}</strong>
                </span>
            </div>
        </div>
        <!-- Symbol -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.symbol') || 'Symbole' }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.symbol"
                    class="form-control"
                    placeholder="$, €, FCFA..."
                    :disabled="isReadOnly"
                    maxlength="10"
                />
                <small class="text-muted">Ex: $, €, ₦, FCFA</small>
                <span v-if="form.errors?.symbol" class="text-danger d-block">
                    <strong>{{ form.errors.symbol }}</strong>
                </span>
            </div>
        </div>
        <!-- Decimal Places -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.decimal_places') || 'Décimales' }}
                </label>
                <input
                    type="number"
                    v-model.number="form.decimal_places"
                    class="form-control"
                    :placeholder="'2'"
                    :disabled="isReadOnly"
                    min="0"
                    max="8"
                />
                <small class="text-muted">Nombre de chiffres après la virgule</small>
                <span v-if="form.errors?.decimal_places" class="text-danger d-block">
                    <strong>{{ form.errors.decimal_places }}</strong>
                </span>
            </div>
        </div>
        <!-- Exchange Rate -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.exchange_rate') || 'Taux de Change' }}
                </label>
                <input
                    type="number"
                    v-model.number="form.exchange_rate"
                    class="form-control"
                    placeholder="1.000000"
                    :disabled="isReadOnly"
                    step="0.000001"
                    min="0"
                />
                <small class="text-muted">Par rapport à la devise de référence</small>
                <span v-if="form.errors?.exchange_rate" class="text-danger d-block">
                    <strong>{{ form.errors.exchange_rate }}</strong>
                </span>
            </div>
        </div>
        <!-- Is Default -->
        <div class="col-sm-6">
            <div class="mb-3">
                <div class="form-check">
                    <input
                        type="checkbox"
                        :checked="form.is_default"
                        @change="form.is_default = $event.target.checked"
                        class="form-check-input"
                        :disabled="isReadOnly"
                        id="is_default"
                    />
                    <label class="form-check-label" for="is_default">
                        {{ t('fields.is_default') || 'Devise par défaut' }}
                    </label>
                </div>
                <small class="text-muted d-block">Marquer comme devise par défaut de l\'établissement</small>
                <span v-if="form.errors?.is_default" class="text-danger d-block">
                    <strong>{{ form.errors.is_default }}</strong>
                </span>
            </div>
        </div>
        <!-- Pays -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.country') || 'Pays' }}
                </label>
                <SearchableSelect
                    v-model="form.pays_id"
                    :options="props.pays"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.pays_id" class="text-danger">
                    <strong>{{ form.errors.pays_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Etat -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.status') || 'Statut' }}
                </label>
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
