<!--
  EcoleForm.vue — refonte selon spec Orchidée

  BLOC 1 — Informations de base :
    L1: Campus | Institution (auto-fill depuis Campus)
    L2: Code | Nom
    L3: Sigle | Devise (slogan libre)
    L4: Type d'établissement | Type d'enseignement
    L5: Type de cours | Capacité maximale

  BLOC 2 — Adresse / Localisation : Adresse + LocalisationBlock

  BLOC 3 — Informations complémentaires :
    L1: Date de création | Numéro d'agrément | Ministère de tutelle | Section
    L2: Devise de tenue de la comptabilité | Logo

  + Bloc Dirigeants (table dynamique) + Contacts + Description + Statut
-->
<script setup>
import { ref, watch } from 'vue';
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
    campuses: { type: Array, default: () => [] },
    institutions: { type: Array, default: () => [] },
    typeEtablissements: { type: Array, default: () => [] },
    typeEnseignements: { type: Array, default: () => [] },
    typeCours: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    directeurs: { type: Array, default: () => [] },
    devises: { type: Array, default: () => [] },
    paysList: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    departements: { type: Array, default: () => [] },
    communes: { type: Array, default: () => [] },
    quartiers: { type: Array, default: () => [] },
});

const isReadOnly = props.mode === 'show';

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'non_actif', libelle: 'Inactif' },
    { id: 'suspendu', libelle: 'Suspendu' },
];

const deviseLabel = (d) => d ? (d.libelle ?? d.symbol ?? '') : '';

// Auto-fill Institution depuis le Campus sélectionné
watch(() => props.form.campus_id, (newCampusId) => {
    if (!newCampusId || isReadOnly) return;
    const campus = props.campuses.find((c) => String(c.id) === String(newCampusId));
    if (campus?.institution_id) {
        props.form.institution_id = campus.institution_id;
    }
});

// ── Dirigeants (table dynamique) ──
const emptyDirigeant = { nom: '', prenom: '', nom_restituer: '', fonction: '', ordre: 0 };

function addDirigeant() {
    if (!Array.isArray(props.form.dirigeants)) {
        props.form.dirigeants = [];
    }
    const nextOrder = props.form.dirigeants.length > 0
        ? Math.max(...props.form.dirigeants.map((d) => d.ordre || 0)) + 1
        : 1;
    props.form.dirigeants.push({ ...emptyDirigeant, ordre: nextOrder });
}

function removeDirigeant(index) {
    props.form.dirigeants.splice(index, 1);
}

// ── Logo ──
const logoPreview = ref(null);

function handleLogoChange(event) {
    const file = event.target.files[0];
    if (!file) return;
    props.form.logo = file;
    const reader = new FileReader();
    reader.onload = (e) => { logoPreview.value = e.target.result; };
    reader.readAsDataURL(file);
}
</script>

<template>
    <div class="row g-3 custom-input">

        <!-- ============================================== -->
        <!-- BLOC 1 : INFORMATIONS DE BASE -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-school"></i> Informations de base
            </h6>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-medium">Campus <span class="text-danger">*</span></label>
            <SearchableSelect
                v-model="form.campus_id"
                :options="campuses"
                optionValue="id"
                optionLabel="nom"
                placeholder="-- Sélectionner un campus --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.campus_id" class="text-danger small">{{ form.errors.campus_id }}</span>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">
                Institution
                <small class="text-muted">(auto-remonte depuis le campus)</small>
            </label>
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

        <div class="col-md-4">
            <label class="form-label fw-medium">Code <span class="text-danger">*</span></label>
            <input v-model="form.code" type="text" class="form-control" placeholder="Code" :disabled="isReadOnly" />
            <span v-if="form.errors?.code" class="text-danger small">{{ form.errors.code }}</span>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">Nom <span class="text-danger">*</span></label>
            <input v-model="form.nom" type="text" class="form-control" placeholder="Nom de l'école" :disabled="isReadOnly" />
            <span v-if="form.errors?.nom" class="text-danger small">{{ form.errors.nom }}</span>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-medium">Sigle</label>
            <input v-model="form.sigle" type="text" class="form-control" placeholder="Ex: ENSPG" :disabled="isReadOnly" />
            <span v-if="form.errors?.sigle" class="text-danger small">{{ form.errors.sigle }}</span>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">
                Devise
                <small class="text-muted">(slogan)</small>
            </label>
            <input v-model="form.devise_slogan" type="text" class="form-control" placeholder="Ex: Excellence et rigueur" :disabled="isReadOnly" />
            <span v-if="form.errors?.devise_slogan" class="text-danger small">{{ form.errors.devise_slogan }}</span>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-medium">Type d'établissement</label>
            <SearchableSelect
                v-model="form.type_etablissement_id"
                :options="typeEtablissements"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.type_etablissement_id" class="text-danger small">{{ form.errors.type_etablissement_id }}</span>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">Type d'enseignement</label>
            <SearchableSelect
                v-model="form.type_enseignement_id"
                :options="typeEnseignements"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.type_enseignement_id" class="text-danger small">{{ form.errors.type_enseignement_id }}</span>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-medium">Type de cours</label>
            <SearchableSelect
                v-model="form.type_cours_id"
                :options="typeCours"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.type_cours_id" class="text-danger small">{{ form.errors.type_cours_id }}</span>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">Capacité maximale</label>
            <input
                v-model.number="form.capacite_maximale"
                type="number"
                class="form-control"
                placeholder="Capacité maximale"
                :disabled="isReadOnly"
                min="0"
            />
            <span v-if="form.errors?.capacite_maximale" class="text-danger small">{{ form.errors.capacite_maximale }}</span>
        </div>

        <!-- ============================================== -->
        <!-- BLOC 2 : ADRESSE / LOCALISATION -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-map-marker"></i> Adresse / Localisation
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
                addressLabel="Adresse"
            />
        </div>

        <!-- ============================================== -->
        <!-- BLOC 3 : INFORMATIONS COMPLÉMENTAIRES -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-info-circle"></i> Informations complémentaires
            </h6>
        </div>

        <div class="col-sm-3">
            <label class="form-label fw-medium">Date de création</label>
            <input v-model="form.date_creation" type="date" class="form-control" :disabled="isReadOnly" />
            <span v-if="form.errors?.date_creation" class="text-danger small">{{ form.errors.date_creation }}</span>
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Numéro d'agrément</label>
            <input v-model="form.numero_agrement" type="text" class="form-control" placeholder="Ex: AGR/2020/001" :disabled="isReadOnly" />
            <span v-if="form.errors?.numero_agrement" class="text-danger small">{{ form.errors.numero_agrement }}</span>
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Ministère de tutelle</label>
            <input v-model="form.ministere_tutelle" type="text" class="form-control" :disabled="isReadOnly" />
            <span v-if="form.errors?.ministere_tutelle" class="text-danger small">{{ form.errors.ministere_tutelle }}</span>
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Section</label>
            <SearchableSelect
                v-model="form.section_id"
                :options="sections"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.section_id" class="text-danger small">{{ form.errors.section_id }}</span>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-medium">Devise de tenue de la comptabilité</label>
            <SearchableSelect
                v-model="form.devise_comptabilite_id"
                :options="devises"
                optionValue="id"
                :optionLabel="deviseLabel"
                placeholder="-- Sélectionner une devise --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.devise_comptabilite_id" class="text-danger small">{{ form.errors.devise_comptabilite_id }}</span>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">Logo</label>
            <input type="file" @change="handleLogoChange" class="form-control" accept="image/*" :disabled="isReadOnly" />
            <small class="text-muted d-block">JPG, PNG, GIF</small>
            <div v-if="logoPreview" class="mt-2">
                <img :src="logoPreview" alt="Aperçu" style="max-width: 120px; max-height: 120px; border: 1px solid #dee2e6; padding: 4px;" />
            </div>
            <span v-if="form.errors?.logo" class="text-danger small">{{ form.errors.logo }}</span>
        </div>

        <!-- ============================================== -->
        <!-- BLOC DIRIGEANTS (conservé du form existant) -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-users"></i> Dirigeants
                <button
                    v-if="!isReadOnly"
                    type="button"
                    class="btn btn-sm btn-primary float-end"
                    @click="addDirigeant"
                    title="Ajouter un dirigeant"
                >
                    <i class="fa fa-plus"></i> Ajouter
                </button>
            </h6>
        </div>
        <div v-if="Array.isArray(form.dirigeants) && form.dirigeants.length > 0" class="col-12">
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Nom à restituer</th>
                            <th>Fonction</th>
                            <th v-if="!isReadOnly" style="width: 40px">×</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(d, i) in form.dirigeants" :key="i">
                            <td><input v-model="d.nom" type="text" class="form-control form-control-sm" :disabled="isReadOnly" /></td>
                            <td><input v-model="d.prenom" type="text" class="form-control form-control-sm" :disabled="isReadOnly" /></td>
                            <td><input v-model="d.nom_restituer" type="text" class="form-control form-control-sm" :disabled="isReadOnly" /></td>
                            <td><input v-model="d.fonction" type="text" class="form-control form-control-sm" :disabled="isReadOnly" /></td>
                            <td v-if="!isReadOnly" class="text-center">
                                <button type="button" class="btn btn-sm btn-danger" @click="removeDirigeant(i)" title="Supprimer">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- BLOC CONTACTS -->
        <div class="col-12">
            <h6 class="section-header"><i class="fa fa-phone"></i> Contacts</h6>
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Téléphone principal</label>
            <input v-model="form.telephone_principal" type="tel" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Téléphone 2</label>
            <input v-model="form.telephone_2" type="tel" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Téléphone 3</label>
            <input v-model="form.telephone_3" type="tel" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">WhatsApp 1</label>
            <input v-model="form.whatsapp_1" type="tel" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">WhatsApp 2</label>
            <input v-model="form.whatsapp_2" type="tel" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Fax</label>
            <input v-model="form.fax" type="tel" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Email principal</label>
            <input v-model="form.email_principal" type="email" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Email 2</label>
            <input v-model="form.email_1" type="email" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Site web</label>
            <input v-model="form.site_web" type="text" class="form-control" placeholder="www.exemple.com" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Facebook</label>
            <input v-model="form.facebook" type="text" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">LinkedIn</label>
            <input v-model="form.linkedin" type="text" class="form-control" :disabled="isReadOnly" />
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-medium">Twitter</label>
            <input v-model="form.twitter" type="text" class="form-control" :disabled="isReadOnly" />
        </div>

        <!-- BLOC DESCRIPTION -->
        <div class="col-12">
            <h6 class="section-header"><i class="fa fa-file-text"></i> Description</h6>
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

        <!-- BLOC STATUT -->
        <div class="col-12">
            <h6 class="section-header"><i class="fa fa-check-circle"></i> Statut</h6>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">Statut</label>
            <SearchableSelect
                v-model="form.statut"
                :options="statusOptions"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
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
.custom-input .form-control-sm {
    padding: 0.375rem 0.5rem;
}
</style>
