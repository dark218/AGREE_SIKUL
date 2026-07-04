<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    annees_scolaires: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
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

// Auto-calcul de la durée à chaque changement de date
watch(() => [props.form.date_debut, props.form.date_fin], ([d1, d2]) => {
    if (d1 && d2) {
        const start = new Date(d1);
        const end = new Date(d2);
        const diff = Math.round((end - start) / (1000 * 60 * 60 * 24));
        if (diff >= 0) {
            props.form.duree = diff;
        }
    }
});
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly" />
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <!-- Libellé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') || 'Libellé' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle')" :disabled="isReadOnly" />
                <span v-if="form.errors?.libelle" class="text-danger"><strong>{{ form.errors.libelle }}</strong></span>
            </div>
        </div>
        <!-- Année scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_scolaire') || 'Année scolaire' }} <span class="text-danger">*</span></label>
                <SearchableSelect v-model="form.annee_scolaire_id" :options="annees_scolaires" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.annee_scolaire_id" class="text-danger"><strong>{{ form.errors.annee_scolaire_id }}</strong></span>
            </div>
        </div>
        <!-- Date début -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.date_debut') || 'Date début' }} <span class="text-danger">*</span></label>
                <input type="date" v-model="form.date_debut" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.date_debut" class="text-danger"><strong>{{ form.errors.date_debut }}</strong></span>
            </div>
        </div>
        <!-- Date fin -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.date_fin') || 'Date fin' }} <span class="text-danger">*</span></label>
                <input type="date" v-model="form.date_fin" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.date_fin" class="text-danger"><strong>{{ form.errors.date_fin }}</strong></span>
            </div>
        </div>
        <!-- Durée (auto) -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.duree') || 'Durée (jours)' }}
                    <small class="text-muted">(auto)</small>
                </label>
                <input type="number" v-model="form.duree" class="form-control" readonly disabled />
                <span v-if="form.errors?.duree" class="text-danger"><strong>{{ form.errors.duree }}</strong></span>
            </div>
        </div>
        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut de disponibilité' }}</label>
                <SearchableSelect v-model="form.etat" :options="statusOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.etat" class="text-danger"><strong>{{ form.errors.etat }}</strong></span>
            </div>
        </div>
    </div>
</template>
