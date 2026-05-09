<script setup>
import { defineProps } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
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

const isReadOnly = props.mode === 'show';

const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const typesManuels = [
    { id: 'textbook', libelle: 'Livre de texte' },
    { id: 'reference', libelle: 'Référence' },
    { id: 'workbook', libelle: 'Cahier d\'exercices' },
    { id: 'guide', libelle: 'Guide pédagogique' },
    { id: 'other', libelle: 'Autre' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Informations Générales -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">{{ t('common.basic_information') || 'Informations générales' }}</h5>
        </div>

        <!-- Année Scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_scolaire') || 'Année scolaire' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.annee_scolaire_id"
                    :options="anneesScolaires"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.annee_scolaire_id" class="text-danger">
                    <strong>{{ form.errors.annee_scolaire_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Ecole -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ecole') || 'Ecole' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.ecole_id"
                    :options="ecoles"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.ecole_id" class="text-danger">
                    <strong>{{ form.errors.ecole_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Section -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.section_id"
                    :options="sections"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.section_id" class="text-danger">
                    <strong>{{ form.errors.section_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Pays -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.pays') || 'Pays' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.pays_id"
                    :options="pays"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.pays_id" class="text-danger">
                    <strong>{{ form.errors.pays_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 2: Détails du Manuel -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.details') || 'Détails du manuel' }}</h5>
        </div>

        <!-- Type de Manuel -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type_manuel') || 'Type de manuel' }} <span class="text-danger">*</span></label>
                <select v-model="form.type_manuel" class="form-control" :disabled="isReadOnly">
                    <option value="">{{ t('actions.select') || '-- Sélectionner --' }}</option>
                    <option v-for="type in typesManuels" :key="type.id" :value="type.id">
                        {{ type.libelle }}
                    </option>
                </select>
                <span v-if="form.errors?.type_manuel" class="text-danger">
                    <strong>{{ form.errors.type_manuel }}</strong>
                </span>
            </div>
        </div>

        <!-- Année d'édition -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_edition') || 'Année d\'édition' }}</label>
                <input
                    type="number"
                    v-model.number="form.annee_edition"
                    class="form-control"
                    :placeholder="new Date().getFullYear()"
                    min="1900"
                    :max="new Date().getFullYear() + 10"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.annee_edition" class="text-danger">
                    <strong>{{ form.errors.annee_edition }}</strong>
                </span>
            </div>
        </div>

        <!-- Titre du Manuel -->
        <div class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.titre_manuel') || 'Titre du manuel' }} <span class="text-danger">*</span></label>
                <input
                    type="text"
                    v-model="form.titre_manuel"
                    class="form-control"
                    :placeholder="t('fields.titre_manuel')"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.titre_manuel" class="text-danger">
                    <strong>{{ form.errors.titre_manuel }}</strong>
                </span>
            </div>
        </div>

        <!-- Auteurs -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.auteurs') || 'Auteur(s)' }}</label>
                <input
                    type="text"
                    v-model="form.auteurs"
                    class="form-control"
                    :placeholder="t('fields.auteurs')"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.auteurs" class="text-danger">
                    <strong>{{ form.errors.auteurs }}</strong>
                </span>
            </div>
        </div>

        <!-- Editeur -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.editeur') || 'Éditeur' }}</label>
                <input
                    type="text"
                    v-model="form.editeur"
                    class="form-control"
                    :placeholder="t('fields.editeur')"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.editeur" class="text-danger">
                    <strong>{{ form.errors.editeur }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 3: État -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.status') || 'État' }}</h5>
        </div>

        <!-- État d'activation -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>État d'activation <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="etatOptions"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.section-title {
    font-weight: 600;
    color: #333;
    font-size: 1.1rem;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 0.5rem;
}
</style>
