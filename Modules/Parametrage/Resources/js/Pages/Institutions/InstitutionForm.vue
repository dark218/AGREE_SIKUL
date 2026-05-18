<!--
  InstitutionForm.vue — refonte selon spec Orchidée
  Blocs : Informations générales / Adresse / Création et agrément / Dirigeants / Contacts / Description / Statut
-->
<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import LocalisationBlock from '@/Components/Common/LocalisationBlock.vue';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    paysList: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    departements: { type: Array, default: () => [] },
    communes: { type: Array, default: () => [] },
    quartiers: { type: Array, default: () => [] },
    devises: { type: Array, default: () => [] },
    directeurs: { type: Array, default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const isReadOnly = props.mode === 'show';

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'non_actif', libelle: 'Inactif' },
];

const deviseLabel = (d) => d ? `${d.code ?? ''} - ${d.libelle}${d.symbol ? ' (' + d.symbol + ')' : ''}`.trim() : '';
</script>

<template>
    <div class="row g-3 custom-input">

        <!-- ============================================== -->
        <!-- BLOC 1 : INFORMATIONS GÉNÉRALES -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-info-circle"></i> Informations générales
            </h6>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Nom <span class="text-danger">*</span></label>
            <input type="text" v-model="form.nom" class="form-control" placeholder="Nom de l'institution" :disabled="isReadOnly" required />
            <span v-if="form.errors?.nom" class="text-danger small">{{ form.errors.nom }}</span>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Sigle</label>
            <input type="text" v-model="form.sigle" class="form-control" placeholder="Sigle" :disabled="isReadOnly" />
            <span v-if="form.errors?.sigle" class="text-danger small">{{ form.errors.sigle }}</span>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">
                Devise
                <small class="text-muted">(slogan)</small>
            </label>
            <input
                type="text"
                v-model="form.devise_slogan"
                class="form-control"
                placeholder="Ex: Discipline - Travail - Succès"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.devise_slogan" class="text-danger small">{{ form.errors.devise_slogan }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Devise de tenue de la comptabilité</label>
            <SearchableSelect
                v-model="form.devise_comptabilite_id"
                :options="devises"
                optionValue="id"
                :optionLabel="deviseLabel"
                placeholder="-- Sélectionner une devise monétaire --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.devise_comptabilite_id" class="text-danger small">{{ form.errors.devise_comptabilite_id }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Logo</label>
            <input
                type="file"
                @change="(e) => form.logo = e.target.files[0]"
                class="form-control"
                accept="image/*"
                :disabled="isReadOnly"
            />
            <small class="text-muted">JPG, PNG, GIF (max 2 Mo)</small>
            <span v-if="form.errors?.logo" class="text-danger small">{{ form.errors.logo }}</span>
        </div>

        <!-- ============================================== -->
        <!-- BLOC 2 : ADRESSE -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-map-marker"></i> Adresse
            </h6>
        </div>
        <div class="col-12">
            <LocalisationBlock
                :form="form"
                :paysList="paysList"
                :regions="regions"
                :departements="departements"
                :communes="communes"
                :quartiers="quartiers"
                :disabled="isReadOnly"
                :showAddressLine="true"
                addressField="adresse_siege"
                addressLabel="Adresse du siège"
            />
        </div>

        <!-- ============================================== -->
        <!-- BLOC 3 : CRÉATION ET AGRÉMENT -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-certificate"></i> Création et agrément
            </h6>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Date de création</label>
            <input type="date" v-model="form.date_creation" class="form-control" :disabled="isReadOnly" />
            <span v-if="form.errors?.date_creation" class="text-danger small">{{ form.errors.date_creation }}</span>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Numéro d'agrément 1</label>
            <input type="text" v-model="form.numero_autorisation" class="form-control" placeholder="Ex: RC/MINENSBA/DRL/123" :disabled="isReadOnly" />
            <span v-if="form.errors?.numero_autorisation" class="text-danger small">{{ form.errors.numero_autorisation }}</span>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Numéro d'agrément 2</label>
            <input type="text" v-model="form.numero_agrement_2" class="form-control" :disabled="isReadOnly" />
            <span v-if="form.errors?.numero_agrement_2" class="text-danger small">{{ form.errors.numero_agrement_2 }}</span>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Numéro d'agrément 3</label>
            <input type="text" v-model="form.numero_agrement_3" class="form-control" :disabled="isReadOnly" />
            <span v-if="form.errors?.numero_agrement_3" class="text-danger small">{{ form.errors.numero_agrement_3 }}</span>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Numéro d'agrément 4</label>
            <input type="text" v-model="form.numero_agrement_4" class="form-control" :disabled="isReadOnly" />
            <span v-if="form.errors?.numero_agrement_4" class="text-danger small">{{ form.errors.numero_agrement_4 }}</span>
        </div>
        <div class="col-sm-4"></div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Ministère de tutelle 1</label>
            <input type="text" v-model="form.ministere_tutelle_1" class="form-control" :disabled="isReadOnly" />
            <span v-if="form.errors?.ministere_tutelle_1" class="text-danger small">{{ form.errors.ministere_tutelle_1 }}</span>
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Ministère de tutelle 2</label>
            <input type="text" v-model="form.ministere_tutelle_2" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Ministère de tutelle 3</label>
            <input type="text" v-model="form.ministere_tutelle_3" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Ministère de tutelle 4</label>
            <input type="text" v-model="form.ministere_tutelle_4" class="form-control" :disabled="isReadOnly" />
        </div>

        <!-- ============================================== -->
        <!-- BLOC 4 : DIRIGEANTS (Promoteur + Gérant en champs libres) -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-users"></i> Dirigeants
            </h6>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Promoteur</label>
            <input type="text" v-model="form.promoteur" class="form-control" placeholder="Nom du promoteur" :disabled="isReadOnly" />
            <span v-if="form.errors?.promoteur" class="text-danger small">{{ form.errors.promoteur }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Gérant</label>
            <input type="text" v-model="form.gerant" class="form-control" placeholder="Nom du gérant" :disabled="isReadOnly" />
            <span v-if="form.errors?.gerant" class="text-danger small">{{ form.errors.gerant }}</span>
        </div>

        <!-- ============================================== -->
        <!-- BLOC 5 : CONTACTS -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-phone"></i> Contacts
            </h6>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Téléphone principal</label>
            <input type="tel" v-model="form.telephone_principal" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Téléphone 2</label>
            <input type="tel" v-model="form.telephone_2" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Téléphone 3</label>
            <input type="tel" v-model="form.telephone_3" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">WhatsApp 1</label>
            <input type="tel" v-model="form.whatsapp_1" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">WhatsApp 2</label>
            <input type="tel" v-model="form.whatsapp_2" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Fax</label>
            <input type="tel" v-model="form.fax" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Email principal</label>
            <input type="email" v-model="form.email_principal" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Email 2</label>
            <input type="email" v-model="form.email_1" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Site web</label>
            <input type="url" v-model="form.site_web" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Facebook</label>
            <input type="text" v-model="form.facebook" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">LinkedIn</label>
            <input type="text" v-model="form.linkedin" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Twitter</label>
            <input type="text" v-model="form.twitter" class="form-control" :disabled="isReadOnly" />
        </div>

        <!-- ============================================== -->
        <!-- BLOC 6 : DESCRIPTION / VISION / MISSION -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-file-text"></i> Description
            </h6>
        </div>
        <div class="col-12">
            <label class="form-label fw-medium">Description</label>
            <textarea v-model="form.description" class="form-control" rows="3" :disabled="isReadOnly"></textarea>
        </div>
        <div class="col-12">
            <label class="form-label fw-medium">Vision</label>
            <textarea v-model="form.vision" class="form-control" rows="2" :disabled="isReadOnly"></textarea>
        </div>
        <div class="col-12">
            <label class="form-label fw-medium">Mission</label>
            <textarea v-model="form.mission" class="form-control" rows="2" :disabled="isReadOnly"></textarea>
        </div>

        <!-- ============================================== -->
        <!-- BLOC 7 : STATUT -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-check-circle"></i> Statut
            </h6>
        </div>
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
.custom-input .section-header {
    margin-top: 20px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #0056b3;
    color: #0056b3;
    font-weight: 600;
    font-size: 1rem;
}
.custom-input .col-12 h6 { margin: 0; }
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
