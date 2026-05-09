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
    niveaux: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
});
const isReadOnly = props.mode === 'show';
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const typeMatieresOptions = [
    { id: 'theorique', libelle: 'Théorique' },
    { id: 'pratique', libelle: 'Pratique' },
    { id: 'tp', libelle: 'TP' },
    { id: 'td', libelle: 'TD' },
    { id: 'projet', libelle: 'Projet' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')">
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle')">
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Ecole -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.ecole') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.ecole_id"
                    :options="props.ecoles"
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
                <span v-if="form.errors?.niveau_id" class="text-danger">
                    <strong>{{ form.errors.niveau_id }}</strong>
                </span>
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
                <span v-if="form.errors?.section_id" class="text-danger">
                    <strong>{{ form.errors.section_id }}</strong>
                </span>
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
                <span v-if="form.errors?.cycle_id" class="text-danger">
                    <strong>{{ form.errors.cycle_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Type de matière -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type_matiere') || 'Type de matière' }}</label>
                <SearchableSelect
                    v-model="form.type_matiere"
                    :options="typeMatieresOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_matiere" class="text-danger">
                    <strong>{{ form.errors.type_matiere }}</strong>
                </span>
            </div>
        </div>
        <!-- Note Max -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.note_max') || 'Note maximale' }}</label>
                <input type="number" v-model.number="form.note_max" class="form-control" :disabled="isReadOnly" step="0.01">
                <span v-if="form.errors?.note_max" class="text-danger">
                    <strong>{{ form.errors.note_max }}</strong>
                </span>
            </div>
        </div>
        <!-- Coefficient -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.coefficient') || 'Coefficient' }}</label>
                <input type="number" v-model.number="form.coefficient" class="form-control" :disabled="isReadOnly" step="0.01">
                <span v-if="form.errors?.coefficient" class="text-danger">
                    <strong>{{ form.errors.coefficient }}</strong>
                </span>
            </div>
        </div>
        <!-- Volume horaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.volume_horaire') || 'Volume horaire (heures)' }}</label>
                <input type="number" v-model.number="form.volume_horaire" class="form-control" placeholder="Heures" :disabled="isReadOnly" min="0">
                <span v-if="form.errors?.volume_horaire" class="text-danger">
                    <strong>{{ form.errors.volume_horaire }}</strong>
                </span>
            </div>
        </div>
        <!-- Est obligatoire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label class="form-check-label">
                    <input type="checkbox" v-model="form.est_obligatoire" class="form-check-input" :disabled="isReadOnly">
                    {{ t('fields.est_obligatoire') || 'Matière obligatoire' }}
                </label>
                <span v-if="form.errors?.est_obligatoire" class="text-danger">
                    <strong>{{ form.errors.est_obligatoire }}</strong>
                </span>
            </div>
        </div>
        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') }}</label>
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
