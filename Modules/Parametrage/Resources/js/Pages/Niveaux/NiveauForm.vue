<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    ecoles: {
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
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'non_actif', libelle: 'Inactif' },
    { id: 'suspendu', libelle: 'Suspendu' },
];
</script>
<template>
    <div class="row g-3 custom-input">
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
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') || 'Libellé' }}</label>
                <input
                    v-model="form.libelle"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.libelle') || 'Libellé'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Cycle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.cycle') || 'Cycle' }}</label>
                <input
                    v-model="form.cycle"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.cycle') || 'Cycle'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.cycle" class="text-danger">
                    <strong>{{ form.errors.cycle }}</strong>
                </span>
            </div>
        </div>
        <!-- École -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.school') || 'École' }}</label>
                <SearchableSelect
                    v-model="form.ecole_id"
                    :options="ecoles"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ecole_id" class="text-danger">
                    <strong>{{ form.errors.ecole_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Ordre -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.order') || 'Ordre' }}</label>
                <input
                    v-model.number="form.ordre"
                    type="number"
                    class="form-control"
                    :placeholder="t('fields.order') || 'Ordre'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ordre" class="text-danger">
                    <strong>{{ form.errors.ordre }}</strong>
                </span>
            </div>
        </div>
        <!-- Âge minimum -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.age_min') || 'Âge minimum' }}</label>
                <input
                    v-model.number="form.age_min"
                    type="number"
                    class="form-control"
                    :placeholder="t('fields.age_min') || 'Âge minimum'"
                    :disabled="isReadOnly"
                    min="0"
                />
                <span v-if="form.errors?.age_min" class="text-danger">
                    <strong>{{ form.errors.age_min }}</strong>
                </span>
            </div>
        </div>
        <!-- Âge maximum -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.age_max') || 'Âge maximum' }}</label>
                <input
                    v-model.number="form.age_max"
                    type="number"
                    class="form-control"
                    :placeholder="t('fields.age_max') || 'Âge maximum'"
                    :disabled="isReadOnly"
                    min="0"
                />
                <span v-if="form.errors?.age_max" class="text-danger">
                    <strong>{{ form.errors.age_max }}</strong>
                </span>
            </div>
        </div>
        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }}</label>
                <SearchableSelect
                    v-model="form.statut"
                    :options="statusOptions"
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
