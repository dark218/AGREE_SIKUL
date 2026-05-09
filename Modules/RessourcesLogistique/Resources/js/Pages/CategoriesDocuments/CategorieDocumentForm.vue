<script setup>
import { ref } from 'vue';
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
    },
});

const iconOptions = [
    { id: 'fa-file', label: 'fa-file - Fichier' },
    { id: 'fa-pdf', label: 'fa-pdf - PDF' },
    { id: 'fa-image', label: 'fa-image - Image' },
    { id: 'fa-video', label: 'fa-video - Vidéo' },
    { id: 'fa-music', label: 'fa-music - Musique' },
    { id: 'fa-archive', label: 'fa-archive - Archive' },
    { id: 'fa-folder', label: 'fa-folder - Dossier' },
    { id: 'fa-book', label: 'fa-book - Livre' },
    { id: 'fa-newspaper', label: 'fa-newspaper - Journal' },
    { id: 'fa-document', label: 'fa-document - Document' },
    { id: 'fa-spreadsheet', label: 'fa-spreadsheet - Feuille de calcul' },
    { id: 'fa-presentation', label: 'fa-presentation - Présentation' },
    { id: 'fa-download', label: 'fa-download - Téléchargement' },
    { id: 'fa-upload', label: 'fa-upload - Envoi' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Information Section -->
        <div class="col-sm-12">
            <h6 class="section-title mb-3">{{ t('common.information') || 'Informations' }}</h6>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
            <input
                v-model="form.code"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': form.errors?.code }"
                :disabled="mode === 'show'"
            />
            <div v-if="form.errors?.code" class="invalid-feedback d-block">
                {{ form.errors.code[0] || form.errors.code }}
            </div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.label') || 'Libellé' }} <span class="text-danger">*</span></label>
            <input
                v-model="form.libelle"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': form.errors?.libelle }"
                :disabled="mode === 'show'"
            />
            <div v-if="form.errors?.libelle" class="invalid-feedback d-block">
                {{ form.errors.libelle[0] || form.errors.libelle }}
            </div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.description') || 'Description' }}</label>
            <textarea
                v-model="form.description"
                class="form-control"
                :class="{ 'is-invalid': form.errors?.description }"
                :disabled="mode === 'show'"
                rows="3"
            ></textarea>
            <div v-if="form.errors?.description" class="invalid-feedback d-block">
                {{ form.errors.description[0] || form.errors.description }}
            </div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.icone') || 'Icône' }}</label>
            <SearchableSelect
                v-model="form.icone"
                :options="iconOptions"
                optionValue="id"
                optionLabel="label"
                :placeholder="t('fields.icone') || 'Sélectionner une icône'"
                :disabled="mode === 'show'"
            />
            <div v-if="form.errors?.icone" class="invalid-feedback d-block">
                {{ form.errors.icone[0] || form.errors.icone }}
            </div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.couleur') || 'Couleur' }}</label>
            <input
                :value="form.couleur || '#000000'"
                @input="form.couleur = $event.target.value || ''"
                type="color"
                class="form-control form-control-color"
                :class="{ 'is-invalid': form.errors?.couleur }"
                :disabled="mode === 'show'"
            />
            <div v-if="form.errors?.couleur" class="invalid-feedback d-block">
                {{ form.errors.couleur[0] || form.errors.couleur }}
            </div>
        </div>

        <!-- Status Section -->
        <div class="col-sm-12 mt-3">
            <h6 class="section-title mb-3">{{ t('common.status') || 'Statut' }}</h6>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.status') || 'Statut' }}</label>
            <select
                v-model="form.statut"
                class="form-control"
                :class="{ 'is-invalid': form.errors?.statut }"
                :disabled="mode === 'show'"
            >
                <option value="actif">{{ t('common.actif') || 'Actif' }}</option>
                <option value="inactif">{{ t('common.inactif') || 'Inactif' }}</option>
            </select>
            <div v-if="form.errors?.statut" class="invalid-feedback d-block">
                {{ form.errors.statut[0] || form.errors.statut }}
            </div>
        </div>
    </div>
</template>

<style scoped>
.section-title {
    font-weight: 600;
    color: #333;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}
.custom-input {
    padding: 20px 0;
}
</style>
