<!--
  UserForm.vue — Refonte Phase 2.6 (Steppers).
  Historique : 670 lignes empilées en 8 sections → 4 steps guidés.

  Steps :
    1. Identité       (nom, prenoms, tel, email, date/lieu de naissance, adresse)
    2. Rôle & Statut  (pays, rôle, statut admin, kyc_status super-admin)
    3. KYC            (alias_smil vérif unicité, type_piece, numero, date/lieu délivrance)
    4. Photo & Pièces (photoprofile, piecerecto, pieceverso avec zoom)

  Contrat vers le parent :
    - defineExpose({ getFormData, form }) — préservé pour rétro-compat
    - emit('submit', formData) — nouveau, pour capture par Create.vue / Edit.vue
      quand le stepper émet son propre 'submit' à la dernière étape.
-->

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import ImageZoomModal from '@/Components/Common/ImageZoomModal.vue';
import FormStepper from '@/Components/Common/FormStepper.vue';

const props = defineProps({
    user:          { type: Object,  default: () => ({}) },
    roles:         { type: Array,   required: true },
    payss:         { type: Array,   required: true },
    showPaysField: { type: Boolean, default: true },
    isReadOnly:    { type: Boolean, default: false },
    isAdmin:       { type: Boolean, default: false },
    isSuperAdmin:  { type: Boolean, default: false },
    statuts:       { type: Array,   default: () => [] },
    kycStatuts:    { type: Array,   default: () => [] },
    typePieces:    { type: Array,   default: () => [] },
    errors:        { type: Object,  default: () => ({}) },
});

const emit = defineEmits(['submit']);

const currentStep = ref(0);

const form = ref({
    nom: '', prenoms: '', tel: '', email: '',
    role_id: '', pays_id: '',
    uuid: '', full_login: '', code_owner: '', code_parrain: '',
    alias_smil: '', type_piece: '', numero_piece: '',
    date_delivrance: '', date_naissance: '',
    lieu_delivrance: '', lieu_naissance: '', adresse: '',
    statut: '', kyc_status: '',
});

const isKycVerified = computed(() => props.user?.kyc_status === 'verifie');
const isEdit = computed(() => !!props.user?.id);

// Mappe {value,label} → {id,label} pour StylishSelect.
const mapToOptions = (arr) => (arr || []).map(x => ({ id: x.value, label: x.label }));
const statutsOptions    = computed(() => mapToOptions(props.statuts));
const kycStatutsOptions = computed(() => mapToOptions(props.kycStatuts));
const typePiecesOptions = computed(() => mapToOptions(props.typePieces));

const formatDateForInput = (d) => (d ? String(d).split(' ')[0].split('T')[0] : '');

// Vérif unicité alias_smil (debounced).
const aliasSmilError = ref('');
const aliasSmilChecking = ref(false);
const aliasSmilValid = ref(false);

const checkAliasSmilUniqueness = debounce(async (alias) => {
    if (!alias || alias.trim() === '') {
        aliasSmilError.value = '';
        aliasSmilValid.value = false;
        return;
    }
    aliasSmilChecking.value = true;
    aliasSmilError.value = '';
    try {
        const { data } = await axios.post(route('administration.users.check-alias'), {
            alias_smil: alias,
            user_id: props.user?.id || null,
        });
        if (data.exists) {
            aliasSmilError.value = data.message || 'Cet alias est déjà utilisé';
            aliasSmilValid.value = false;
        } else {
            aliasSmilValid.value = true;
        }
    } catch (e) {
        console.error('checkAliasSmilUniqueness:', e);
    } finally {
        aliasSmilChecking.value = false;
    }
}, 500);

watch(() => form.value.alias_smil, (v) => {
    if (!isKycVerified.value) checkAliasSmilUniqueness(v);
});

// Uploads.
const photoPreview      = ref(null);
const pieceRectoPreview = ref(null);
const pieceVersoPreview = ref(null);
const photoFile      = ref(null);
const pieceRectoFile = ref(null);
const pieceVersoFile = ref(null);

const populateForm = (u) => {
    if (!u) return;
    form.value = {
        nom: u.nom || '', prenoms: u.prenoms || '',
        tel: u.login || '', email: u.email || '',
        role_id: u.current_role || '', pays_id: u.pays_id || '',
        uuid: u.uuid || '', full_login: u.full_login || '',
        code_owner: u.code_owner || '', code_parrain: u.code_parrain || '',
        alias_smil: u.alias_smil || '',
        type_piece: u.type_piece || '', numero_piece: u.numero_piece || '',
        date_delivrance: formatDateForInput(u.date_delivrance),
        date_naissance: formatDateForInput(u.date_naissance),
        lieu_delivrance: u.lieu_delivrance || '',
        lieu_naissance: u.lieu_naissance || '',
        adresse: u.adresse || '',
        statut: u.statut || '', kyc_status: u.kyc_status || '',
    };
    if (u.photoprofile) photoPreview.value = u.photoprofile;
    if (u.piecerecto)   pieceRectoPreview.value = u.piecerecto;
    if (u.pieceverso)   pieceVersoPreview.value = u.pieceverso;
};

onMounted(() => populateForm(props.user));
watch(() => props.user, populateForm, { deep: true });

const makeUploadHandler = (fileRef, previewRef) => (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    fileRef.value = file;
    const reader = new FileReader();
    reader.onload = (e) => (previewRef.value = e.target.result);
    reader.readAsDataURL(file);
};
const handlePhotoChange      = makeUploadHandler(photoFile,      photoPreview);
const handlePieceRectoChange = makeUploadHandler(pieceRectoFile, pieceRectoPreview);
const handlePieceVersoChange = makeUploadHandler(pieceVersoFile, pieceVersoPreview);

const defaultImage = 'https://caer.univ-amu.fr/wp-content/uploads/default-placeholder.png';
const showZoomModal = ref(false);
const zoomImageUrl = ref('');
const zoomImageAlt = ref('');
const openZoomModal = (url, alt) => {
    if (url && url !== defaultImage) {
        zoomImageUrl.value = url;
        zoomImageAlt.value = alt;
        showZoomModal.value = true;
    }
};

function getFormData() {
    const fd = new FormData();
    const v = form.value;
    fd.append('nom', v.nom);
    fd.append('prenoms', v.prenoms);
    fd.append('tel', v.tel);
    fd.append('email', v.email || '');
    fd.append('role_id', v.role_id);
    if (props.showPaysField) fd.append('pays_id', v.pays_id);
    if (!isKycVerified.value) fd.append('alias_smil', v.alias_smil || '');
    fd.append('type_piece', v.type_piece || '');
    fd.append('numero_piece', v.numero_piece || '');
    fd.append('date_delivrance', v.date_delivrance || '');
    fd.append('date_naissance', v.date_naissance || '');
    fd.append('lieu_delivrance', v.lieu_delivrance || '');
    fd.append('lieu_naissance', v.lieu_naissance || '');
    fd.append('adresse', v.adresse || '');
    if (props.isAdmin)      fd.append('statut', v.statut || '');
    if (props.isSuperAdmin) fd.append('kyc_status', v.kyc_status || '');
    if (photoFile.value)      fd.append('photoprofile', photoFile.value);
    if (pieceRectoFile.value) fd.append('piecerecto', pieceRectoFile.value);
    if (pieceVersoFile.value) fd.append('pieceverso', pieceVersoFile.value);
    return fd;
}

const onStepperSubmit = () => emit('submit', getFormData());

const steps = [
    { key: 'identite', label: 'Identité',       icon: 'fas fa-id-card',    requiredFields: ['nom', 'prenoms', 'tel'] },
    { key: 'role',     label: 'Rôle & Statut',  icon: 'fas fa-user-shield', requiredFields: ['role_id'] },
    { key: 'kyc',      label: 'KYC',            icon: 'fas fa-passport' },
    { key: 'photo',    label: 'Photo & Pièces', icon: 'fas fa-camera' },
];

defineExpose({ getFormData, form });
</script>

<template>
    <div class="user-form">
        <FormStepper
            v-model="currentStep"
            :steps="steps"
            :form="form"
            persist-key="user-form"
            @submit="onStepperSubmit"
        >
            <!-- STEP 1 : IDENTITÉ -->
            <template #identite>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Nom <span class="text-danger">*</span></label>
                        <input v-model="form.nom" :disabled="isReadOnly" type="text" class="form-control" :class="{ 'is-invalid': errors.nom }" />
                        <div v-if="errors.nom" class="invalid-feedback">{{ errors.nom }}</div>
                    </div>
                    <div class="col-md-6">
                        <label>Prénoms <span class="text-danger">*</span></label>
                        <input v-model="form.prenoms" :disabled="isReadOnly" type="text" class="form-control" :class="{ 'is-invalid': errors.prenoms }" />
                        <div v-if="errors.prenoms" class="invalid-feedback">{{ errors.prenoms }}</div>
                    </div>
                    <div class="col-md-6">
                        <label>Téléphone <span class="text-danger">*</span></label>
                        <input v-model="form.tel" :disabled="isReadOnly || isEdit" type="text" class="form-control" :class="{ 'is-invalid': errors.tel }" />
                        <small v-if="isEdit" class="text-muted">Non modifiable après création</small>
                        <div v-if="errors.tel" class="invalid-feedback">{{ errors.tel }}</div>
                    </div>
                    <div class="col-md-6">
                        <label>Email</label>
                        <input v-model="form.email" :disabled="isReadOnly" type="email" class="form-control" :class="{ 'is-invalid': errors.email }" />
                        <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                    </div>
                    <div class="col-md-6">
                        <label>Date de naissance</label>
                        <input v-model="form.date_naissance" :disabled="isReadOnly" type="date" class="form-control" />
                    </div>
                    <div class="col-md-6">
                        <label>Lieu de naissance</label>
                        <input v-model="form.lieu_naissance" :disabled="isReadOnly" type="text" class="form-control" />
                    </div>
                    <div class="col-12">
                        <label>Adresse</label>
                        <input v-model="form.adresse" :disabled="isReadOnly" type="text" class="form-control" />
                    </div>
                </div>
            </template>

            <!-- STEP 2 : RÔLE & STATUT -->
            <template #role>
                <div class="row g-3">
                    <div v-if="showPaysField" class="col-md-6">
                        <label>Pays <span class="text-danger">*</span></label>
                        <StylishSelect
                            v-model="form.pays_id"
                            :options="payss"
                            option-value="id"
                            option-label="libelle"
                            placeholder="Sélectionner un pays"
                            :disabled="isReadOnly || isEdit"
                            :clearable="!isReadOnly && !isEdit"
                        />
                        <small v-if="isEdit" class="text-muted">Non modifiable après création</small>
                        <div v-if="errors.pays_id" class="text-danger small">{{ errors.pays_id }}</div>
                    </div>
                    <div class="col-md-6">
                        <label>Rôle <span class="text-danger">*</span></label>
                        <StylishSelect
                            v-model="form.role_id"
                            :options="roles"
                            option-value="name"
                            option-label="label"
                            placeholder="Sélectionner un rôle"
                            :disabled="isReadOnly || isEdit"
                            :clearable="!isReadOnly && !isEdit"
                        />
                        <small v-if="isEdit" class="text-muted">Non modifiable après création</small>
                        <div v-if="errors.role_id" class="text-danger small">{{ errors.role_id }}</div>
                    </div>
                    <div v-if="isAdmin && isEdit" class="col-md-6">
                        <label>Statut</label>
                        <StylishSelect
                            v-model="form.statut"
                            :options="statutsOptions"
                            option-value="id"
                            option-label="label"
                            placeholder="Sélectionner un statut"
                            :disabled="isReadOnly"
                            :searchable="false"
                        />
                        <div v-if="errors.statut" class="text-danger small">{{ errors.statut }}</div>
                    </div>
                    <div v-if="isSuperAdmin && isEdit" class="col-md-6">
                        <label>Statut KYC</label>
                        <StylishSelect
                            v-model="form.kyc_status"
                            :options="kycStatutsOptions"
                            option-value="id"
                            option-label="label"
                            placeholder="Sélectionner un statut KYC"
                            :disabled="isReadOnly"
                            :searchable="false"
                        />
                        <div v-if="errors.kyc_status" class="text-danger small">{{ errors.kyc_status }}</div>
                    </div>

                    <template v-if="isEdit">
                        <hr class="mt-4" />
                        <div class="col-12">
                            <h6 class="text-muted"><i class="fa fa-lock me-1"></i> Identifiants système (lecture seule)</h6>
                        </div>
                        <div class="col-md-6">
                            <label>UUID</label>
                            <input v-model="form.uuid" type="text" class="form-control" disabled readonly />
                        </div>
                        <div class="col-md-6">
                            <label>Login complet</label>
                            <input v-model="form.full_login" type="text" class="form-control" disabled readonly />
                        </div>
                        <div class="col-md-6">
                            <label>Code Owner</label>
                            <input v-model="form.code_owner" type="text" class="form-control" disabled readonly />
                        </div>
                        <div class="col-md-6">
                            <label>Code Parrain</label>
                            <input v-model="form.code_parrain" type="text" class="form-control" disabled readonly />
                        </div>
                    </template>
                </div>
            </template>

            <!-- STEP 3 : KYC -->
            <template #kyc>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>
                            Alias Smil
                            <span v-if="isKycVerified && isEdit" class="text-muted small">(verrouillé — KYC vérifié)</span>
                            <span v-if="aliasSmilChecking" class="text-info small ms-2">
                                <i class="fas fa-spinner fa-spin"></i> Vérification…
                            </span>
                            <span v-if="aliasSmilValid && !aliasSmilChecking && form.alias_smil" class="text-success small ms-2">
                                <i class="fas fa-check"></i> Disponible
                            </span>
                        </label>
                        <input
                            v-model="form.alias_smil"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': errors.alias_smil || aliasSmilError, 'is-valid': aliasSmilValid && !aliasSmilChecking && form.alias_smil }"
                            :disabled="isReadOnly || (isKycVerified && isEdit)"
                        />
                        <div v-if="aliasSmilError" class="invalid-feedback d-block">
                            <i class="fas fa-exclamation-triangle"></i> {{ aliasSmilError }}
                        </div>
                        <div v-if="errors.alias_smil" class="invalid-feedback">{{ errors.alias_smil }}</div>
                    </div>

                    <hr class="mt-3" />
                    <div class="col-md-6">
                        <label>Type de pièce</label>
                        <StylishSelect
                            v-model="form.type_piece"
                            :options="typePiecesOptions"
                            option-value="id"
                            option-label="label"
                            placeholder="Sélectionner un type de pièce"
                            :disabled="isReadOnly"
                            :searchable="false"
                        />
                        <div v-if="errors.type_piece" class="text-danger small">{{ errors.type_piece }}</div>
                    </div>
                    <div class="col-md-6">
                        <label>Numéro de pièce</label>
                        <input v-model="form.numero_piece" :disabled="isReadOnly" type="text" class="form-control" :class="{ 'is-invalid': errors.numero_piece }" />
                        <div v-if="errors.numero_piece" class="invalid-feedback">{{ errors.numero_piece }}</div>
                    </div>
                    <div class="col-md-6">
                        <label>Date de délivrance</label>
                        <input v-model="form.date_delivrance" :disabled="isReadOnly" type="date" class="form-control" />
                    </div>
                    <div class="col-md-6">
                        <label>Lieu de délivrance</label>
                        <input v-model="form.lieu_delivrance" :disabled="isReadOnly" type="text" class="form-control" />
                    </div>
                </div>
            </template>

            <!-- STEP 4 : PHOTO & PIÈCES -->
            <template #photo>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label>Photo de profil</label>
                        <div class="preview-container">
                            <img :src="photoPreview || defaultImage" alt="Photo de profil" class="preview-img" @click="openZoomModal(photoPreview, 'Photo de profil')" />
                        </div>
                        <input v-if="!isReadOnly" type="file" class="form-control mt-2" accept=".png,.jpg,.jpeg" @change="handlePhotoChange" />
                    </div>
                    <div class="col-md-4">
                        <label>Pièce d'identité — Recto</label>
                        <div class="preview-container position-relative">
                            <img :src="pieceRectoPreview || defaultImage" alt="Pièce recto" class="preview-img" @click="openZoomModal(pieceRectoPreview, 'Pièce recto')" />
                            <button v-if="pieceRectoPreview && pieceRectoPreview !== defaultImage" type="button" class="btn btn-sm btn-info zoom-btn" @click="openZoomModal(pieceRectoPreview, 'Pièce recto')" title="Agrandir">
                                <i class="fa fa-search-plus"></i>
                            </button>
                        </div>
                        <input v-if="!isReadOnly" type="file" class="form-control mt-2" accept=".png,.jpg,.jpeg" @change="handlePieceRectoChange" />
                    </div>
                    <div class="col-md-4">
                        <label>Pièce d'identité — Verso</label>
                        <div class="preview-container position-relative">
                            <img :src="pieceVersoPreview || defaultImage" alt="Pièce verso" class="preview-img" @click="openZoomModal(pieceVersoPreview, 'Pièce verso')" />
                            <button v-if="pieceVersoPreview && pieceVersoPreview !== defaultImage" type="button" class="btn btn-sm btn-info zoom-btn" @click="openZoomModal(pieceVersoPreview, 'Pièce verso')" title="Agrandir">
                                <i class="fa fa-search-plus"></i>
                            </button>
                        </div>
                        <input v-if="!isReadOnly" type="file" class="form-control mt-2" accept=".png,.jpg,.jpeg" @change="handlePieceVersoChange" />
                    </div>
                </div>
            </template>
        </FormStepper>

        <ImageZoomModal
            :show="showZoomModal"
            :image-url="zoomImageUrl"
            :alt="zoomImageAlt"
            @close="showZoomModal = false"
        />
    </div>
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
.preview-container {
    background: #f8f9fa;
    border: 2px dashed #e0e0e0;
    border-radius: 8px;
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.preview-img {
    max-width: 100%;
    max-height: 180px;
    object-fit: contain;
    cursor: pointer;
}
.preview-img:hover {
    opacity: 0.9;
}
.zoom-btn {
    position: absolute;
    top: 6px;
    right: 6px;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}
</style>
