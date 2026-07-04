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
const typeFichierOptions = [
    { id: 'document', libelle: 'Document' },
    { id: 'image', libelle: 'Image' },
    { id: 'video', libelle: 'Vidéo' },
    { id: 'audio', libelle: 'Audio' },
    { id: 'archive', libelle: 'Archive' },
    { id: 'autre', libelle: 'Autre' },
];
const entiteLieeOptions = [
    { id: 'apprenant', libelle: 'Apprenant' },
    { id: 'enseignant', libelle: 'Enseignant' },
    { id: 'inscription', libelle: 'Inscription' },
    { id: 'classe', libelle: 'Classe' },
    { id: 'autre', libelle: 'Autre' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Libelle -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle')" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Type de fichier -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.type_fichier') || 'Type de fichier' }}</label>
                <SearchableSelect
                    v-model="form.type_fichier"
                    :options="typeFichierOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_fichier" class="text-danger">
                    <strong>{{ form.errors.type_fichier }}</strong>
                </span>
            </div>
        </div>
        <!-- Extensions autorisées -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.extensions_autorisees') || 'Extensions autorisées' }}</label>
                <input type="text" v-model="form.extensions_autorisees" class="form-control" placeholder="pdf, jpg, png" :disabled="isReadOnly">
                <small class="text-muted">Séparées par des virgules</small>
                <span v-if="form.errors?.extensions_autorisees" class="text-danger">
                    <strong>{{ form.errors.extensions_autorisees }}</strong>
                </span>
            </div>
        </div>
        <!-- Taille maximum (Mo) -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.taille_max_mo') || 'Taille maximum (Mo)' }}</label>
                <input type="number" v-model.number="form.taille_max_mo" class="form-control" min="1" :disabled="isReadOnly">
                <span v-if="form.errors?.taille_max_mo" class="text-danger">
                    <strong>{{ form.errors.taille_max_mo }}</strong>
                </span>
            </div>
        </div>
        <!-- Est obligatoire -->
        <div class="col-md-4">
            <div class="mb-3">
                <label class="form-check-label">
                    <input type="checkbox" v-model="form.est_obligatoire" class="form-check-input" :disabled="isReadOnly">
                    {{ t('fields.est_obligatoire') || 'Est obligatoire' }}
                </label>
                <span v-if="form.errors?.est_obligatoire" class="text-danger">
                    <strong>{{ form.errors.est_obligatoire }}</strong>
                </span>
            </div>
        </div>
        <!-- Entité liée -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.entite_liee') || 'Entité liée' }}</label>
                <SearchableSelect
                    v-model="form.entite_liee"
                    :options="entiteLieeOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.entite_liee" class="text-danger">
                    <strong>{{ form.errors.entite_liee }}</strong>
                </span>
            </div>
        </div>
        <!-- Ordre d'affichage -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.ordre_affichage') || 'Ordre d\'affichage' }}</label>
                <input type="number" v-model.number="form.ordre_affichage" class="form-control" min="0" :disabled="isReadOnly">
                <span v-if="form.errors?.ordre_affichage" class="text-danger">
                    <strong>{{ form.errors.ordre_affichage }}</strong>
                </span>
            </div>
        </div>
        <!-- Nécessite validation -->
        <div class="col-md-4">
            <div class="mb-3">
                <label class="form-check-label">
                    <input type="checkbox" v-model="form.necessite_validation" class="form-check-input" :disabled="isReadOnly">
                    {{ t('fields.necessite_validation') || 'Nécessite validation' }}
                </label>
                <span v-if="form.errors?.necessite_validation" class="text-danger">
                    <strong>{{ form.errors.necessite_validation }}</strong>
                </span>
            </div>
        </div>
        <!-- État -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.status') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
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
