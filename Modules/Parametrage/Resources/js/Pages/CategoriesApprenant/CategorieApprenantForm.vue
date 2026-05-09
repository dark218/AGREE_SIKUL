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
const ecoles = computed(() => page.props.ecoles || []);
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
        <!-- Libellé -->
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
        <!-- École -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.school') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <SearchableSelect
                    v-model="form.ecole_id"
                    :options="ecoles"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ecole_id" class="text-danger">
                    <strong>{{ form.errors.ecole_id }}</strong>
                </span>
            </div>
        </div>
        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.status') }}
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
