<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import { useLocale } from '@/Composables/useLocale';
import StylishSelect from '@/Components/Common/StylishSelect.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    title: String,
    user: Object,
    roles: Array,
    payss: Array,
});

const page = usePage();
const { t } = useLocale();

const form = ref({
    nom: '',
    prenoms: '',
    email: '',
    login: '',
    alias_smil: '',
});

const photoPreview = ref(null);
const photoFile = ref(null);
const isSubmitting = ref(false);
const errors = ref({});
const activeTab = ref('info');

const isKycVerified = computed(() => props.user?.kyc_status === 'verifie');

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
        } else {
            aliasSmilValid.value = true;
        }
    } catch (error) {
        console.error('Erreur:', error);
    } finally {
        aliasSmilChecking.value = false;
    }
}, 500);

watch(() => form.value.alias_smil, (newValue) => {
    if (!isKycVerified.value) checkAliasSmilUniqueness(newValue);
});

onMounted(() => {
    if (props.user) {
        form.value = {
            nom: props.user.nom || '',
            prenoms: props.user.prenoms || '',
            email: props.user.email || '',
            login: props.user.login || '',
            alias_smil: props.user.alias_smil || '',
        };
        if (props.user.photoprofile) {
            photoPreview.value = props.user.photoprofile;
        }
    }
});

const defaultImage = 'https://ui-avatars.com/api/?name=' + encodeURIComponent((props.user?.prenoms || '') + '+' + (props.user?.nom || '')) + '&size=200&background=0B5697&color=fff&bold=true&font-size=0.4';

function handlePhotoChange(event) {
    const file = event.target.files[0];
    if (file) {
        photoFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => { photoPreview.value = e.target.result; };
        reader.readAsDataURL(file);
    }
}

function submitForm() {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('nom', form.value.nom);
    formData.append('prenoms', form.value.prenoms);
    formData.append('email', form.value.email);
    formData.append('login', form.value.login);
    formData.append('alias_smil', form.value.alias_smil);
    if (photoFile.value) formData.append('photoprofile', photoFile.value);

    router.post(route('administration.users.updateprofile', props.user.id), formData, {
        forceFormData: true,
        preserveScroll: true,
        onError: (errs) => { errors.value = errs; isSubmitting.value = false; },
        onSuccess: () => { isSubmitting.value = false; },
        onFinish: () => { isSubmitting.value = false; }
    });
}

const userInitials = computed(() => {
    const f = (props.user?.prenoms || '').charAt(0);
    const l = (props.user?.nom || '').charAt(0);
    return (f + l).toUpperCase();
});

const roleBadge = computed(() => {
    const role = props.user?.current_role || '';
    const map = {
        'super_admin': { class: 'role-admin', icon: 'fa-crown' },
        'directeur_general': { class: 'role-director', icon: 'fa-building' },
        'enseignant': { class: 'role-teacher', icon: 'fa-chalkboard-teacher' },
        'apprenant': { class: 'role-student', icon: 'fa-graduation-cap' },
    };
    return map[role] || { class: 'role-default', icon: 'fa-user' };
});
</script>

<template>
    <Head :title="title" />
    <div class="body-wrapper">
        <div class="profile-page">
            <!-- PROFILE HEADER -->
            <div class="profile-header-card">
                <div class="profile-header-bg"></div>
                <div class="profile-header-content">
                    <div class="avatar-wrapper">
                        <div class="avatar-img" :style="{ backgroundImage: `url('${photoPreview || defaultImage}')` }">
                            <div v-if="!photoPreview && !props.user?.photoprofile" class="avatar-initials">{{ userInitials }}</div>
                        </div>
                        <label for="photoUpload" class="avatar-edit-btn" title="Changer la photo">
                            <i class="fa fa-camera"></i>
                        </label>
                        <input type="file" id="photoUpload" accept=".png,.jpg,.jpeg" @change="handlePhotoChange" style="display:none;" />
                    </div>
                    <div class="profile-info">
                        <h2 class="profile-fullname">{{ user.prenoms }} {{ user.nom }}</h2>
                        <div class="profile-role" :class="roleBadge.class">
                            <i class="fa" :class="roleBadge.icon"></i>
                            {{ user.current_role || 'Utilisateur' }}
                        </div>
                        <div class="profile-meta">
                            <span v-if="user.email"><i class="fa fa-envelope"></i> {{ user.email }}</span>
                            <span v-if="user.login"><i class="fa fa-phone"></i> {{ user.login }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <AlertMessage />

            <!-- TABS -->
            <div class="profile-tabs">
                <button class="profile-tab" :class="{ active: activeTab === 'info' }" @click="activeTab = 'info'">
                    <i class="fa fa-user me-1"></i> Informations
                </button>
                <button class="profile-tab" :class="{ active: activeTab === 'security' }" @click="activeTab = 'security'">
                    <i class="fa fa-shield-alt me-1"></i> Sécurité
                </button>
            </div>

            <!-- TAB: Informations -->
            <div v-show="activeTab === 'info'" class="profile-card">
                <div class="card-section">
                    <h6 class="section-title"><i class="fa fa-id-card me-2"></i> Identité</h6>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Nom</label>
                            <div class="input-icon-wrapper">
                                <i class="fa fa-user input-icon"></i>
                                <input v-model="form.nom" type="text" class="form-control form-control-sm profile-input" :class="{ 'is-invalid': errors.nom }" placeholder="Nom" />
                            </div>
                            <div v-if="errors.nom" class="text-danger small mt-1">{{ errors.nom }}</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Prénom(s)</label>
                            <div class="input-icon-wrapper">
                                <i class="fa fa-user input-icon"></i>
                                <input v-model="form.prenoms" type="text" class="form-control form-control-sm profile-input" :class="{ 'is-invalid': errors.prenoms }" placeholder="Prénom(s)" />
                            </div>
                            <div v-if="errors.prenoms" class="text-danger small mt-1">{{ errors.prenoms }}</div>
                        </div>
                    </div>
                </div>

                <div class="card-section">
                    <h6 class="section-title"><i class="fa fa-address-book me-2"></i> Contact</h6>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Email</label>
                            <div class="input-icon-wrapper">
                                <i class="fa fa-envelope input-icon"></i>
                                <input v-model="form.email" type="email" class="form-control form-control-sm profile-input" :class="{ 'is-invalid': errors.email }" placeholder="Email" />
                            </div>
                            <div v-if="errors.email" class="text-danger small mt-1">{{ errors.email }}</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Téléphone</label>
                            <div class="input-icon-wrapper">
                                <i class="fa fa-phone input-icon"></i>
                                <input v-model="form.login" type="text" class="form-control form-control-sm profile-input" :class="{ 'is-invalid': errors.login }" placeholder="Téléphone" />
                            </div>
                            <div v-if="errors.login" class="text-danger small mt-1">{{ errors.login }}</div>
                        </div>
                    </div>
                </div>

                <div class="card-section">
                    <h6 class="section-title"><i class="fa fa-cog me-2"></i> Compte</h6>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">
                                Alias SIKUL
                                <span v-if="isKycVerified" class="badge bg-success ms-1" style="font-size:9px;">KYC Vérifié</span>
                                <span v-if="aliasSmilChecking" class="text-info small ms-2"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-if="aliasSmilValid && !aliasSmilChecking && form.alias_smil" class="text-success small ms-2"><i class="fa fa-check-circle"></i></span>
                            </label>
                            <div class="input-icon-wrapper">
                                <i class="fa fa-at input-icon"></i>
                                <input v-model="form.alias_smil" type="text" class="form-control form-control-sm profile-input"
                                    :class="{ 'is-invalid': errors.alias_smil || aliasSmilError, 'is-valid': aliasSmilValid && !aliasSmilChecking && form.alias_smil }"
                                    placeholder="Alias unique" :disabled="isKycVerified" />
                            </div>
                            <div v-if="aliasSmilError" class="text-danger small mt-1"><i class="fa fa-exclamation-triangle"></i> {{ aliasSmilError }}</div>
                            <div v-if="errors.alias_smil" class="text-danger small mt-1">{{ errors.alias_smil }}</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Rôle</label>
                            <div class="input-icon-wrapper">
                                <i class="fa fa-user-tag input-icon"></i>
                                <StylishSelect :model-value="user.current_role" :options="roles" option-value="name" option-label="name" :disabled="true" :clearable="false" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="profile-actions">
                    <Link :href="route('home')" class="btn btn-outline-secondary">
                        <i class="fa fa-times me-1"></i> Annuler
                    </Link>
                    <button type="button" class="btn btn-save" :disabled="isSubmitting" @click="submitForm">
                        <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="fa fa-save me-1"></i>
                        {{ isSubmitting ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                    </button>
                </div>
            </div>

            <!-- TAB: Sécurité -->
            <div v-show="activeTab === 'security'" class="profile-card">
                <div class="card-section">
                    <h6 class="section-title"><i class="fa fa-lock me-2"></i> Informations de connexion</h6>
                    <div class="security-info">
                        <div class="security-item">
                            <i class="fa fa-calendar-check"></i>
                            <div>
                                <strong>Compte créé le</strong>
                                <span>{{ user.created_at || '-' }}</span>
                            </div>
                        </div>
                        <div class="security-item">
                            <i class="fa fa-sign-in-alt"></i>
                            <div>
                                <strong>Dernière connexion</strong>
                                <span>{{ user.last_login_at || '-' }}</span>
                            </div>
                        </div>
                        <div class="security-item">
                            <i class="fa fa-fingerprint"></i>
                            <div>
                                <strong>Statut KYC</strong>
                                <span class="badge" :class="isKycVerified ? 'bg-success' : 'bg-warning text-dark'">
                                    {{ isKycVerified ? 'Vérifié' : 'Non vérifié' }}
                                </span>
                            </div>
                        </div>
                        <div class="security-item">
                            <i class="fa fa-user-shield"></i>
                            <div>
                                <strong>Rôle actif</strong>
                                <span>{{ user.current_role || '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.profile-page { width: 100%; }

/* PROFILE HEADER CARD */
.profile-header-card {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 20px;
    background: white;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

.profile-header-bg {
    height: 120px;
    background: linear-gradient(135deg, #0c1e36 0%, #0B5697 50%, #0FBCAF 100%);
}

.profile-header-content {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 0 30px 24px;
    margin-top: -50px;
    position: relative;
    z-index: 2;
}

/* AVATAR */
.avatar-wrapper { position: relative; flex-shrink: 0; }

.avatar-img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-size: cover;
    background-position: center;
    border: 4px solid white;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #0B5697;
}

.avatar-initials {
    font-size: 32px;
    font-weight: 900;
    color: white;
    letter-spacing: 2px;
}

.avatar-edit-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #E5590C;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: 3px solid white;
    box-shadow: 0 3px 10px rgba(229, 89, 12, 0.4);
    transition: all 0.3s;
    font-size: 13px;
    z-index: 10;
}

.avatar-edit-btn:hover {
    transform: scale(1.15);
    box-shadow: 0 5px 18px rgba(229, 89, 12, 0.6);
    background: #ff6b1a;
}

/* PROFILE INFO */
.profile-info { padding-top: 55px; }

.profile-fullname {
    font-size: 22px;
    font-weight: 800;
    color: #0c1e36;
    margin-bottom: 4px;
}

.profile-role {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: capitalize;
    margin-bottom: 6px;
}

.role-admin { background: #E5590C; color: white; }
.role-director { background: #0FBCAF; color: white; }
.role-teacher { background: #0B5697; color: white; }
.role-student { background: #0c1e36; color: white; }
.role-default { background: #6c757d; color: white; }

.profile-meta {
    display: flex;
    gap: 20px;
    font-size: 12px;
    color: #64748b;
}

.profile-meta span { display: flex; align-items: center; gap: 5px; }
.profile-meta i { color: #E5590C; }

/* TABS */
.profile-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 15px;
    padding: 0;
}

.profile-tab {
    padding: 10px 20px;
    border: none;
    background: white;
    border-radius: 10px 10px 0 0;
    font-weight: 600;
    color: #777;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 13px;
}

.profile-tab.active {
    color: #0B5697;
    background: white;
    box-shadow: 0 -4px 10px rgba(11, 86, 151, 0.1);
    border-bottom: 3px solid #0FBCAF;
}

.profile-tab:hover:not(.active) { color: #0B5697; background: #f8fbff; }

/* CARD */
.profile-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.card-section {
    margin-bottom: 25px;
}

.card-section:last-child { margin-bottom: 0; }

.section-title {
    font-weight: 700;
    color: #0c1e36;
    font-size: 14px;
    border-bottom: 2px solid #E5590C;
    padding-bottom: 8px;
    margin-bottom: 15px;
}

/* INPUTS */
.input-icon-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #E5590C;
    font-size: 13px;
    z-index: 1;
}

.profile-input {
    padding-left: 38px !important;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    height: 42px;
    font-size: 14px;
    transition: all 0.3s;
}

.profile-input:focus {
    border-color: #E5590C;
    box-shadow: 0 0 0 4px rgba(229, 89, 12, 0.08);
}

/* ACTIONS */
.profile-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
}

.btn-save {
    background: #E5590C;
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 10px;
    font-weight: 700;
    transition: all 0.3s;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(229, 89, 12, 0.35);
    color: white;
    background: #ff6b1a;
}

.btn-save:disabled { opacity: 0.6; transform: none; }

/* SECURITY */
.security-info { display: flex; flex-direction: column; gap: 12px; }

.security-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 16px;
    background: #fafafa;
    border-radius: 10px;
    border-left: 3px solid #E5590C;
}

.security-item i {
    font-size: 18px;
    color: #E5590C;
    width: 24px;
}

.security-item strong {
    display: block;
    font-size: 12px;
    color: #555;
}

.security-item span {
    font-size: 14px;
    color: #1a1a1a;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .profile-cover { height: 180px; }
    .profile-avatar-section {
        flex-direction: column;
        align-items: center;
        left: 50%;
        transform: translateX(-50%);
        bottom: -60px;
    }
    .profile-name-section { text-align: center; }
    .profile-tabs { margin-top: 70px; }
    .avatar-img { width: 100px; height: 100px; }
    .profile-fullname { font-size: 18px; }
    .profile-card { padding: 20px; }
    .profile-actions { flex-direction: column; }
}
</style>
