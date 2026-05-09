<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    typeEtablissements: {
        type: Array,
        default: () => [],
    },
    directeurs: {
        type: Array,
        default: () => [],
    },
    paysList: {
    typeCours: {
        type: Array,
        default: () => [],
    },
    institutions: {
        type: Array,
        default: () => [],
    },
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
    { id: 'non_actif', libelle: t('common.inactive') || 'Inactif' },
    { id: 'suspendu', libelle: t('common.suspended') || 'Suspendu' },
];
const getDirecteurLabel = (opt) => opt ? `${opt.nom}` : '';
// Dirigeants management
const emptyDirigeant = {
    nom: '',
    prenom: '',
    nom_restituer: '',
    fonction: '',
    ordre: 0,
};
const addDirigeant = () => {
    if (!Array.isArray(props.form.dirigeants)) {
        props.form.dirigeants = [];
    }
    const nextOrder = props.form.dirigeants.length > 0
        ? Math.max(...props.form.dirigeants.map(d => d.ordre || 0)) + 1
        : 1;
    props.form.dirigeants.push({ ...emptyDirigeant, ordre: nextOrder });
};
import { ref } from 'vue';

const logoPreview = ref(null);
const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        props.form.logo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeDirigeant = (index) => {
    props.form.dirigeants.splice(index, 1);
};
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- SECTION 1: INFORMATIONS BASIQUES -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-school"></i> {{ t('common.information_base') || 'Informations de base' }}
            </h6>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') || 'Campus' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.campus_id"
                    :options="campuses"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.campus_id" class="text-danger small">{{ form.errors.campus_id }}</span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.code"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.code') || 'Code'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.code" class="text-danger small">{{ form.errors.code }}</span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom') || 'Nom' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.nom"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.nom') || 'Nom'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom" class="text-danger small">{{ form.errors.nom }}</span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type_enseignement') || 'Type d\'enseignement' }}</label>
                <SearchableSelect
                    v-model="form.type_enseignement"
                    :options="typeEtablissements"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_enseignement" class="text-danger small">{{ form.errors.type_enseignement }}</span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.directeur') || 'Directeur' }}</label>
                <SearchableSelect
                    v-model="form.directeur_id"
                    :options="directeurs"
                    optionValue="id"
                    :optionLabel="getDirecteurLabel"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.directeur_id" class="text-danger small">{{ form.errors.directeur_id }}</span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.capacite_totale') || 'Capacité totale' }}</label>
                <input
                    v-model.number="form.capacite_totale"
                    type="number"
                    class="form-control"
                    :placeholder="t('fields.capacite_totale') || 'Capacité totale'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.capacite_totale" class="text-danger small">{{ form.errors.capacite_totale }}</span>
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
                <label>{{ t('fields.adresse_siege') || 'Adresse' }}</label>
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
                <label>{{ t('fields.commune') || 'Commune' }}</label>
                <input type="text" v-model="form.commune" class="form-control" :placeholder="t('fields.commune') || 'Commune'" :disabled="isReadOnly">
                <span v-if="form.errors?.commune" class="text-danger small">{{ form.errors.commune }}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.departement') || 'Département' }}</label>
                <input type="text" v-model="form.departement" class="form-control" :placeholder="t('fields.departement') || 'Département'" :disabled="isReadOnly">
                <span v-if="form.errors?.departement" class="text-danger small">{{ form.errors.departement }}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.region') || 'Région/Province' }}</label>
                <input type="text" v-model="form.region" class="form-control" :placeholder="t('fields.region') || 'Région'" :disabled="isReadOnly">
                <span v-if="form.errors?.region" class="text-danger small">{{ form.errors.region }}</span>
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
        <!-- SECTION 3: CONTACTS -->
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
        <!-- SECTION 4: DESCRIPTION -->
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
        <!-- SECTION 5: DIRIGEANTS -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-users"></i> {{ t('common.dirigeants') || 'Dirigeants' }}
                <button
                    v-if="!isReadOnly"
                    type="button"
                    class="btn btn-sm btn-primary float-end"
                    @click="addDirigeant"
                    title="Ajouter un dirigeant"
                >
                    <i class="fa fa-plus"></i>
                </button>
            </h6>
        </div>
        <div v-if="Array.isArray(form.dirigeants) && form.dirigeants.length > 0" class="col-12">
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 20%">{{ t('fields.nom') || 'Nom' }}</th>
                            <th style="width: 20%">{{ t('fields.prenom') || 'Prénom' }}</th>
                            <th style="width: 30%">{{ t('fields.nom_restituer') || 'Nom à restituer' }}</th>
                            <th style="width: 25%">{{ t('fields.fonction') || 'Fonction' }}</th>
                            <th v-if="!isReadOnly" style="width: 5%" class="text-center">{{ t('actions.delete') || 'Supprimer' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(dirigeant, index) in form.dirigeants" :key="index">
                            <td>
                                <input
                                    v-model="dirigeant.nom"
                                    type="text"
                                    class="form-control form-control-sm"
                                    :placeholder="t('fields.nom') || 'Nom'"
                                    :disabled="isReadOnly"
                                />
                            </td>
                            <td>
                                <input
                                    v-model="dirigeant.prenom"
                                    type="text"
                                    class="form-control form-control-sm"
                                    :placeholder="t('fields.prenom') || 'Prénom'"
                                    :disabled="isReadOnly"
                                />
                            </td>
                            <td>
                                <input
                                    v-model="dirigeant.nom_restituer"
                                    type="text"
                                    class="form-control form-control-sm"
                                    :placeholder="t('fields.nom_restituer') || 'Nom à restituer'"
                                    :disabled="isReadOnly"
                                />
                            </td>
                            <td>
                                <input
                                    v-model="dirigeant.fonction"
                                    type="text"
                                    class="form-control form-control-sm"
                                    :placeholder="t('fields.fonction') || 'Fonction'"
                                    :disabled="isReadOnly"
                                />
                            </td>
                            <td v-if="!isReadOnly" class="text-center">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger"
                                    @click="removeDirigeant(index)"
                                    title="Supprimer cette ligne"
                                >
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div v-else-if="!isReadOnly" class="col-12">
            <div class="alert alert-info small mb-3">
                {{ t('messages.no_dirigeants') || 'Aucun dirigeant ajouté. Cliquez sur le + pour en ajouter.' }}
            </div>
        </div>
        <!-- SECTION 6: STATUT -->
        <!-- SECTION 3: INFORMATIONS COMPLÉMENTAIRES -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-info-circle"></i> Informations complémentaires
            </h6>
        </div>
        <!-- Sigle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Sigle</label>
                <input type="text" v-model="form.sigle" class="form-control" placeholder="Ex: ENSPG" :disabled="isReadOnly">
                <span v-if="form.errors?.sigle" class="text-danger small">{{ form.errors.sigle }}</span>
            </div>
        </div>
        <!-- Devise -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Devise</label>
                <input type="text" v-model="form.devise" class="form-control" placeholder="Excellence et rigueur" :disabled="isReadOnly">
                <span v-if="form.errors?.devise" class="text-danger small">{{ form.errors.devise }}</span>
            </div>
        </div>
        <!-- Type d'établissement -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Type d'établissement</label>
                <SearchableSelect
                    v-model="form.type_etablissement_id"
                    :options="typeEtablissements"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_etablissement_id" class="text-danger small">{{ form.errors.type_etablissement_id }}</span>
            </div>
        </div>
        <!-- Type de cours -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Type de cours</label>
                <SearchableSelect
                    v-model="form.type_cours_id"
                    :options="typeCours"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_cours_id" class="text-danger small">{{ form.errors.type_cours_id }}</span>
            </div>
        </div>
        <!-- Institution -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Institution</label>
                <SearchableSelect
                    v-model="form.institution_id"
                    :options="institutions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.institution_id" class="text-danger small">{{ form.errors.institution_id }}</span>
            </div>
        </div>
        <!-- Localisation -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Localisation</label>
                <input type="text" v-model="form.localisation" class="form-control" placeholder="Quartier X, Ville Y" :disabled="isReadOnly">
                <span v-if="form.errors?.localisation" class="text-danger small">{{ form.errors.localisation }}</span>
            </div>
        </div>
        <!-- Date de création -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Date de création</label>
                <input type="date" v-model="form.date_creation" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_creation" class="text-danger small">{{ form.errors.date_creation }}</span>
            </div>
        </div>
        <!-- Numéro d'agrément -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Numéro d'agrément</label>
                <input type="text" v-model="form.numero_agrement" class="form-control" placeholder="AGR/2020/001" :disabled="isReadOnly">
                <span v-if="form.errors?.numero_agrement" class="text-danger small">{{ form.errors.numero_agrement }}</span>
            </div>
        </div>
        <!-- Ministère de tutelle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Ministère de tutelle</label>
                <input type="text" v-model="form.ministere_tutelle" class="form-control" placeholder="Ministère de l'Éducation" :disabled="isReadOnly">
                <span v-if="form.errors?.ministere_tutelle" class="text-danger small">{{ form.errors.ministere_tutelle }}</span>
            </div>
        </div>
        <!-- Logo -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Logo</label>
                <input type="file" @change="handleLogoChange" class="form-control" accept="image/*" :disabled="isReadOnly">
                <small class="text-muted">Formats acceptés: JPG, PNG, GIF</small>
                <span v-if="form.errors?.logo" class="text-danger small">{{ form.errors.logo }}</span>
            </div>
        </div>
        <!-- Logo Preview -->
        <div class="col-sm-6" v-if="form.logo || logoPreview">
            <div class="mb-3">
                <label>Aperçu du logo</label>
                <div class="logo-preview" style="border: 1px solid #ccc; padding: 10px; border-radius: 4px;">
                    <img 
                        :src="logoPreview || (form.logo && typeof form.logo === 'string' ? form.logo : '')" 
                        alt="Logo preview" 
                        class="img-fluid"
                        style="max-width: 150px; max-height: 150px; object-fit: contain; display: block;"
                    >
                </div>
            </div>
        </div>

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
.custom-input .table {
    margin-bottom: 0;
}
.custom-input .table-responsive {
    margin-top: 15px;
    margin-bottom: 20px;
}
.custom-input .form-control-sm {
    border: 1px solid #dee2e6;
    padding: 0.375rem 0.5rem;
}
.custom-input .form-control-sm:focus {
    border-color: #0056b3;
    box-shadow: 0 0 0 0.2rem rgba(0, 86, 179, 0.25);
}
.custom-input .btn-sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.85rem;
}
.custom-input .section-header .btn {
    margin-top: -2px;
}
</style>
