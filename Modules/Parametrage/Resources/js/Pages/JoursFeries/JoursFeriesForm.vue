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
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly" />
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle')" :disabled="isReadOnly" />
                <span v-if="form.errors?.libelle" class="text-danger"><strong>{{ form.errors.libelle }}</strong></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.date') || 'Date' }}</label>
                <input type="date" v-model="form.date" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.date" class="text-danger"><strong>{{ form.errors.date }}</strong></span>
            </div>
        </div>
        <div class="col-md-4" v-if="pays && pays.length > 0">
            <div class="mb-3">
                <label>{{ t('fields.country') || 'Pays' }}</label>
                <SearchableSelect v-model="form.pays_id" :options="pays" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.pays_id" class="text-danger"><strong>{{ form.errors.pays_id }}</strong></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.status') }}</label>
                <SearchableSelect v-model="form.etat" :options="statusOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.etat" class="text-danger"><strong>{{ form.errors.etat }}</strong></span>
            </div>
        </div>
        <!-- Férié récurrent -->
        <div class="col-md-4">
            <div class="mb-3">
                <label class="d-block">Férié récurrent</label>
                <div class="form-check mt-2">
                    <input
                        id="est_recurrent"
                        type="checkbox"
                        class="form-check-input"
                        v-model="form.est_recurrent"
                        :disabled="isReadOnly"
                    />
                    <label class="form-check-label" for="est_recurrent">
                        Se répète chaque année
                    </label>
                </div>
                <span v-if="form.errors?.est_recurrent" class="text-danger"><strong>{{ form.errors.est_recurrent }}</strong></span>
            </div>
        </div>
    </div>
</template>
