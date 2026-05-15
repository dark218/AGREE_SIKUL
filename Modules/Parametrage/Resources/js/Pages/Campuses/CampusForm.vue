<!--
  CampusForm.vue — refonte selon spec Orchidée
  Ordre des champs (2 colonnes) :
    L1: Institution | Code
    L2: Nom | Adresse
    L3: Code postal | Boîte postale
    L4: Quartier | Commune
    L5: Ville | Département
    L6: Région | Pays
    L7: Longitude | Latitude
    L8: Téléphone | Email
    L9: Responsable | Statut de disponibilité
-->
<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import LocalisationBlock from '@/Components/Common/LocalisationBlock.vue';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
    institutions: { type: Array, default: () => [] },
    responsables: { type: Array, default: () => [] },
    paysList: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    departements: { type: Array, default: () => [] },
    communes: { type: Array, default: () => [] },
    quartiers: { type: Array, default: () => [] },
});

const isReadOnly = computed(() => props.mode === 'show');

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'non_actif', libelle: 'Inactif' },
    { id: 'suspendu', libelle: 'Suspendu' },
];

const disponibiliteOptions = [
    { id: 'disponible', libelle: 'Disponible' },
    { id: 'indisponible', libelle: 'Indisponible' },
    { id: 'maintenance', libelle: 'En maintenance' },
];

const getResponsableLabel = (opt) => opt ? `${opt.nom} (${opt.email})` : '';
</script>

<template>
    <div class="row g-3 custom-input">

        <!-- LIGNE 1 : Institution | Code -->
        <div class="col-sm-6">
            <label class="form-label fw-medium">Institution <span v-if="!isReadOnly" class="text-danger">*</span></label>
            <SearchableSelect
                v-model="form.institution_id"
                :options="institutions"
                optionValue="id"
                optionLabel="nom"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.institution_id" class="text-danger small">{{ form.errors.institution_id }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Code <span v-if="!isReadOnly" class="text-danger">*</span></label>
            <input type="text" v-model="form.code" class="form-control" placeholder="Code" :disabled="isReadOnly" />
            <span v-if="form.errors?.code" class="text-danger small">{{ form.errors.code }}</span>
        </div>

        <!-- LIGNE 2 : Nom | Adresse -->
        <div class="col-sm-6">
            <label class="form-label fw-medium">Nom <span v-if="!isReadOnly" class="text-danger">*</span></label>
            <input type="text" v-model="form.nom" class="form-control" placeholder="Nom du campus" :disabled="isReadOnly" />
            <span v-if="form.errors?.nom" class="text-danger small">{{ form.errors.nom }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Adresse</label>
            <input type="text" v-model="form.adresse" class="form-control" placeholder="Adresse" :disabled="isReadOnly" />
            <span v-if="form.errors?.adresse" class="text-danger small">{{ form.errors.adresse }}</span>
        </div>

        <!-- LIGNES 3-6 : Bloc localisation (Code postal/BP/Quartier/Commune + Ville/Département/Région/Pays) -->
        <div class="col-12">
            <LocalisationBlock
                :form="form"
                :paysList="paysList"
                :regions="regions"
                :departements="departements"
                :communes="communes"
                :quartiers="quartiers"
                :disabled="isReadOnly"
            />
        </div>

        <!-- LIGNE 7 : Longitude | Latitude -->
        <div class="col-sm-6">
            <label class="form-label fw-medium">Longitude</label>
            <input type="number" v-model.number="form.longitude" class="form-control" placeholder="Ex: -4.024429" :disabled="isReadOnly" step="0.000001" />
            <span v-if="form.errors?.longitude" class="text-danger small">{{ form.errors.longitude }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Latitude</label>
            <input type="number" v-model.number="form.latitude" class="form-control" placeholder="Ex: 5.345317" :disabled="isReadOnly" step="0.000001" />
            <span v-if="form.errors?.latitude" class="text-danger small">{{ form.errors.latitude }}</span>
        </div>

        <!-- LIGNE 8 : Téléphone | Email -->
        <div class="col-sm-6">
            <label class="form-label fw-medium">Téléphone</label>
            <input type="tel" v-model="form.telephone" class="form-control" :disabled="isReadOnly" />
            <span v-if="form.errors?.telephone" class="text-danger small">{{ form.errors.telephone }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Email</label>
            <input type="email" v-model="form.email" class="form-control" :disabled="isReadOnly" />
            <span v-if="form.errors?.email" class="text-danger small">{{ form.errors.email }}</span>
        </div>

        <!-- LIGNE 9 : Responsable | Statut de disponibilité -->
        <div class="col-sm-6">
            <label class="form-label fw-medium">Responsable</label>
            <SearchableSelect
                v-model="form.responsable_id"
                :options="responsables"
                optionValue="id"
                :optionLabel="getResponsableLabel"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.responsable_id" class="text-danger small">{{ form.errors.responsable_id }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Statut de disponibilité</label>
            <SearchableSelect
                v-model="form.statut_disponibilite"
                :options="disponibiliteOptions"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.statut_disponibilite" class="text-danger small">{{ form.errors.statut_disponibilite }}</span>
        </div>

        <!-- Statut administratif (caché en bas, hors spec mais nécessaire pour le système) -->
        <div class="col-sm-6">
            <label class="form-label fw-medium">Statut</label>
            <SearchableSelect
                v-model="form.statut"
                :options="statusOptions"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.statut" class="text-danger small">{{ form.errors.statut }}</span>
        </div>
    </div>
</template>

<style scoped>
.custom-input .form-control,
.custom-input select {
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
}
.custom-input .form-control:focus,
.custom-input select:focus {
    border-color: #0056b3;
    box-shadow: 0 0 0 0.2rem rgba(0, 86, 179, 0.25);
}
.custom-input .form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}
</style>
