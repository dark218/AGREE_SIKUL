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
    bibliotheques: {
        type: Array,
        default: () => [],
    },
});

const isReadOnly = props.mode === 'show';

const statusOptions = [
    { id: 'actif', libelle: t('common.actif') || 'Actif' },
    { id: 'inactif', libelle: t('common.inactif') || 'Inactif' },
    { id: 'archive', libelle: t('common.archive') || 'Archivé' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Bibliothèque -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.bibliotheque') || 'Bibliothèque' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.bibliotheque_id"
                    :options="bibliotheques"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.bibliotheque_id" class="text-danger">
                    <strong>{{ form.errors.bibliotheque_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Titre -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.titre') || 'Titre' }} <span class="text-danger">*</span></label>
                <input
                    type="text"
                    v-model="form.titre"
                    class="form-control"
                    :placeholder="t('fields.titre') || 'Titre'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.titre" class="text-danger">
                    <strong>{{ form.errors.titre }}</strong>
                </span>
            </div>
        </div>

        <!-- Auteur -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.auteur') || 'Auteur' }} <span class="text-danger">*</span></label>
                <input
                    type="text"
                    v-model="form.auteur"
                    class="form-control"
                    :placeholder="t('fields.auteur') || 'Auteur'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.auteur" class="text-danger">
                    <strong>{{ form.errors.auteur }}</strong>
                </span>
            </div>
        </div>

        <!-- ISBN -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.isbn') || 'ISBN' }}</label>
                <input
                    type="text"
                    v-model="form.isbn"
                    class="form-control"
                    :placeholder="t('fields.isbn') || 'ISBN'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.isbn" class="text-danger">
                    <strong>{{ form.errors.isbn }}</strong>
                </span>
            </div>
        </div>

        <!-- Éditeur -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.editeur') || 'Éditeur' }}</label>
                <input
                    type="text"
                    v-model="form.editeur"
                    class="form-control"
                    :placeholder="t('fields.editeur') || 'Éditeur'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.editeur" class="text-danger">
                    <strong>{{ form.errors.editeur }}</strong>
                </span>
            </div>
        </div>

        <!-- Catégorie -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.categorie') || 'Catégorie' }}</label>
                <input
                    type="text"
                    v-model="form.categorie"
                    class="form-control"
                    :placeholder="t('fields.categorie') || 'Catégorie'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.categorie" class="text-danger">
                    <strong>{{ form.errors.categorie }}</strong>
                </span>
            </div>
        </div>

        <!-- Date de publication -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_publication') || 'Date de publication' }}</label>
                <input
                    type="date"
                    v-model="form.date_publication"
                    class="form-control"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_publication" class="text-danger">
                    <strong>{{ form.errors.date_publication }}</strong>
                </span>
            </div>
        </div>

        <!-- Description -->
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('fields.description') || 'Description' }}</label>
                <textarea
                    v-model="form.description"
                    class="form-control"
                    :placeholder="t('fields.description') || 'Description'"
                    :disabled="isReadOnly"
                    rows="3"
                ></textarea>
                <span v-if="form.errors?.description" class="text-danger">
                    <strong>{{ form.errors.description }}</strong>
                </span>
            </div>
        </div>

        <!-- Nombre d'exemplaires -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nombre_exemplaires') || 'Nombre d\'exemplaires' }} <span class="text-danger">*</span></label>
                <input
                    type="number"
                    v-model.number="form.nombre_exemplaires"
                    class="form-control"
                    :disabled="isReadOnly"
                    min="1"
                />
                <span v-if="form.errors?.nombre_exemplaires" class="text-danger">
                    <strong>{{ form.errors.nombre_exemplaires }}</strong>
                </span>
            </div>
        </div>

        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }}</label>
                <SearchableSelect
                    v-model="form.statut"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

