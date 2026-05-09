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
    sections: {
        type: Array,
        default: () => [],
    },
    niveaux: {
        type: Array,
        default: () => [],
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    pays: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
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
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly" required>
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') || 'Libellé' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle')" :disabled="isReadOnly" required>
                <span v-if="form.errors?.libelle" class="text-danger"><strong>{{ form.errors.libelle }}</strong></span>
            </div>
        </div>
        <!-- Ecole -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.ecole') || 'École' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.ecole_id"
                    :options="props.ecoles"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ecole_id" class="text-danger"><strong>{{ form.errors.ecole_id }}</strong></span>
            </div>
        </div>
        <!-- Section -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }}</label>
                <SearchableSelect
                    v-model="form.section_id"
                    :options="props.sections"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.section_id" class="text-danger"><strong>{{ form.errors.section_id }}</strong></span>
            </div>
        </div>
        <!-- Niveau -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.niveau') || 'Niveau' }}</label>
                <SearchableSelect
                    v-model="form.niveau_id"
                    :options="props.niveaux"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.niveau_id" class="text-danger"><strong>{{ form.errors.niveau_id }}</strong></span>
            </div>
        </div>
        <!-- Cycle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.cycle') || 'Cycle d\'enseignement' }}</label>
                <SearchableSelect
                    v-model="form.cycle_id"
                    :options="props.cycles"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.cycle_id" class="text-danger"><strong>{{ form.errors.cycle_id }}</strong></span>
            </div>
        </div>
        <!-- Pays -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.pays') || 'Pays' }}</label>
                <SearchableSelect
                    v-model="form.pays_id"
                    :options="props.pays"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.pays_id" class="text-danger"><strong>{{ form.errors.pays_id }}</strong></span>
            </div>
        </div>
        <!-- Poids / Coefficient global -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.poids') || 'Coefficient' }}</label>
                <input type="number" v-model.number="form.poids" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.poids" class="text-danger"><strong>{{ form.errors.poids }}</strong></span>
            </div>
        </div>
        <!-- Est éliminatoire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label class="form-check-label">
                    <input type="checkbox" v-model="form.est_eliminatoire" class="form-check-input" :disabled="isReadOnly">
                    {{ t('fields.est_eliminatoire') || 'Examen éliminatoire' }}
                </label>
                <span v-if="form.errors?.est_eliminatoire" class="text-danger"><strong>{{ form.errors.est_eliminatoire }}</strong></span>
            </div>
        </div>
        <!-- Note éliminatoire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.note_eliminatoire') || 'Note éliminatoire (seuil)' }}</label>
                <input type="number" v-model.number="form.note_eliminatoire" class="form-control" step="0.01" min="0" max="20" :disabled="isReadOnly">
                <span v-if="form.errors?.note_eliminatoire" class="text-danger"><strong>{{ form.errors.note_eliminatoire }}</strong></span>
            </div>
        </div>
        <!-- Durée en minutes -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.duree_minutes') || 'Durée (minutes)' }}</label>
                <input type="number" v-model.number="form.duree_minutes" class="form-control" min="0" :disabled="isReadOnly">
                <span v-if="form.errors?.duree_minutes" class="text-danger"><strong>{{ form.errors.duree_minutes }}</strong></span>
            </div>
        </div>
        <!-- Est rattrapage -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label class="form-check-label">
                    <input type="checkbox" v-model="form.est_rattrapage" class="form-check-input" :disabled="isReadOnly">
                    {{ t('fields.est_rattrapage') || 'Examen de rattrapage' }}
                </label>
                <span v-if="form.errors?.est_rattrapage" class="text-danger"><strong>{{ form.errors.est_rattrapage }}</strong></span>
            </div>
        </div>
        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') || 'Statut' }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger"><strong>{{ form.errors.etat }}</strong></span>
            </div>
        </div>
    </div>
</template>
