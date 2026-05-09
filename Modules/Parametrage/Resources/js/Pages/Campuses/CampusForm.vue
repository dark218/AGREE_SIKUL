<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import { usePage } from '@inertiajs/vue3';
const { t } = useI18n();
const page = usePage();
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
    institutions: {
        type: Array,
        default: () => [],
    },
    responsables: {
        type: Array,
        default: () => [],
    },
    paysList: {
        type: Array,
        default: () => [],
    },
    communes: {
        type: Array,
        default: () => [],
    },
    departements: {
        type: Array,
        default: () => [],
    },
    quartiers: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Array,
        default: () => [],
    },
});
const isReadOnly = computed(() => props.mode === 'show');
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const getResponsableLabel = (opt) => opt ? `${opt.nom} (${opt.email})` : '';
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Institution -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.institution') || 'Institution' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.institution_id"
                    :options="institutions"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.institution_id" class="text-danger">
                    <strong>{{ form.errors.institution_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Nom -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom') || 'Nom' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.nom" class="form-control" :placeholder="t('fields.nom') || 'Nom'" :disabled="isReadOnly">
                <span v-if="form.errors?.nom" class="text-danger">
                    <strong>{{ form.errors.nom }}</strong>
                </span>
            </div>
        </div>
        <!-- Ville -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ville') || 'Ville' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" v-model="form.ville" class="form-control" :placeholder="t('fields.ville') || 'Ville'" :disabled="isReadOnly">
                <span v-if="form.errors?.ville" class="text-danger">
                    <strong>{{ form.errors.ville }}</strong>
                </span>
            </div>
        </div>
        <!-- Adresse -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.address') || 'Adresse' }}</label>
                <input type="text" v-model="form.adresse" class="form-control" :placeholder="t('fields.address') || 'Adresse'" :disabled="isReadOnly">
                <span v-if="form.errors?.adresse" class="text-danger">
                    <strong>{{ form.errors.adresse }}</strong>
                </span>
            </div>
        </div>
        <!-- Code Postal -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code_postal') || 'Code Postal' }}</label>
                <input type="text" v-model="form.code_postal" class="form-control" :placeholder="t('fields.code_postal') || 'Code Postal'" :disabled="isReadOnly">
                <span v-if="form.errors?.code_postal" class="text-danger">
                    <strong>{{ form.errors.code_postal }}</strong>
                </span>
            </div>
        </div>
        <!-- Boîte Postale -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.boite_postale') || 'Boîte postale' }}</label>
                <input type="text" v-model="form.boite_postale" class="form-control" :placeholder="t('common.boite_postale') || 'Boîte postale'" :disabled="isReadOnly">
                <span v-if="form.errors?.boite_postale" class="text-danger">
                    <strong>{{ form.errors.boite_postale }}</strong>
                </span>
            </div>
        </div>
        <!-- Quartier -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.quartier') || 'Quartier' }}</label>
                <SearchableSelect
                    v-model="form.quartier"
                    :options="quartiers"
                    optionValue="libelle"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.quartier" class="text-danger">
                    <strong>{{ form.errors.quartier }}</strong>
                </span>
            </div>
        </div>
        <!-- Commune -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.commune') || 'Commune' }}</label>
                <SearchableSelect
                    v-model="form.commune"
                    :options="communes"
                    optionValue="libelle"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.commune" class="text-danger">
                    <strong>{{ form.errors.commune }}</strong>
                </span>
            </div>
        </div>
        <!-- Département -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.departement') || 'Département' }}</label>
                <SearchableSelect
                    v-model="form.departement"
                    :options="departements"
                    optionValue="libelle"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.departement" class="text-danger">
                    <strong>{{ form.errors.departement }}</strong>
                </span>
            </div>
        </div>
        <!-- Région -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.region') || 'Région' }}</label>
                <SearchableSelect
                    v-model="form.region"
                    :options="regions"
                    optionValue="libelle"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.region" class="text-danger">
                    <strong>{{ form.errors.region }}</strong>
                </span>
            </div>
        </div>
        <!-- Pays -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.pays') || 'Pays' }}</label>
                <SearchableSelect
                    v-model="form.pays_id"
                    :options="paysList"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.pays_id" class="text-danger">
                    <strong>{{ form.errors.pays_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Longitude -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.longitude') || 'Longitude' }}</label>
                <input type="number" v-model.number="form.longitude" class="form-control" :placeholder="t('fields.longitude') || 'Longitude'" :disabled="isReadOnly" step="0.000001">
                <span v-if="form.errors?.longitude" class="text-danger">
                    <strong>{{ form.errors.longitude }}</strong>
                </span>
            </div>
        </div>
        <!-- Latitude -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.latitude') || 'Latitude' }}</label>
                <input type="number" v-model.number="form.latitude" class="form-control" :placeholder="t('fields.latitude') || 'Latitude'" :disabled="isReadOnly" step="0.000001">
                <span v-if="form.errors?.latitude" class="text-danger">
                    <strong>{{ form.errors.latitude }}</strong>
                </span>
            </div>
        </div>
        <!-- Téléphone -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.telephone') || 'Téléphone' }}</label>
                <input type="text" v-model="form.telephone" class="form-control" :placeholder="t('fields.telephone') || 'Téléphone'" :disabled="isReadOnly">
                <span v-if="form.errors?.telephone" class="text-danger">
                    <strong>{{ form.errors.telephone }}</strong>
                </span>
            </div>
        </div>
        <!-- Email -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.email') || 'Email' }}</label>
                <input type="email" v-model="form.email" class="form-control" :placeholder="t('fields.email') || 'Email'" :disabled="isReadOnly">
                <span v-if="form.errors?.email" class="text-danger">
                    <strong>{{ form.errors.email }}</strong>
                </span>
            </div>
        </div>
        <!-- Responsable -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.responsable') || 'Responsable' }}</label>
                <SearchableSelect
                    v-model="form.responsable_id"
                    :options="responsables"
                    optionValue="id"
                    :optionLabel="getResponsableLabel"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.responsable_id" class="text-danger">
                    <strong>{{ form.errors.responsable_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Statut -->
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
