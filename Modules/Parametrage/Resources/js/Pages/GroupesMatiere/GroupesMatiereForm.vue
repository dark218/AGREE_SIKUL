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
    matieres: {
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    pays: {
        type: Array,
        default: () => [],
    },
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
        <!-- Niveau Id -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.niveau_id') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.niveau_id"
                    :options="props.niveaux"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.niveau_id" class="text-danger">
                    <strong>{{ form.errors.niveau_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Section Id -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section_id') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.section_id"
                    :options="props.sections"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.section_id" class="text-danger">
                    <strong>{{ form.errors.section_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Cycle Id -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.cycle_id') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.cycle_id"
                    :options="props.cycles"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.cycle_id" class="text-danger">
                    <strong>{{ form.errors.cycle_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Matiere 1 Id -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Matière 1</label>
                <SearchableSelect
                    v-model="form.matiere1_id"
                    :options="props.matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere1_id" class="text-danger">
                    <strong>{{ form.errors.matiere1_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Matiere 2 Id -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Matière 2</label>
                <SearchableSelect
                    v-model="form.matiere2_id"
                    :options="props.matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere2_id" class="text-danger">
                    <strong>{{ form.errors.matiere2_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Matiere 3 Id -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Matière 3</label>
                <SearchableSelect
                    v-model="form.matiere3_id"
                    :options="props.matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere3_id" class="text-danger">
                    <strong>{{ form.errors.matiere3_id }}</strong>
                </span>
            </div>
        </div>
                <!-- Année Scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_scolaire') }}</label>
                <SearchableSelect
                    v-model="form.annee_scolaire_id"
                    :options="props.anneesScolaires"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.annee_scolaire_id" class="text-danger">
                    <strong>{{ form.errors.annee_scolaire_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Pays -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.pays') }}</label>
                <SearchableSelect
                    v-model="form.pays_id"
                    :options="props.pays"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.pays_id" class="text-danger">
                    <strong>{{ form.errors.pays_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Matière 4 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Matière 4</label>
                <SearchableSelect
                    v-model="form.matiere4_id"
                    :options="props.matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere4_id" class="text-danger">
                    <strong>{{ form.errors.matiere4_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Matière 5 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Matière 5</label>
                <SearchableSelect
                    v-model="form.matiere5_id"
                    :options="props.matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere5_id" class="text-danger">
                    <strong>{{ form.errors.matiere5_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Matière 6 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Matière 6</label>
                <SearchableSelect
                    v-model="form.matiere6_id"
                    :options="props.matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere6_id" class="text-danger">
                    <strong>{{ form.errors.matiere6_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Matière 7 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Matière 7</label>
                <SearchableSelect
                    v-model="form.matiere7_id"
                    :options="props.matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere7_id" class="text-danger">
                    <strong>{{ form.errors.matiere7_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Matière 8 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Matière 8</label>
                <SearchableSelect
                    v-model="form.matiere8_id"
                    :options="props.matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere8_id" class="text-danger">
                    <strong>{{ form.errors.matiere8_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Matière 9 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Matière 9</label>
                <SearchableSelect
                    v-model="form.matiere9_id"
                    :options="props.matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere9_id" class="text-danger">
                    <strong>{{ form.errors.matiere9_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Matière 10 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Matière 10</label>
                <SearchableSelect
                    v-model="form.matiere10_id"
                    :options="props.matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere10_id" class="text-danger">
                    <strong>{{ form.errors.matiere10_id }}</strong>
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
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
