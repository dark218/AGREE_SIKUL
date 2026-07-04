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
                <label>{{ t('fields.code') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle')" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Date Debut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_debut') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="date" v-model="form.date_debut" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_debut" class="text-danger">
                    <strong>{{ form.errors.date_debut }}</strong>
                </span>
            </div>
        </div>
        <!-- Date Fin -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_fin') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="date" v-model="form.date_fin" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_fin" class="text-danger">
                    <strong>{{ form.errors.date_fin }}</strong>
                </span>
            </div>
        </div>
        <!-- Duree -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.duree') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="number" v-model.number="form.duree" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.duree" class="text-danger">
                    <strong>{{ form.errors.duree }}</strong>
                </span>
            </div>
        </div>
        <!-- État -->
        <div class="col-sm-6">
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
