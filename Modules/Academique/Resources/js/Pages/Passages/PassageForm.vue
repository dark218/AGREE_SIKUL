<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    sections: Array,
    cycles: Array,
    niveaux: Array,
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
        <!-- Section -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.section_id"
                    :options="sections"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('fields.section') || 'Section'"
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
                <label>{{ t('common.cycle') || 'Cycle' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.cycle_enseignement_id"
                    :options="cycles"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.cycle') || 'Cycle'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.cycle_enseignement_id" class="text-danger">
                    <strong>{{ form.errors.cycle_enseignement_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Niveau -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.niveau') || 'Niveau' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.niveau_id"
                    :options="niveaux"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.niveau') || 'Niveau'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.niveau_id" class="text-danger">
                    <strong>{{ form.errors.niveau_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Niveau Supérieur -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.niveau_superieur') || 'Niveau Supérieur' }}</label>
                <SearchableSelect
                    v-model="form.niveau_superieur_id"
                    :options="niveaux"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('fields.niveau_superieur') || 'Niveau Supérieur'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.niveau_superieur_id" class="text-danger">
                    <strong>{{ form.errors.niveau_superieur_id }}</strong>
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
