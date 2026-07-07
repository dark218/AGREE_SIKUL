<!--
  EcoleForm.vue — Refonte Phase 4.4 (Steppers).
  Historique : 458 lignes / ~50 champs mono-page → 5 steps guidés.

  Steps :
    1. Identité      (campus → institution auto, code, nom, sigle, devise/slogan)
    2. Typologie     (type établissement/enseignement/cours, section, capacité,
                       date création, agrément, ministère, devise comptable, logo)
    3. Localisation  (LocalisationBlock : adresse + quartier → commune → dept → région → pays)
    4. Dirigeants    (table dynamique add/remove)
    5. Contacts & Statut (tels, whatsapp, fax, emails, réseaux, description/vision/mission, statut)
-->

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import LocalisationBlock from '@/Components/Common/LocalisationBlock.vue';
import FormStepper from '@/Components/Common/FormStepper.vue';

const { t } = useI18n();

const props = defineProps({
    form:               { type: Object, required: true },
    mode:               { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
    campuses:           { type: Array,  default: () => [] },
    institutions:       { type: Array,  default: () => [] },
    typeEtablissements: { type: Array,  default: () => [] },
    typeEnseignements:  { type: Array,  default: () => [] },
    typeCours:          { type: Array,  default: () => [] },
    sections:           { type: Array,  default: () => [] },
    directeurs:         { type: Array,  default: () => [] },
    devises:            { type: Array,  default: () => [] },
    paysList:           { type: Array,  default: () => [] },
    regions:            { type: Array,  default: () => [] },
    departements:       { type: Array,  default: () => [] },
    communes:           { type: Array,  default: () => [] },
    quartiers:          { type: Array,  default: () => [] },
});

const emit = defineEmits(['submit']);

const isReadOnly = props.mode === 'show';
const currentStep = ref(0);

const statusOptions = [
    { id: 'actif',     libelle: 'Actif' },
    { id: 'non_actif', libelle: 'Inactif' },
    { id: 'suspendu',  libelle: 'Suspendu' },
];

const deviseLabel = (d) => d ? (d.libelle ?? d.symbol ?? '') : '';

// Auto-fill Institution depuis Campus.
watch(() => props.form.campus_id, (id) => {
    if (!id || isReadOnly) return;
    const c = props.campuses.find(x => String(x.id) === String(id));
    if (c?.institution_id) props.form.institution_id = c.institution_id;
});

// Dirigeants dynamiques.
const emptyDirigeant = { nom: '', prenom: '', nom_restituer: '', fonction: '', ordre: 0 };
if (!Array.isArray(props.form.dirigeants)) props.form.dirigeants = [];

function addDirigeant() {
    const nextOrder = props.form.dirigeants.length > 0
        ? Math.max(...props.form.dirigeants.map(d => d.ordre || 0)) + 1
        : 1;
    props.form.dirigeants.push({ ...emptyDirigeant, ordre: nextOrder });
}
function removeDirigeant(i) { props.form.dirigeants.splice(i, 1); }

// Logo preview.
const logoPreview = ref(null);
function handleLogoChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    props.form.logo = file;
    const reader = new FileReader();
    reader.onload = (ev) => { logoPreview.value = ev.target.result; };
    reader.readAsDataURL(file);
}

const steps = [
    { key: 'identite',     label: 'Identité',       icon: 'fas fa-school',        requiredFields: ['campus_id', 'code', 'nom'] },
    { key: 'typologie',    label: 'Typologie',      icon: 'fas fa-tags' },
    { key: 'localisation', label: 'Localisation',   icon: 'fas fa-map-marker-alt' },
    { key: 'dirigeants',   label: 'Dirigeants',     icon: 'fas fa-users' },
    { key: 'contacts',     label: 'Contacts & Statut', icon: 'fas fa-address-book', requiredFields: ['statut'] },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="ecole-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : IDENTITÉ -->
        <template #identite>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Campus <span class="text-danger">*</span></label>
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
                <div class="col-md-6">
                    <label>Institution <span class="badge bg-secondary">auto</span></label>
                    <SearchableSelect
                        v-model="form.institution_id"
                        :options="institutions"
                        optionValue="id"
                        optionLabel="nom"
                        placeholder="-- Auto-remonte depuis le campus --"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4">
                    <label>Code <span class="text-danger">*</span></label>
                    <input v-model="form.code" :disabled="isReadOnly" type="text" class="form-control" />
                    <span v-if="form.errors?.code" class="text-danger small">{{ form.errors.code }}</span>
                </div>
                <div class="col-md-4">
                    <label>Nom <span class="text-danger">*</span></label>
                    <input v-model="form.nom" :disabled="isReadOnly" type="text" class="form-control" />
                    <span v-if="form.errors?.nom" class="text-danger small">{{ form.errors.nom }}</span>
                </div>
                <div class="col-md-4">
                    <label>Sigle</label>
                    <input v-model="form.sigle" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ex : ENSPG" />
                </div>
                <div class="col-12">
                    <label>Devise / slogan</label>
                    <input v-model="form.devise_slogan" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ex : Excellence et rigueur" />
                </div>
            </div>
        </template>

        <!-- STEP 2 : TYPOLOGIE -->
        <template #typologie>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Type d'établissement</label>
                    <SearchableSelect
                        v-model="form.type_etablissement_id"
                        :options="typeEtablissements"
                        optionValue="id"
                        optionLabel="libelle"
                        placeholder="-- Sélectionner --"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4">
                    <label>Type d'enseignement</label>
                    <SearchableSelect
                        v-model="form.type_enseignement_id"
                        :options="typeEnseignements"
                        optionValue="id"
                        optionLabel="libelle"
                        placeholder="-- Sélectionner --"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4">
                    <label>Type de cours</label>
                    <SearchableSelect
                        v-model="form.type_cours_id"
                        :options="typeCours"
                        optionValue="id"
                        optionLabel="libelle"
                        placeholder="-- Sélectionner --"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4">
                    <label>Section</label>
                    <SearchableSelect
                        v-model="form.section_id"
                        :options="sections"
                        optionValue="id"
                        optionLabel="libelle"
                        placeholder="-- Sélectionner --"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4">
                    <label>Capacité maximale</label>
                    <input v-model.number="form.capacite_maximale" :disabled="isReadOnly" type="number" min="0" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Date de création</label>
                    <input v-model="form.date_creation" :disabled="isReadOnly" type="date" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Numéro d'agrément</label>
                    <input v-model="form.numero_agrement" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ex : AGR/2020/001" />
                </div>
                <div class="col-md-4">
                    <label>Ministère de tutelle</label>
                    <input v-model="form.ministere_tutelle" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Devise de la comptabilité</label>
                    <SearchableSelect
                        v-model="form.devise_comptabilite_id"
                        :options="devises"
                        optionValue="id"
                        :optionLabel="deviseLabel"
                        placeholder="-- Sélectionner --"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-8">
                    <label>Logo</label>
                    <input type="file" @change="handleLogoChange" class="form-control" accept="image/*" :disabled="isReadOnly" />
                    <small class="text-muted d-block">JPG, PNG, GIF</small>
                    <div v-if="logoPreview" class="mt-2">
                        <img :src="logoPreview" alt="Aperçu" style="max-width: 120px; max-height: 120px; border: 1px solid #dee2e6; padding: 4px;" />
                    </div>
                </div>
            </div>
        </template>

        <!-- STEP 3 : LOCALISATION -->
        <template #localisation>
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
        </template>

        <!-- STEP 4 : DIRIGEANTS -->
        <template #dirigeants>
            <div class="row g-3">
                <div class="col-12">
                    <button
                        v-if="!isReadOnly"
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="addDirigeant"
                    >
                        <i class="fa fa-plus"></i> Ajouter un dirigeant
                    </button>
                </div>
                <div v-if="form.dirigeants.length === 0" class="col-12">
                    <div class="alert alert-info mb-0">
                        <i class="fa fa-info-circle"></i> Aucun dirigeant. Cliquez sur « Ajouter » pour en créer un.
                    </div>
                </div>
                <div v-else class="col-12">
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
            </div>
        </template>

        <!-- STEP 5 : CONTACTS & STATUT -->
        <template #contacts>
            <div class="row g-3">
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-phone me-1"></i> Téléphones</h6>
                </div>
                <div class="col-md-4">
                    <label>Téléphone principal</label>
                    <input v-model="form.telephone_principal" :disabled="isReadOnly" type="tel" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Téléphone 2</label>
                    <input v-model="form.telephone_2" :disabled="isReadOnly" type="tel" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Téléphone 3</label>
                    <input v-model="form.telephone_3" :disabled="isReadOnly" type="tel" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>WhatsApp 1</label>
                    <input v-model="form.whatsapp_1" :disabled="isReadOnly" type="tel" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>WhatsApp 2</label>
                    <input v-model="form.whatsapp_2" :disabled="isReadOnly" type="tel" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Fax</label>
                    <input v-model="form.fax" :disabled="isReadOnly" type="tel" class="form-control" />
                </div>

                <hr class="mt-3" />
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-envelope me-1"></i> Emails & réseaux</h6>
                </div>
                <div class="col-md-6">
                    <label>Email principal</label>
                    <input v-model="form.email_principal" :disabled="isReadOnly" type="email" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label>Email secondaire</label>
                    <input v-model="form.email_1" :disabled="isReadOnly" type="email" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Site web</label>
                    <input v-model="form.site_web" :disabled="isReadOnly" type="text" class="form-control" placeholder="www.exemple.com" />
                </div>
                <div class="col-md-3">
                    <label>Facebook</label>
                    <input v-model="form.facebook" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>LinkedIn</label>
                    <input v-model="form.linkedin" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Twitter</label>
                    <input v-model="form.twitter" :disabled="isReadOnly" type="text" class="form-control" />
                </div>

                <hr class="mt-3" />
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-file-text me-1"></i> Description</h6>
                </div>
                <div class="col-12">
                    <label>Description</label>
                    <textarea v-model="form.description" :disabled="isReadOnly" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                    <label>Vision</label>
                    <textarea v-model="form.vision" :disabled="isReadOnly" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                    <label>Mission</label>
                    <textarea v-model="form.mission" :disabled="isReadOnly" class="form-control" rows="2"></textarea>
                </div>

                <hr class="mt-3" />
                <div class="col-md-4">
                    <label>Statut <span class="text-danger">*</span></label>
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
    </FormStepper>
</template>

<style scoped>
.form-control {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.55rem 0.85rem;
    font-size: 0.95rem;
}
.form-control:focus {
    border-color: #0b5697;
    box-shadow: 0 0 0 0.2rem rgba(11, 86, 151, 0.15);
}
label {
    font-weight: 500;
    color: #374151;
    font-size: 0.9rem;
    margin-bottom: 0.4rem;
    display: block;
}
</style>
