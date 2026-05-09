<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import ImageZoomModal from '@/Components/Common/ImageZoomModal.vue';
const props = defineProps({
    user: {
        type: Object,
        default: () => ({})
    },
    roles: {
        type: Array,
        required: true
    },
    payss: {
        type: Array,
        required: true
    },
    showPaysField: {
        type: Boolean,
        default: true
    },
    isReadOnly: {
        type: Boolean,
        default: false
    },
    isAdmin: {
        type: Boolean,
        default: false
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    },
    statuts: {
        type: Array,
        default: () => []
    },
    kycStatuts: {
        type: Array,
        default: () => []
    },
    typePieces: {
        type: Array,
        default: () => []
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});
const emit = defineEmits(['submit']);
// État local du formulaire
const form = ref({
    nom: '',
    prenoms: '',
    tel: '',
    email: '',
    role_id: '',
    pays_id: '',
    // Champs read-only
    uuid: '',
    full_login: '',
    code_owner: '',
    code_parrain: '',
    // Champs modifiables
    alias_smil: '',
    type_piece: '',
    numero_piece: '',
    date_delivrance: '',
    date_naissance: '',
    lieu_delivrance: '',
    lieu_naissance: '',
    adresse: '',
    statut: '',
    kyc_status: '',
});
// Vérifier si le KYC est vérifié (pour bloquer alias_smil)
const isKycVerified = computed(() => {
    return props.user?.kyc_status === 'verifie';
});
// Transformer statuts (array avec {value, label}) pour StylishSelect
const statutsOptions = computed(() => {
    return (props.statuts || []).map(item => ({
        id: item.value,
        label: item.label
    }));
});
// Transformer kycStatuts (array avec {value, label}) pour StylishSelect
const kycStatutsOptions = computed(() => {
    return (props.kycStatuts || []).map(item => ({
        id: item.value,
        label: item.label
    }));
});
// Transformer typePieces (array avec {value, label}) pour StylishSelect
const typePiecesOptions = computed(() => {
    return (props.typePieces || []).map(item => ({
        id: item.value,
        label: item.label
    }));
});
// Fonction pour formater les dates (2026-01-03 00:00:00 -> 2026-01-03)
function formatDateForInput(dateString) {
    if (!dateString) return '';
    // Si la date contient un espace (datetime), on garde juste la partie date
    if (dateString.includes(' ')) {
        return dateString.split(' ')[0];
    }
    // Si c'est déjà au bon format, on retourne tel quel
    return dateString;
}
// Vérification de l'unicité de alias_smil
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
    aliasSmilValid.value = false;
    try {
        const response = await axios.post(route('administration.users.check-alias'), {
            alias_smil: alias,
            user_id: props.user?.id || null
        });
        if (response.data.exists) {
            aliasSmilError.value = response.data.message || 'Cet alias est déjà utilisé';
            aliasSmilValid.value = false;
        } else {
            aliasSmilValid.value = true;
        }
    } catch (error) {
        console.error('Erreur lors de la vérification de l\'alias:', error);
    } finally {
        aliasSmilChecking.value = false;
    }
}, 500);
// Watch pour la vérification de alias_smil
watch(() => form.value.alias_smil, (newValue) => {
    if (!isKycVerified.value) {
        checkAliasSmilUniqueness(newValue);
    }
});
// Prévisualisation des images
const photoPreview = ref(null);
const pieceRectoPreview = ref(null);
const pieceVersoPreview = ref(null);
// Fichiers sélectionnés
const photoFile = ref(null);
const pieceRectoFile = ref(null);
const pieceVersoFile = ref(null);
// Initialiser le formulaire avec les données de l'utilisateur
onMounted(() => {
    if (props.user) {
        form.value = {
            nom: props.user.nom || '',
            prenoms: props.user.prenoms || '',
            tel: props.user.login || '',
            email: props.user.email || '',
            role_id: props.user.current_role || '',
            pays_id: props.user.pays_id || '',
            // Champs read-only
            uuid: props.user.uuid || '',
            full_login: props.user.full_login || '',
            code_owner: props.user.code_owner || '',
            code_parrain: props.user.code_parrain || '',
            // Champs modifiables
            alias_smil: props.user.alias_smil || '',
            type_piece: props.user.type_piece || '',
            numero_piece: props.user.numero_piece || '',
            date_delivrance: formatDateForInput(props.user.date_delivrance),
            date_naissance: formatDateForInput(props.user.date_naissance),
            lieu_delivrance: props.user.lieu_delivrance || '',
            lieu_naissance: props.user.lieu_naissance || '',
            adresse: props.user.adresse || '',
            statut: props.user.statut || '',
            kyc_status: props.user.kyc_status || '',
        };
        // Images existantes
        if (props.user.photoprofile) {
            photoPreview.value = props.user.photoprofile;
        }
        if (props.user.piecerecto) {
            pieceRectoPreview.value = props.user.piecerecto;
        }
        if (props.user.pieceverso) {
            pieceVersoPreview.value = props.user.pieceverso;
        }
    }
});
// Watch pour les changements de props.user
watch(() => props.user, (newUser) => {
    if (newUser) {
        form.value = {
            nom: newUser.nom || '',
            prenoms: newUser.prenoms || '',
            tel: newUser.login || '',
            email: newUser.email || '',
            role_id: newUser.current_role || '',
            pays_id: newUser.pays_id || '',
            // Champs read-only
            uuid: newUser.uuid || '',
            full_login: newUser.full_login || '',
            code_owner: newUser.code_owner || '',
            code_parrain: newUser.code_parrain || '',
            // Champs modifiables
            alias_smil: newUser.alias_smil || '',
            type_piece: newUser.type_piece || '',
            numero_piece: newUser.numero_piece || '',
            date_delivrance: formatDateForInput(newUser.date_delivrance),
            date_naissance: formatDateForInput(newUser.date_naissance),
            lieu_delivrance: newUser.lieu_delivrance || '',
            lieu_naissance: newUser.lieu_naissance || '',
            adresse: newUser.adresse || '',
            statut: newUser.statut || '',
            kyc_status: newUser.kyc_status || '',
        };
    }
}, { deep: true });
// Gérer le changement de photo de profil
function handlePhotoChange(event) {
    const file = event.target.files[0];
    if (file) {
        photoFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}
// Gérer le changement de pièce recto
function handlePieceRectoChange(event) {
    const file = event.target.files[0];
    if (file) {
        pieceRectoFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            pieceRectoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}
// Gérer le changement de pièce verso
function handlePieceVersoChange(event) {
    const file = event.target.files[0];
    if (file) {
        pieceVersoFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            pieceVersoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}
// Obtenir les données du formulaire
function getFormData() {
    const formData = new FormData();
    formData.append('nom', form.value.nom);
    formData.append('prenoms', form.value.prenoms);
    formData.append('tel', form.value.tel);
    formData.append('email', form.value.email || '');
    formData.append('role_id', form.value.role_id);
    if (props.showPaysField) {
        formData.append('pays_id', form.value.pays_id);
    }
    // Nouveaux champs modifiables
    if (!isKycVerified.value) {
        formData.append('alias_smil', form.value.alias_smil || '');
    }
    formData.append('type_piece', form.value.type_piece || '');
    formData.append('numero_piece', form.value.numero_piece || '');
    formData.append('date_delivrance', form.value.date_delivrance || '');
    formData.append('date_naissance', form.value.date_naissance || '');
    formData.append('lieu_delivrance', form.value.lieu_delivrance || '');
    formData.append('lieu_naissance', form.value.lieu_naissance || '');
    formData.append('adresse', form.value.adresse || '');
    // Statut (admin seulement)
    if (props.isAdmin) {
        formData.append('statut', form.value.statut || '');
    }
    // KYC Status (super admin seulement)
    if (props.isSuperAdmin) {
        formData.append('kyc_status', form.value.kyc_status || '');
    }
    if (photoFile.value) {
        formData.append('photoprofile', photoFile.value);
    }
    if (pieceRectoFile.value) {
        formData.append('piecerecto', pieceRectoFile.value);
    }
    if (pieceVersoFile.value) {
        formData.append('pieceverso', pieceVersoFile.value);
    }
    return formData;
}
// Image par défaut
const defaultImage = 'https://caer.univ-amu.fr/wp-content/uploads/default-placeholder.png';
// Image Zoom Modal
const showZoomModal = ref(false);
const zoomImageUrl = ref('');
const zoomImageAlt = ref('');
function openZoomModal(imageUrl, alt) {
    if (imageUrl && imageUrl !== defaultImage) {
        zoomImageUrl.value = imageUrl;
        zoomImageAlt.value = alt;
        showZoomModal.value = true;
    }
}
// Exposer les méthodes
defineExpose({
    getFormData,
    form
});
</script>
<template>
    <div class="user-form">
        <!-- Profile Section avec bannière et avatar -->
        <div class="profile-settings-wrapper">
            <!-- Bannière -->
            <div class="preview-thumb profile-wallpaper">
                <div class="avatar-preview">
                    <div class="profilePicPreview bg_img"></div>
                </div>
            </div>
            <!-- Avatar avec bouton upload -->
            <div class="profile-thumb-content">
                <div class="preview-thumb profile-thumb">
                    <div class="avatar-preview">
                        <div class="profilePicPreview bg_img"
                            :style="{ backgroundImage: `url('${photoPreview || defaultImage}')` }">
                            <i v-if="!photoPreview" class="fas fa-image placeholder-icon"></i>
                        </div>
                    </div>
                    <div v-if="!isReadOnly" class="avatar-edit">
                        <input type="file" class="profilePicUpload" id="profilePicUpload" accept=".png, .jpg, .jpeg"
                            @change="handlePhotoChange">
                        <label for="profilePicUpload">
                            <i class="las la-upload"></i>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <!-- Champ Pays -->
        <div v-if="showPaysField" class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('fields.country') : 'Pays' }}
                        <span class="text-danger">*</span>
                    </label>
                    <StylishSelect v-model="form.pays_id" :options="payss" option-value="id" option-label="libelle"
                        :placeholder="($t ? $t('actions.select') : 'Sélectionner') + ' ' + ($t ? $t('fields.country') : 'Pays')"
                        :disabled="isReadOnly || !!user?.id" :clearable="!isReadOnly && !user?.id" />
                    <div v-if="errors.pays_id" class="text-danger small">{{ errors.pays_id }}</div>
                </div>
            </div>
        </div>
        <!-- Nom et Prénoms -->
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('fields.name') : 'Nom' }}
                        <span class="text-danger">*</span>
                    </label>
                    <input v-model="form.nom" type="text" class="form-control" :class="{ 'is-invalid': errors.nom }"
                        :placeholder="$t ? $t('fields.name') : 'Nom'" :disabled="isReadOnly">
                    <div v-if="errors.nom" class="invalid-feedback">{{ errors.nom }}</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('fields.firstName') : 'Prénoms' }}
                        <span class="text-danger">*</span>
                    </label>
                    <input v-model="form.prenoms" type="text" class="form-control"
                        :class="{ 'is-invalid': errors.prenoms }" :placeholder="$t ? $t('fields.firstName') : 'Prénoms'"
                        :disabled="isReadOnly">
                    <div v-if="errors.prenoms" class="invalid-feedback">{{ errors.prenoms }}</div>
                </div>
            </div>
        </div>
        <!-- Téléphone et Email -->
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('tel') : 'Téléphone' }}
                        <span class="text-danger">*</span>
                    </label>
                    <input v-model="form.tel" type="text" class="form-control" :class="{ 'is-invalid': errors.tel }"
                        :placeholder="$t ? $t('tel') : 'Téléphone'" :disabled="isReadOnly || !!user?.id">
                    <div v-if="errors.tel" class="invalid-feedback">{{ errors.tel }}</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('fields.email') : 'Email' }}
                    </label>
                    <input v-model="form.email" type="email" class="form-control"
                        :class="{ 'is-invalid': errors.email }" :placeholder="$t ? $t('fields.email') : 'Email'"
                        :disabled="isReadOnly">
                    <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                </div>
            </div>
        </div>
        <!-- Rôle et Statut -->
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('fields.role') : 'Rôle' }}
                        <span class="text-danger">*</span>
                    </label>
                    <StylishSelect v-model="form.role_id" :options="roles" option-value="name" option-label="label"
                        :placeholder="($t ? $t('actions.select') : 'Sélectionner') + ' ' + ($t ? $t('fields.role') : 'Rôle')"
                        :disabled="isReadOnly || !!user?.id" :clearable="!isReadOnly && !user?.id" />
                    <div v-if="errors.role_id" class="text-danger small">{{ errors.role_id }}</div>
                </div>
            </div>
            <!-- Statut (Admin seulement) -->
            <div v-if="isAdmin && user?.id" class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('Statut') : 'Statut' }}
                    </label>
                    <StylishSelect v-model="form.statut" :options="statutsOptions" option-value="id"
                        option-label="label"
                        :placeholder="($t ? $t('actions.select') : 'Sélectionner') + ' ' + ($t ? $t('Statut') : 'Statut')"
                        :disabled="isReadOnly" :searchable="false" />
                    <div v-if="errors.statut" class="text-danger small">{{ errors.statut }}</div>
                </div>
            </div>
        </div>
        <!-- Statut KYC (Super Admin seulement) -->
        <div v-if="isSuperAdmin && user?.id" class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('kyc_status') : 'Statut KYC' }}
                    </label>
                    <StylishSelect v-model="form.kyc_status" :options="kycStatutsOptions" option-value="id"
                        option-label="label"
                        :placeholder="($t ? $t('actions.select') : 'Sélectionner') + ' ' + ($t ? $t('kyc_status') : 'Statut KYC')"
                        :disabled="isReadOnly" :searchable="false" />
                    <div v-if="errors.kyc_status" class="text-danger small">{{ errors.kyc_status }}</div>
                </div>
            </div>
        </div>
        <!-- Champs Read-Only (affichés seulement en édition) -->
        <div v-if="user?.id" class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">{{ $t ? $t('uuid') : 'UUID' }}</label>
                    <input v-model="form.uuid" type="text" class="form-control" disabled readonly>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">{{ $t ? $t('full_login') : 'Login complet' }}</label>
                    <input v-model="form.full_login" type="text" class="form-control" disabled readonly>
                </div>
            </div>
        </div>
        <div v-if="user?.id" class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">{{ $t ? $t('code_owner') : 'Code Owner' }}</label>
                    <input v-model="form.code_owner" type="text" class="form-control" disabled readonly>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">{{ $t ? $t('code_parrain') : 'Code Parrain' }}</label>
                    <input v-model="form.code_parrain" type="text" class="form-control" disabled readonly>
                </div>
            </div>
        </div>
        <!-- Alias Smil et Adresse (affichés en création et édition) -->
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('alias_smil') : 'Alias Smil' }}
                        <span v-if="isKycVerified && user?.id" class="text-muted small">(verrouillé - KYC
                            vérifié)</span>
                        <span v-if="aliasSmilChecking" class="text-info small ms-2">
                            <i class="fas fa-spinner fa-spin"></i> {{ $t ? $t('messages.checking') : 'Disponible' }}
                        </span>
                        <span v-if="aliasSmilValid && !aliasSmilChecking && form.alias_smil"
                            class="text-success small ms-2">
                            <i class="fas fa-check"></i> {{ $t ? $t('messages.available') : 'Disponible' }}
                        </span>
                    </label>
                    <input v-model="form.alias_smil" type="text" class="form-control" :class="{
                        'is-invalid': errors.alias_smil || aliasSmilError,
                        'is-valid': aliasSmilValid && !aliasSmilChecking && form.alias_smil
                    }" :placeholder="$t ? $t('alias_smil') : 'Alias Smil'"
                        :disabled="isReadOnly || (isKycVerified && user?.id)">
                    <div v-if="aliasSmilError" class="invalid-feedback d-block">
                        <i class="fas fa-exclamation-triangle"></i> {{ aliasSmilError }}
                    </div>
                    <div v-if="errors.alias_smil" class="invalid-feedback">{{ errors.alias_smil }}</div>
                </div>
            </div>
            <!-- <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">{{ $t ? $t('adresse') : 'Adresse' }}</label>
                    <input
                        v-model="form.adresse"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': errors.adresse }"
                        :placeholder="$t ? $t('adresse') : 'Adresse'"
                        :disabled="isReadOnly"
                    >
                    <div v-if="errors.adresse" class="invalid-feedback">{{ errors.adresse }}</div>
                </div>
            </div> -->
        </div>
        <!-- Informations pièce d'identité -->
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">{{ $t ? $t('type_piece') : 'Type de pièce' }}</label>
                    <StylishSelect v-model="form.type_piece" :options="typePiecesOptions" option-value="id"
                        option-label="label"
                        :placeholder="($t ? $t('actions.select') : 'Sélectionner') + ' ' + ($t ? $t('type_piece') : 'Type de pièce')"
                        :disabled="isReadOnly" :searchable="false" />
                    <div v-if="errors.type_piece" class="text-danger small">{{ errors.type_piece }}</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">{{ $t ? $t('numero_piece') : 'Numéro de pièce' }}</label>
                    <input v-model="form.numero_piece" type="text" class="form-control"
                        :class="{ 'is-invalid': errors.numero_piece }"
                        :placeholder="$t ? $t('numero_piece') : 'Numéro de pièce'" :disabled="isReadOnly">
                    <div v-if="errors.numero_piece" class="invalid-feedback">{{ errors.numero_piece }}</div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">{{ $t ? $t('date_delivrance') : 'Date de délivrance' }}</label>
                    <input v-model="form.date_delivrance" type="date" class="form-control"
                        :class="{ 'is-invalid': errors.date_delivrance }" :disabled="isReadOnly">
                    <div v-if="errors.date_delivrance" class="invalid-feedback">{{ errors.date_delivrance }}</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">{{ $t ? $t('lieu_delivrance') : 'Lieu de délivrance' }}</label>
                    <input v-model="form.lieu_delivrance" type="text" class="form-control"
                        :class="{ 'is-invalid': errors.lieu_delivrance }"
                        :placeholder="$t ? $t('lieu_delivrance') : 'Lieu de délivrance'" :disabled="isReadOnly">
                    <div v-if="errors.lieu_delivrance" class="invalid-feedback">{{ errors.lieu_delivrance }}</div>
                </div>
            </div>
        </div>
        <!-- Informations naissance -->
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">{{ $t ? $t('date_naissance') : 'Date de naissance' }}</label>
                    <input v-model="form.date_naissance" type="date" class="form-control"
                        :class="{ 'is-invalid': errors.date_naissance }" :disabled="isReadOnly">
                    <div v-if="errors.date_naissance" class="invalid-feedback">{{ errors.date_naissance }}</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label">{{ $t ? $t('lieu_naissance') : 'Lieu de naissance' }}</label>
                    <input v-model="form.lieu_naissance" type="text" class="form-control"
                        :class="{ 'is-invalid': errors.lieu_naissance }"
                        :placeholder="$t ? $t('lieu_naissance') : 'Lieu de naissance'" :disabled="isReadOnly">
                    <div v-if="errors.lieu_naissance" class="invalid-feedback">{{ errors.lieu_naissance }}</div>
                </div>
            </div>
        </div>
        <!-- Pièces d'identité -->
        <div class="row">
            <!-- Pièce Recto -->
            <div class="col-6">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('piecerecto_id') : 'Pièce recto' }}
                    </label>
                    <div class="image-upload-wrapper">
                        <div class="image-preview position-relative">
                            <img :src="pieceRectoPreview || defaultImage" alt="Pièce recto" class="preview-img"
                                :class="{ 'cursor-pointer': pieceRectoPreview }"
                                @click="openZoomModal(pieceRectoPreview, 'Pièce recto')">
                            <button v-if="pieceRectoPreview && pieceRectoPreview !== defaultImage"
                                type="button" class="btn btn-sm btn-info zoom-btn"
                                @click="openZoomModal(pieceRectoPreview, 'Pièce recto')"
                                title="Agrandir l'image">
                                <i class="fa fa-search-plus"></i>
                            </button>
                        </div>
                        <input v-if="!isReadOnly" type="file" id="piecerecto" class="form-control mt-2"
                            accept=".png, .jpg, .jpeg" @change="handlePieceRectoChange">
                    </div>
                </div>
            </div>
            <!-- Pièce Verso -->
            <div class="col-6">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('pieceverso_id') : 'Pièce verso' }}
                    </label>
                    <div class="image-upload-wrapper">
                        <div class="image-preview position-relative">
                            <img :src="pieceVersoPreview || defaultImage" alt="Pièce verso" class="preview-img"
                                :class="{ 'cursor-pointer': pieceVersoPreview }"
                                @click="openZoomModal(pieceVersoPreview, 'Pièce verso')">
                            <button v-if="pieceVersoPreview && pieceVersoPreview !== defaultImage"
                                type="button" class="btn btn-sm btn-info zoom-btn"
                                @click="openZoomModal(pieceVersoPreview, 'Pièce verso')"
                                title="Agrandir l'image">
                                <i class="fa fa-search-plus"></i>
                            </button>
                        </div>
                        <input v-if="!isReadOnly" type="file" id="pieceverso" class="form-control mt-2"
                            accept=".png, .jpg, .jpeg" @change="handlePieceVersoChange">
                    </div>
                </div>
            </div>
        </div>
        <!-- Image Zoom Modal -->
        <ImageZoomModal
            :show="showZoomModal"
            :image-url="zoomImageUrl"
            :alt="zoomImageAlt"
            @close="showZoomModal = false"
        />
    </div>
</template>
<style scoped>
.cursor-pointer {
    cursor: pointer;
}
.zoom-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}
.preview-img:hover {
    opacity: 0.9;
}
</style>
