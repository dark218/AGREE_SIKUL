<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    paysList: {
        type: Array,
        default: () => [],
    },
    directeurs: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Array,
        default: () => [],
    },
    departements: {
        type: Array,
        default: () => [],
    },
    communes: {
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
const statusOptions = [
    { id: 'actif', libelle: t('common.active') || 'Actif' },
    { id: 'inactif', libelle: t('common.inactive') || 'Inactif' },
];
const typeOptions = [
    { id: 'primaire', libelle: t('common.primaire') || 'Primaire' },
    { id: 'secondaire', libelle: t('common.secondaire') || 'Secondaire' },
    { id: 'superieur', libelle: t('common.superieur') || 'Supérieur' },
    { id: 'formation', libelle: t('common.formation') || 'Formation' },
    { id: 'autre', libelle: t('common.autre') || 'Autre' },
];
const deviseOptions = [
    { id: 'USD', libelle: 'USD - Dollar Américain' },
    { id: 'EUR', libelle: 'EUR - Euro' },
    { id: 'XOF', libelle: 'XOF - Franc CFA' },
    { id: 'GBP', libelle: 'GBP - Livre Sterling' },
];
const getDirecteurLabel = (opt) => opt ? `${opt.nom} (${opt.email})` : '';
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- SECTION 1: NOM DE L'INSTITUTION -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-building"></i> {{ t('common.institution') || 'Nom de l\'institution' }}
            </h6>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.nom') || 'Nom' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.nom" class="form-control" :placeholder="t('fields.nom') || 'Nom'" :disabled="isReadOnly" required>
                <span v-if="form.errors?.nom" class="text-danger small">{{ form.errors.nom }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.sigle') || 'Sigle' }}</label>
                <input type="text" v-model="form.sigle" class="form-control" :placeholder="t('fields.sigle') || 'Sigle'" :disabled="isReadOnly">
                <span v-if="form.errors?.sigle" class="text-danger small">{{ form.errors.sigle }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.devise_principale') || 'Devise' }}</label>
                <SearchableSelect
                    v-model="form.devise_principale"
                    :options="deviseOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.devise_principale" class="text-danger small">{{ form.errors.devise_principale }}</span>
            </div>
        </div>
        <!-- SECTION 2: ADRESSE ET LOCALISATION -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-map-marker"></i> {{ t('fields.address') || 'Adresse et localisation' }}
            </h6>
        </div>
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('fields.adresse_siege') || 'Adresse du siège' }}</label>
                <input type="text" v-model="form.adresse_siege" class="form-control" :placeholder="t('fields.adresse_siege') || 'Adresse'" :disabled="isReadOnly">
                <span v-if="form.errors?.adresse_siege" class="text-danger small">{{ form.errors.adresse_siege }}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.code_postal') || 'Code postal' }}</label>
                <input type="text" v-model="form.code_postal" class="form-control" :placeholder="t('fields.code_postal') || 'Code postal'" :disabled="isReadOnly">
                <span v-if="form.errors?.code_postal" class="text-danger small">{{ form.errors.code_postal }}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('common.boite_postale') || 'Boîte postale' }}</label>
                <input type="text" v-model="form.boite_postale" class="form-control" :placeholder="t('common.boite_postale') || 'BP'" :disabled="isReadOnly">
                <span v-if="form.errors?.boite_postale" class="text-danger small">{{ form.errors.boite_postale }}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.ville') || 'Ville' }}</label>
                <input type="text" v-model="form.ville" class="form-control" :placeholder="t('fields.ville') || 'Ville'" :disabled="isReadOnly">
                <span v-if="form.errors?.ville" class="text-danger small">{{ form.errors.ville }}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.quartier') || 'Quartier' }}</label>
                <input type="text" v-model="form.quartier" class="form-control" :placeholder="t('fields.quartier') || 'Quartier'" :disabled="isReadOnly">
                <span v-if="form.errors?.quartier" class="text-danger small">{{ form.errors.quartier }}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.region') || 'Région/Province' }}</label>
                <SearchableSelect
                    v-model="form.region_id"
                    :options="props.regions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.region_id" class="text-danger small">{{ form.errors.region_id }}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.departement') || 'Département' }}</label>
                <SearchableSelect
                    v-model="form.departement_id"
                    :options="props.departements"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.departement_id" class="text-danger small">{{ form.errors.departement_id }}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.commune') || 'Commune' }}</label>
                <SearchableSelect
                    v-model="form.commune_id"
                    :options="props.communes"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.commune_id" class="text-danger small">{{ form.errors.commune_id }}</span>
            </div>
        </div>
        <div class="col-sm-3">
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
                <span v-if="form.errors?.pays_id" class="text-danger small">{{ form.errors.pays_id }}</span>
            </div>
        </div>
        <!-- SECTION 3: CREATION ET AGRÉMENT -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-certificate"></i> {{ t('common.creation_et_agrément') || 'Création et agrément' }}
            </h6>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.date_creation') || 'Date de création' }}</label>
                <input type="date" v-model="form.date_creation" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_creation" class="text-danger small">{{ form.errors.date_creation }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.numero_autorisation') || 'Numéro d\'agrément' }}</label>
                <input type="text" v-model="form.numero_autorisation" class="form-control" :placeholder="t('fields.numero_autorisation') || 'Numéro agrément'" :disabled="isReadOnly">
                <span v-if="form.errors?.numero_autorisation" class="text-danger small">{{ form.errors.numero_autorisation }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code') || 'Code'" :disabled="isReadOnly" required>
                <span v-if="form.errors?.code" class="text-danger small">{{ form.errors.code }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.ministere_tutelle_1') || 'Ministère de tutelle 1' }}</label>
                <input type="text" v-model="form.ministere_tutelle_1" class="form-control" :placeholder="t('fields.ministere_tutelle_1')" :disabled="isReadOnly">
                <span v-if="form.errors?.ministere_tutelle_1" class="text-danger small">{{ form.errors.ministere_tutelle_1 }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.ministere_tutelle_2') || 'Ministère de tutelle 2' }}</label>
                <input type="text" v-model="form.ministere_tutelle_2" class="form-control" :placeholder="t('fields.ministere_tutelle_2')" :disabled="isReadOnly">
                <span v-if="form.errors?.ministere_tutelle_2" class="text-danger small">{{ form.errors.ministere_tutelle_2 }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.ministere_tutelle_3') || 'Ministère de tutelle 3' }}</label>
                <input type="text" v-model="form.ministere_tutelle_3" class="form-control" :placeholder="t('fields.ministere_tutelle_3')" :disabled="isReadOnly">
                <span v-if="form.errors?.ministere_tutelle_3" class="text-danger small">{{ form.errors.ministere_tutelle_3 }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.ministere_tutelle_4') || 'Ministère de tutelle 4' }}</label>
                <input type="text" v-model="form.ministere_tutelle_4" class="form-control" :placeholder="t('fields.ministere_tutelle_4')" :disabled="isReadOnly">
                <span v-if="form.errors?.ministere_tutelle_4" class="text-danger small">{{ form.errors.ministere_tutelle_4 }}</span>
            </div>
        </div>
        <!-- SECTION 4: CONTACTS -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-phone"></i> {{ t('common.contacts') || 'Contacts' }}
            </h6>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.telephone_principal') || 'Téléphone principal' }}</label>
                <input type="tel" v-model="form.telephone_principal" class="form-control" :placeholder="t('fields.telephone_principal')" :disabled="isReadOnly">
                <span v-if="form.errors?.telephone_principal" class="text-danger small">{{ form.errors.telephone_principal }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.telephone_2') || 'Téléphone 2' }}</label>
                <input type="tel" v-model="form.telephone_2" class="form-control" :placeholder="t('fields.telephone_2')" :disabled="isReadOnly">
                <span v-if="form.errors?.telephone_2" class="text-danger small">{{ form.errors.telephone_2 }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.telephone_3') || 'Téléphone 3' }}</label>
                <input type="tel" v-model="form.telephone_3" class="form-control" :placeholder="t('fields.telephone_3')" :disabled="isReadOnly">
                <span v-if="form.errors?.telephone_3" class="text-danger small">{{ form.errors.telephone_3 }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.whatsapp_1') || 'WhatsApp 1' }}</label>
                <input type="tel" v-model="form.whatsapp_1" class="form-control" :placeholder="t('fields.whatsapp_1')" :disabled="isReadOnly">
                <span v-if="form.errors?.whatsapp_1" class="text-danger small">{{ form.errors.whatsapp_1 }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.whatsapp_2') || 'WhatsApp 2' }}</label>
                <input type="tel" v-model="form.whatsapp_2" class="form-control" :placeholder="t('fields.whatsapp_2')" :disabled="isReadOnly">
                <span v-if="form.errors?.whatsapp_2" class="text-danger small">{{ form.errors.whatsapp_2 }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.fax') || 'Fax' }}</label>
                <input type="tel" v-model="form.fax" class="form-control" :placeholder="t('fields.fax')" :disabled="isReadOnly">
                <span v-if="form.errors?.fax" class="text-danger small">{{ form.errors.fax }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.email_principal') || 'Email principal' }}</label>
                <input type="email" v-model="form.email_principal" class="form-control" :placeholder="t('fields.email_principal')" :disabled="isReadOnly">
                <span v-if="form.errors?.email_principal" class="text-danger small">{{ form.errors.email_principal }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.email_1') || 'Email 2' }}</label>
                <input type="email" v-model="form.email_1" class="form-control" :placeholder="t('fields.email_1')" :disabled="isReadOnly">
                <span v-if="form.errors?.email_1" class="text-danger small">{{ form.errors.email_1 }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.site_web') || 'Site web' }}</label>
                <input type="url" v-model="form.site_web" class="form-control" :placeholder="t('fields.site_web')" :disabled="isReadOnly">
                <span v-if="form.errors?.site_web" class="text-danger small">{{ form.errors.site_web }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.facebook') || 'Facebook' }}</label>
                <input type="text" v-model="form.facebook" class="form-control" :placeholder="t('fields.facebook')" :disabled="isReadOnly">
                <span v-if="form.errors?.facebook" class="text-danger small">{{ form.errors.facebook }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.linkedin') || 'LinkedIn' }}</label>
                <input type="text" v-model="form.linkedin" class="form-control" :placeholder="t('fields.linkedin')" :disabled="isReadOnly">
                <span v-if="form.errors?.linkedin" class="text-danger small">{{ form.errors.linkedin }}</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.twitter') || 'Twitter' }}</label>
                <input type="text" v-model="form.twitter" class="form-control" :placeholder="t('fields.twitter')" :disabled="isReadOnly">
                <span v-if="form.errors?.twitter" class="text-danger small">{{ form.errors.twitter }}</span>
            </div>
        </div>
        <!-- SECTION 5: DIRIGEANTS -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-users"></i> {{ t('common.dirigeants') || 'Dirigeants' }}
            </h6>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.directeur_general') || 'Directeur Général' }}</label>
                <SearchableSelect
                    v-model="form.directeur_general_id"
                    :options="directeurs"
                    optionValue="id"
                    :optionLabel="getDirecteurLabel"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.directeur_general_id" class="text-danger small">{{ form.errors.directeur_general_id }}</span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type') || 'Type d\'établissement' }}</label>
                <SearchableSelect
                    v-model="form.type"
                    :options="typeOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type" class="text-danger small">{{ form.errors.type }}</span>
            </div>
        </div>
        <!-- SECTION 6: DESCRIPTION -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-file-text"></i> {{ t('fields.description') || 'Description' }}
            </h6>
        </div>
        <div class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.description') || 'Description' }}</label>
                <textarea v-model="form.description" class="form-control" rows="3" :placeholder="t('fields.description')" :disabled="isReadOnly"></textarea>
                <span v-if="form.errors?.description" class="text-danger small">{{ form.errors.description }}</span>
            </div>
        </div>
        <div class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.vision') || 'Vision' }}</label>
                <textarea v-model="form.vision" class="form-control" rows="2" :placeholder="t('fields.vision')" :disabled="isReadOnly"></textarea>
                <span v-if="form.errors?.vision" class="text-danger small">{{ form.errors.vision }}</span>
            </div>
        </div>
        <div class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.mission') || 'Mission' }}</label>
                <textarea v-model="form.mission" class="form-control" rows="2" :placeholder="t('fields.mission')" :disabled="isReadOnly"></textarea>
                <span v-if="form.errors?.mission" class="text-danger small">{{ form.errors.mission }}</span>
            </div>
        </div>
        <!-- SECTION 7: STATUT -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-check-circle"></i> {{ t('fields.status') || 'Statut' }}
            </h6>
        </div>
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
                <span v-if="form.errors?.statut" class="text-danger small">{{ form.errors.statut }}</span>
            </div>
        </div>
    </div>
</template>
<style scoped>
.custom-input .section-header {
    margin-top: 20px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #0056b3;
    color: #0056b3;
    font-weight: 600;
    font-size: 1rem;
}
.custom-input .col-12 h6 {
    margin: 0;
}
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
.custom-input label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}
.custom-input .text-danger {
    font-size: 0.85rem;
    margin-top: 0.25rem;
}
.custom-input .mb-3 {
    margin-bottom: 1.5rem;
}
</style>
