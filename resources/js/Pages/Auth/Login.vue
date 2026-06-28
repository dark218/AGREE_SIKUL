<script setup>
import { ref, computed, nextTick, onMounted } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { initializeApp } from 'firebase/app';
import { getMessaging, getToken } from 'firebase/messaging';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const props = defineProps({
    pays: {
        type: Array,
        default: () => []
    },
    errors: {
        type: Object,
        default: () => ({})
    },
    firebaseConfig: {
        type: Object,
        default: () => ({})
    }
});

const page = usePage();
const { t } = useI18n();

const phoneInput = ref(null);
const passwordInput = ref(null);

const currentStep = ref(0); // 0 = portal, 1 = formulaire complet (pays + tel + mot de passe)
const showPassword = ref(false);
const localError = ref(null);
const fcmToken = ref(null);
const fcmInitialized = ref(false);

// Portails disponibles
const portals = [
    {
        key: 'administration',
        label: 'Administration',
        subtitle: 'Direction & gestion de l\'établissement',
        icon: 'bx bxs-bank',
        color: '#0B5697',
        gradient: 'linear-gradient(135deg, #0B5697, #1a6db8)',
        roles: ['super_admin', 'directeur_general', 'directeur_campus', 'directeur_ecole'],
    },
    {
        key: 'enseignant',
        label: 'Enseignant',
        subtitle: 'Cours, notes & suivi pédagogique',
        icon: 'bx bxs-chalkboard',
        color: '#0FBCAF',
        gradient: 'linear-gradient(135deg, #0FBCAF, #14d4c5)',
        roles: ['enseignant'],
    },
    {
        key: 'eleve',
        label: 'Élève',
        subtitle: 'Espace apprenant & résultats',
        icon: 'bx bxs-graduation',
        color: '#8B5CF6',
        gradient: 'linear-gradient(135deg, #8B5CF6, #a78bfa)',
        roles: ['eleve'],
    },
    {
        key: 'parent',
        label: 'Parent / Tuteur',
        subtitle: 'Suivi de votre enfant',
        icon: 'bx bxs-group',
        color: '#E5590C',
        gradient: 'linear-gradient(135deg, #E5590C, #f97316)',
        roles: ['parent'],
    },
    {
        key: 'personnel',
        label: 'Personnel',
        subtitle: 'Administratif, bibliothèque, infirmerie',
        icon: 'bx bxs-id-card',
        color: '#10B981',
        gradient: 'linear-gradient(135deg, #10B981, #34d399)',
        roles: ['personnel_administratif', 'bibliothecaire', 'infirmier', 'agent_securite'],
    },
    {
        key: 'caisse',
        label: 'Caisse / POS',
        subtitle: 'Point de vente & encaissement',
        icon: 'bx bxs-calculator',
        color: '#f59e0b',
        gradient: 'linear-gradient(135deg, #f59e0b, #fbbf24)',
        roles: ['Caissier', 'Manager'],
    },
];

const selectedPortal = ref(null);

const form = useForm({
    country_code: '',
    full_login: '',
    password: '',
    fcm_token: null,
    portal: '',
});

const defaultCountries = [
    { code: '+225', name: 'Côte d\'Ivoire', length: 10 },
    { code: '+33', name: 'France', length: 9 },
    { code: '+1', name: 'USA', length: 10 },
    { code: '+32', name: 'Belgique', length: 9 },
    { code: '+41', name: 'Suisse', length: 9 },
    { code: '+237', name: 'Cameroun', length: 9 },
    { code: '+221', name: 'Sénégal', length: 9 },
];

const countries = computed(() => {
    if (props.pays && props.pays.length > 0) {
        return props.pays.map(country => ({
            code: country.code,
            name: country.libelle || '',
            label: (country.libelle || '') + ' (' + country.code + ')',
            length: country.length || 10
        }));
    }
    return defaultCountries.map(c => ({
        ...c,
        label: c.name + ' (' + c.code + ')',
    }));
});

const selectedCountry = computed(() =>
    countries.value.find(c => c.code === form.country_code)
);

const hasError = computed(() => {
    return localError.value || form.errors.full_login || form.errors.password || form.errors.country_code || form.errors.portal;
});

const errorMessage = computed(() => {
    if (localError.value) return localError.value;
    if (form.errors.portal) return form.errors.portal;
    if (form.errors.full_login) return form.errors.full_login;
    if (form.errors.password) return form.errors.password;
    if (form.errors.country_code) return form.errors.country_code;
    return null;
});

const flashError = computed(() => page.props.flash?.error || null);
const flashSuccess = computed(() => page.props.flash?.success || null);

function selectPortal(portal) {
    selectedPortal.value = portal;
    form.portal = portal.key;
    localError.value = null;
    form.clearErrors();
    currentStep.value = 1;
    nextTick(() => {
        // Focus is handled by the SearchableSelect
    });
}

function goToPortals() {
    currentStep.value = 0;
    localError.value = null;
    form.clearErrors();
    form.reset();
    selectedPortal.value = null;
}

function handleSubmit() {
    if (!form.country_code.trim()) {
        localError.value = 'Veuillez sélectionner un pays';
        return;
    }
    if (!form.full_login.trim()) {
        localError.value = 'Veuillez renseigner votre numéro de téléphone';
        return;
    }
    if (!form.password.trim()) {
        localError.value = 'Veuillez renseigner votre mot de passe';
        return;
    }

    localError.value = null;

    form.post(route('login'), {
        onError: () => {
            // Les erreurs sont automatiquement gérées par Inertia
        },
        onFinish: () => {
            form.reset('password');
        }
    });
}

function clearLocalError() {
    localError.value = null;
}

async function initFCM() {
    try {
        if (!props.firebaseConfig?.apiKey) {
            fcmInitialized.value = true;
            return;
        }

        if (!('Notification' in window)) {
            fcmInitialized.value = true;
            return;
        }

        if (!('serviceWorker' in navigator)) {
            fcmInitialized.value = true;
            return;
        }

        const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');

        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            fcmInitialized.value = true;
            return;
        }

        const app = initializeApp({
            apiKey: props.firebaseConfig.apiKey,
            authDomain: props.firebaseConfig.authDomain,
            projectId: props.firebaseConfig.projectId,
            messagingSenderId: props.firebaseConfig.messagingSenderId,
            appId: props.firebaseConfig.appId,
        });

        const messaging = getMessaging(app);

        const token = await getToken(messaging, {
            vapidKey: props.firebaseConfig.vapidKey,
            serviceWorkerRegistration: registration
        });

        if (token) {
            fcmToken.value = token;
            form.fcm_token = token;
        }
    } catch (error) {
        console.error('Erreur FCM:', error);
    } finally {
        fcmInitialized.value = true;
    }
}

onMounted(() => {
    initFCM();
});
</script>

<template>
    <Head title="Connexion" />

    <div class="login-container">
        <!-- Floating particles decoration -->
        <div class="particles">
            <div class="particle p1"></div>
            <div class="particle p2"></div>
            <div class="particle p3"></div>
            <div class="particle p4"></div>
            <div class="particle p5"></div>
        </div>

        <!-- CENTER: LOGIN AREA -->
        <div class="login-form-section">

            <!-- =============================== -->
            <!-- STEP 0: PORTAL SELECTION         -->
            <!-- =============================== -->
            <transition name="card-switch" mode="out-in">
                <div v-if="currentStep === 0" key="portal" class="portal-wrapper">
                    <!-- Logo & Header -->
                    <div class="portal-header">
                        <img :src="page.props.appConfig?.logo_login || page.props.appConfig?.logo || '/images/image.png'" alt="AGREE SIKUL" class="portal-logo" />
                        <h1>Bienvenue sur <span class="brand-highlight">AGREE SIKUL</span></h1>
                        <p>Choisissez votre espace pour vous connecter</p>
                    </div>

                    <!-- Portal Cards Grid -->
                    <div class="portal-grid">
                        <button
                            v-for="portal in portals"
                            :key="portal.key"
                            class="portal-card"
                            @click="selectPortal(portal)"
                        >
                            <div class="portal-icon-wrap" :style="{ background: portal.gradient }">
                                <i :class="portal.icon"></i>
                            </div>
                            <div class="portal-info">
                                <h3>{{ portal.label }}</h3>
                                <p>{{ portal.subtitle }}</p>
                            </div>
                            <i class="bx bx-chevron-right portal-arrow"></i>
                        </button>
                    </div>

                    <div class="portal-footer">
                        <p>&copy; 2026 AGREE SIKUL &mdash; Plateforme de gestion scolaire</p>
                    </div>
                </div>

                <!-- =============================== -->
                <!-- STEP 1 & 2: LOGIN FORM           -->
                <!-- =============================== -->
                <div v-else key="form" class="form-card">
                    <!-- Colored top bar matching portal -->
                    <div class="form-card-bar" :style="{ background: selectedPortal?.gradient || 'linear-gradient(90deg, #0B5697, #0FBCAF, #E5590C)' }"></div>

                    <!-- Back to portals -->
                    <button type="button" class="btn-portal-back" @click="goToPortals">
                        <i class="bx bx-grid-alt"></i> Changer de portail
                    </button>

                    <!-- Portal badge -->
                    <div class="portal-badge" v-if="selectedPortal">
                        <div class="portal-badge-icon" :style="{ background: selectedPortal.gradient }">
                            <i :class="selectedPortal.icon"></i>
                        </div>
                        <div>
                            <span class="portal-badge-label">Portail {{ selectedPortal.label }}</span>
                        </div>
                    </div>

                    <!-- Logo -->
                    <div class="card-brand">
                        <img :src="page.props.appConfig?.logo_login || page.props.appConfig?.logo || '/images/image.png'" alt="AGREE SIKUL" class="login-logo" />
                    </div>

                    <div class="form-header">
                        <h2>Connexion</h2>
                        <p>Entrez vos identifiants pour accéder à votre espace</p>
                    </div>

                    <!-- Error Messages -->
                    <div v-if="flashError" class="alert alert-error">
                        <i class="bx bx-error-circle"></i> {{ flashError }}
                    </div>
                    <div v-if="flashSuccess" class="alert alert-success">
                        <i class="bx bx-check-circle"></i> {{ flashSuccess }}
                    </div>
                    <transition name="fade">
                        <div v-if="hasError && !flashError" class="alert alert-error">
                            <i class="bx bx-error-circle"></i> {{ errorMessage }}
                        </div>
                    </transition>

                    <form @submit.prevent="handleSubmit" class="login-form">
                        <div class="form-step">
                            <!-- Pays -->
                            <div class="form-group">
                                <label><i class="bx bx-globe me-1"></i> Pays</label>
                                <SearchableSelect
                                    v-model="form.country_code"
                                    :options="countries"
                                    optionValue="code"
                                    optionLabel="label"
                                    placeholder="-- Sélectionner un pays --"
                                    searchPlaceholder="Rechercher un pays..."
                                    @update:model-value="clearLocalError"
                                />
                            </div>

                            <!-- Téléphone -->
                            <div class="form-group">
                                <label><i class="bx bx-phone me-1"></i> Numéro de téléphone</label>
                                <input
                                    ref="phoneInput"
                                    v-model="form.full_login"
                                    class="form-input"
                                    type="tel"
                                    placeholder="Ex: 0700000001"
                                    :maxlength="selectedCountry?.length || 15"
                                    @input="clearLocalError"
                                />
                                <small class="form-helper">Numéro sans l'indicatif pays</small>
                            </div>

                            <!-- Mot de passe -->
                            <div class="form-group">
                                <label><i class="bx bx-lock-alt me-1"></i> Mot de passe</label>
                                <div class="password-wrapper">
                                    <input
                                        ref="passwordInput"
                                        v-model="form.password"
                                        class="form-input"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Entrez votre mot de passe"
                                        @input="clearLocalError"
                                    />
                                    <button
                                        type="button"
                                        class="btn-toggle-password"
                                        @click.prevent="showPassword = !showPassword"
                                    >
                                        <i :class="showPassword ? 'bx bx-hide' : 'bx bx-show'"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-footer">
                                <Link :href="route('password.request')" class="link-forgot">
                                    Mot de passe oublié ?
                                </Link>
                            </div>

                            <button
                                type="submit"
                                class="btn-login"
                                :style="{ background: selectedPortal?.gradient }"
                                :disabled="form.processing"
                            >
                                <i v-if="form.processing" class="bx bx-loader-alt spin"></i>
                                <span>{{ form.processing ? 'Connexion...' : 'Se connecter' }}</span>
                                <i v-if="!form.processing" class="bx bx-log-in"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Footer card -->
                    <div class="card-footer-text">
                        <p>&copy; 2026 AGREE SIKUL &mdash; Plateforme de gestion scolaire</p>
                    </div>
                </div>
            </transition>

        </div>
    </div>
</template>

<style scoped>
/* === VARIABLES === */
:root {
    --primary: #0B5697;
    --primary-light: #1a6db8;
    --secondary: #E5590C;
    --accent: #0FBCAF;
    --white: #ffffff;
    --text-dark: #1e293b;
    --text-light: #64748b;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

/* === FULL SCREEN BG === */
.login-container {
    min-height: 100vh;
    width: 100%;
    background: url('https://images.unsplash.com/photo-1552664730-d307ca884978?w=1920&h=1080&fit=crop&q=80') center/cover no-repeat fixed;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

.login-container::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(11,86,151,0.82) 0%, rgba(15,188,175,0.55) 50%, rgba(229,89,12,0.35) 100%);
    z-index: 0;
}

/* === FLOATING PARTICLES === */
.particles { position: absolute; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
.particle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
    animation: float-particle 20s infinite ease-in-out;
}
.p1 { width: 300px; height: 300px; top: -80px; left: -80px; animation-delay: 0s; }
.p2 { width: 200px; height: 200px; top: 60%; right: -60px; animation-delay: -5s; }
.p3 { width: 150px; height: 150px; bottom: -40px; left: 30%; animation-delay: -10s; }
.p4 { width: 100px; height: 100px; top: 20%; right: 20%; animation-delay: -8s; }
.p5 { width: 250px; height: 250px; bottom: 10%; right: 10%; animation-delay: -3s; }

@keyframes float-particle {
    0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.08; }
    25% { transform: translate(30px, -40px) scale(1.1); opacity: 0.12; }
    50% { transform: translate(-20px, 30px) scale(0.95); opacity: 0.06; }
    75% { transform: translate(40px, 20px) scale(1.05); opacity: 0.1; }
}

/* === FORM SECTION === */
.login-form-section {
    position: relative;
    z-index: 1;
    width: 100%;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 30px;
}

/* ================================================ */
/*  STEP 0 — PORTAL SELECTION                       */
/* ================================================ */
.portal-wrapper {
    width: 100%;
    max-width: 720px;
    animation: cardAppear 0.6s cubic-bezier(0.23, 1, 0.32, 1);
}

.portal-header {
    text-align: center;
    margin-bottom: 36px;
}

.portal-logo {
    max-width: 160px;
    height: auto;
    margin: 0 auto 18px;
    display: block;
    filter: drop-shadow(0 4px 12px rgba(0,0,0,0.2));
}

.portal-header h1 {
    font-size: 28px;
    font-weight: 800;
    color: white;
    text-shadow: 0 2px 8px rgba(0,0,0,0.25);
    margin-bottom: 6px;
}

.brand-highlight {
    background: linear-gradient(135deg, #0FBCAF, #fff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.portal-header p {
    color: rgba(255,255,255,0.85);
    font-size: 15px;
}

/* === PORTAL GRID === */
.portal-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.portal-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(15px);
    border-radius: 16px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.35s cubic-bezier(0.23, 1, 0.32, 1);
    text-align: left;
    position: relative;
    overflow: hidden;
}

.portal-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 16px;
    opacity: 0;
    transition: opacity 0.35s;
    background: linear-gradient(135deg, rgba(11,86,151,0.03), rgba(15,188,175,0.05));
}

.portal-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 48px rgba(0,0,0,0.2);
    border-color: rgba(15,188,175,0.3);
}

.portal-card:hover::before { opacity: 1; }

.portal-card:hover .portal-arrow {
    transform: translateX(4px);
    color: #0FBCAF;
}

.portal-icon-wrap {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.portal-icon-wrap i {
    font-size: 24px;
    color: white;
}

.portal-info {
    flex: 1;
    min-width: 0;
}

.portal-info h3 {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 2px;
}

.portal-info p {
    font-size: 12px;
    color: #94a3b8;
    line-height: 1.3;
}

.portal-arrow {
    font-size: 22px;
    color: #cbd5e1;
    transition: all 0.3s;
    flex-shrink: 0;
}

.portal-footer {
    text-align: center;
    margin-top: 30px;
}

.portal-footer p {
    color: rgba(255,255,255,0.55);
    font-size: 12px;
}

/* ================================================ */
/*  STEP 1 & 2 — LOGIN FORM CARD                    */
/* ================================================ */
.form-card {
    width: 100%;
    max-width: 460px;
    background: rgba(255, 255, 255, 0.97);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border-radius: 24px;
    padding: 50px 40px 35px;
    box-shadow:
        0 25px 80px rgba(0, 0, 0, 0.3),
        0 0 0 1px rgba(255, 255, 255, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
    animation: cardAppear 0.6s cubic-bezier(0.23, 1, 0.32, 1);
    position: relative;
    overflow: hidden;
}

.form-card-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
}

@keyframes cardAppear {
    from { transform: translateY(30px) scale(0.95); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}

/* === BACK TO PORTALS === */
.btn-portal-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1f5f9;
    border: none;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    padding: 8px 14px;
    border-radius: 10px;
    transition: all 0.3s;
    margin-bottom: 16px;
}

.btn-portal-back:hover {
    background: #e2e8f0;
    color: #0B5697;
}

.btn-portal-back i {
    font-size: 16px;
}

/* === PORTAL BADGE === */
.portal-badge {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding: 12px 16px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.portal-badge-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.portal-badge-icon i {
    font-size: 18px;
    color: white;
}

.portal-badge-label {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
}

/* === BRAND IN CARD === */
.card-brand {
    text-align: center;
    margin-bottom: 16px;
}

.login-logo {
    max-width: 140px;
    height: auto;
    margin: 0 auto;
    display: block;
}

/* === HEADER === */
.form-header {
    text-align: center;
    margin-bottom: 24px;
}

.form-header h2 {
    font-size: 24px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 4px;
}

.form-header p {
    color: #64748b;
    font-size: 13px;
}

/* === FORM GROUPS === */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.form-group label i {
    color: #0FBCAF;
}

.form-input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 15px;
    color: #1e293b;
    background: #f9fafb;
    transition: all 0.3s;
    outline: none;
}

.form-input:focus {
    border-color: #0FBCAF;
    background: white;
    box-shadow: 0 0 0 4px rgba(15, 188, 175, 0.12);
}

.form-input::placeholder { color: #9ca3af; }

.form-helper {
    display: block;
    font-size: 11px;
    color: #9ca3af;
    margin-top: 5px;
}

/* === PASSWORD === */
.password-wrapper {
    position: relative;
}

.password-wrapper .form-input {
    padding-right: 50px;
}

.btn-toggle-password {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #9ca3af;
    font-size: 20px;
    transition: color 0.3s;
}

.btn-toggle-password:hover { color: #0B5697; }

/* === LOGIN BUTTON === */
.btn-login {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #0B5697, #0FBCAF);
    color: white;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
    position: relative;
    overflow: hidden;
    margin-top: 8px;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(11, 86, 151, 0.4);
    filter: brightness(1.08);
}

.btn-login span,
.btn-login i { position: relative; z-index: 1; }

.btn-login:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none;
}

/* === BACK BUTTON === */
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: none;
    border: none;
    color: #0B5697;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 20px;
    padding: 6px 0;
    transition: all 0.3s;
}

.btn-back:hover {
    color: #0FBCAF;
    transform: translateX(-4px);
}

/* === FORGOT PASSWORD === */
.form-footer {
    text-align: right;
    margin-bottom: 12px;
}

.link-forgot {
    color: #0B5697;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: color 0.3s;
}

.link-forgot:hover { color: #E5590C; }

/* === CARD FOOTER === */
.card-footer-text {
    text-align: center;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #f0f0f0;
}

.card-footer-text p {
    font-size: 11px;
    color: #9ca3af;
}

/* === ALERTS === */
.alert {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
}

.alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #16a34a;
}

/* === SPINNER === */
.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}

/* === TRANSITIONS === */
.fade-enter-active, .fade-leave-active { transition: all 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-8px); }

.card-switch-enter-active, .card-switch-leave-active {
    transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
}
.card-switch-enter-from { opacity: 0; transform: translateX(40px) scale(0.96); }
.card-switch-leave-to   { opacity: 0; transform: translateX(-40px) scale(0.96); }

/* === RESPONSIVE === */
@media (max-width: 768px) {
    .login-form-section { padding: 20px; }

    .portal-grid {
        grid-template-columns: 1fr;
    }

    .portal-header h1 { font-size: 22px; }
    .portal-logo { max-width: 130px; }

    .form-card { padding: 35px 25px 25px; border-radius: 18px; }
    .form-header h2 { font-size: 22px; }
    .login-logo { max-width: 120px; }
}

@media (max-width: 480px) {
    .login-form-section { padding: 15px; }

    .portal-card { padding: 16px; }
    .portal-icon-wrap { width: 44px; height: 44px; }
    .portal-icon-wrap i { font-size: 20px; }
    .portal-info h3 { font-size: 14px; }

    .form-card { padding: 30px 20px 20px; border-radius: 16px; }
    .form-input { padding: 12px 14px; font-size: 14px; }
    .btn-login { padding: 13px; font-size: 15px; }
    .form-header h2 { font-size: 20px; }
    .form-header p { font-size: 13px; }
}
</style>
