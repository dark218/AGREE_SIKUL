<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
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
});
const isReadOnly = props.mode === 'show';
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code') || 'Code'" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Code 3 Chars -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.code_3_chars') || 'Code 3 Chars' }}</label>
                <input type="text" v-model="form.code_3_chars" class="form-control" :placeholder="t('fields.code_3_chars') || 'Code 3 Chars'" :disabled="isReadOnly">
                <span v-if="form.errors?.code_3_chars" class="text-danger">
                    <strong>{{ form.errors.code_3_chars }}</strong>
                </span>
            </div>
        </div>
        <!-- Code 2 Chars -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.code_2_chars') || 'Code 2 Chars' }}</label>
                <input type="text" v-model="form.code_2_chars" class="form-control" :placeholder="t('fields.code_2_chars') || 'Code 2 Chars'" :disabled="isReadOnly">
                <span v-if="form.errors?.code_2_chars" class="text-danger">
                    <strong>{{ form.errors.code_2_chars }}</strong>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.label') || 'Libellé' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.label') || 'Libellé'" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Capitale -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.capitale') || 'Capitale' }}</label>
                <input type="text" v-model="form.capitale" class="form-control" :placeholder="t('fields.capitale') || 'Capitale'" :disabled="isReadOnly">
                <span v-if="form.errors?.capitale" class="text-danger">
                    <strong>{{ form.errors.capitale }}</strong>
                </span>
            </div>
        </div>
        <!-- Nombre -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.nombre') || 'Nombre' }}</label>
                <input type="number" v-model.number="form.nombre" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.nombre" class="text-danger">
                    <strong>{{ form.errors.nombre }}</strong>
                </span>
            </div>
        </div>
        <!-- Continent -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.continent') || 'Continent' }}</label>
                <input type="text" v-model="form.continent" class="form-control" :placeholder="t('fields.continent') || 'Continent'" :disabled="isReadOnly">
                <span v-if="form.errors?.continent" class="text-danger">
                    <strong>{{ form.errors.continent }}</strong>
                </span>
            </div>
        </div>
        <!-- État -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }}</label>
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
