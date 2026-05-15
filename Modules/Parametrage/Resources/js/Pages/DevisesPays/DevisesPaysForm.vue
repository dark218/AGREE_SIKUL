<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    pays: { type: Array, default: () => [] },
    devises: { type: Array, default: () => [] },
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
                <label>{{ t('fields.code') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly" />
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <!-- Pays -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.country') || 'Pays' }} <span class="text-danger">*</span></label>
                <SearchableSelect v-model="form.pays_id" :options="pays" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.pays_id" class="text-danger"><strong>{{ form.errors.pays_id }}</strong></span>
            </div>
        </div>
        <!-- Devise -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.devise') || 'Devise' }} <span class="text-danger">*</span></label>
                <SearchableSelect v-model="form.devise_id" :options="devises" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.devise_id" class="text-danger"><strong>{{ form.errors.devise_id }}</strong></span>
            </div>
        </div>
        <!-- Taux de change -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.taux_change') || 'Taux de change' }}
                    <small class="text-muted">(vers la devise principale)</small>
                </label>
                <input type="number" step="0.0001" min="0" v-model="form.taux_change" class="form-control" placeholder="1.0000" :disabled="isReadOnly" />
                <span v-if="form.errors?.taux_change" class="text-danger"><strong>{{ form.errors.taux_change }}</strong></span>
            </div>
        </div>
        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }}</label>
                <SearchableSelect v-model="form.etat" :options="statusOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.etat" class="text-danger"><strong>{{ form.errors.etat }}</strong></span>
            </div>
        </div>
    </div>
</template>
