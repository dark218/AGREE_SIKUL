<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    campuses: { type: Array, default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const isReadOnly = computed(() => props.mode === 'show');

const campusesOptions = computed(() => props.campuses.map(c => ({ id: c.id, libelle: c.nom || c.libelle })));

const dispoOptions = [
    { id: 'disponible', libelle: 'Disponible' },
    { id: 'indisponible', libelle: 'Indisponible' },
    { id: 'maintenance', libelle: 'En maintenance' },
];

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }}</label>
                <input v-model="form.code" type="text" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <div class="col-sm-8">
            <div class="mb-3">
                <label>{{ t('fields.label') || 'Libellé' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input v-model="form.libelle" type="text" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.libelle" class="text-danger"><strong>{{ form.errors.libelle }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.location') || 'Localisation' }}</label>
                <input v-model="form.localisation" type="text" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.localisation" class="text-danger"><strong>{{ form.errors.localisation }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') || 'Campus' }}</label>
                <SearchableSelect v-model="form.campus_id" :options="campusesOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.campus_id" class="text-danger"><strong>{{ form.errors.campus_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.manager') || 'Responsable' }}</label>
                <input v-model="form.responsable" type="text" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.responsable" class="text-danger"><strong>{{ form.errors.responsable }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.availability_status') || 'Statut de disponibilité' }}</label>
                <SearchableSelect v-model="form.statut_disponibilite" :options="dispoOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.statut_disponibilite" class="text-danger"><strong>{{ form.errors.statut_disponibilite }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'État' }}</label>
                <SearchableSelect v-model="form.etat" :options="statusOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.etat" class="text-danger"><strong>{{ form.errors.etat }}</strong></span>
            </div>
        </div>
    </div>
</template>
