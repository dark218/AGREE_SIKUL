<!--
  InstitutionForm.vue — Refonte Phase 4.4 (Steppers).
  Historique : 319 lignes / 7 blocs empilés → 4 steps guidés.

  Steps :
    1. Identité       (nom, sigle, devise/slogan, devise comptable, logo)
    2. Localisation   (LocalisationBlock complet)
    3. Agrément       (date création, 4 numéros d'agrément, 4 ministères de tutelle,
                        promoteur, gérant)
    4. Contacts       (tels, whatsapp, fax, emails, réseaux, description/vision/mission, statut)
-->

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import LocalisationBlock from '@/Components/Common/LocalisationBlock.vue';
import FormStepper from '@/Components/Common/FormStepper.vue';

const { t } = useI18n();

const props = defineProps({
    form:         { type: Object, required: true },
    paysList:     { type: Array,  default: () => [] },
    regions:      { type: Array,  default: () => [] },
    departements: { type: Array,  default: () => [] },
    communes:     { type: Array,  default: () => [] },
    quartiers:    { type: Array,  default: () => [] },
    devises:      { type: Array,  default: () => [] },
    directeurs:   { type: Array,  default: () => [] },
    mode:         { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
});

const emit = defineEmits(['submit']);

const isReadOnly = props.mode === 'show';
const currentStep = ref(0);

const statusOptions = [
    { id: 'actif',     libelle: 'Actif' },
    { id: 'non_actif', libelle: 'Inactif' },
];

const deviseLabel = (d) => d ? (d.libelle ?? d.symbol ?? '') : '';

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
    { key: 'identite',     label: 'Identité',     icon: 'fas fa-building',      requiredFields: ['nom'] },
    { key: 'localisation', label: 'Localisation', icon: 'fas fa-map-marker-alt' },
    { key: 'agrement',     label: 'Agrément',     icon: 'fas fa-certificate' },
    { key: 'contacts',     label: 'Contacts & Statut', icon: 'fas fa-address-book', requiredFields: ['statut'] },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="institution-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : IDENTITÉ -->
        <template #identite>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Nom <span class="text-danger">*</span></label>
                    <input v-model="form.nom" :disabled="isReadOnly" type="text" class="form-control" required />
                    <span v-if="form.errors?.nom" class="text-danger small">{{ form.errors.nom }}</span>
                </div>
                <div class="col-md-3">
                    <label>Sigle</label>
                    <input v-model="form.sigle" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-3">
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
                <div class="col-12">
                    <label>Devise / slogan</label>
                    <input v-model="form.devise_slogan" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ex : Discipline - Travail - Succès" />
                </div>
                <div class="col-md-6">
                    <label>Logo</label>
                    <input type="file" @change="handleLogoChange" class="form-control" accept="image/*" :disabled="isReadOnly" />
                    <small class="text-muted">JPG, PNG, GIF (max 2 Mo)</small>
                    <div v-if="logoPreview" class="mt-2">
                        <img :src="logoPreview" alt="Aperçu" style="max-width: 120px; max-height: 120px; border: 1px solid #dee2e6; padding: 4px;" />
                    </div>
                </div>
            </div>
        </template>

        <!-- STEP 2 : LOCALISATION -->
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

        <!-- STEP 3 : AGRÉMENT & DIRIGEANTS -->
        <template #agrement>
            <div class="row g-3">
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-certificate me-1"></i> Création & agrément</h6>
                </div>
                <div class="col-md-3">
                    <label>Date de création</label>
                    <input v-model="form.date_creation" :disabled="isReadOnly" type="date" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Agrément 1</label>
                    <input v-model="form.numero_autorisation" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ex : RC/MINENSBA/DRL/123" />
                </div>
                <div class="col-md-3">
                    <label>Agrément 2</label>
                    <input v-model="form.numero_agrement_2" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Agrément 3</label>
                    <input v-model="form.numero_agrement_3" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Agrément 4</label>
                    <input v-model="form.numero_agrement_4" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Ministère 1</label>
                    <input v-model="form.ministere_tutelle_1" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Ministère 2</label>
                    <input v-model="form.ministere_tutelle_2" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Ministère 3</label>
                    <input v-model="form.ministere_tutelle_3" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Ministère 4</label>
                    <input v-model="form.ministere_tutelle_4" :disabled="isReadOnly" type="text" class="form-control" />
                </div>

                <hr class="mt-3" />
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-users me-1"></i> Dirigeants</h6>
                </div>
                <div class="col-md-6">
                    <label>Promoteur</label>
                    <input v-model="form.promoteur" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom du promoteur" />
                </div>
                <div class="col-md-6">
                    <label>Gérant</label>
                    <input v-model="form.gerant" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom du gérant" />
                </div>
            </div>
        </template>

        <!-- STEP 4 : CONTACTS & STATUT -->
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
