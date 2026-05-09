<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';

// Pas de layout - utilise le template auth comme Login/ResetPassword

const props = defineProps({
    title: String,
});

const page = usePage();

const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showPasswordConfirm = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const hasError = computed(() => {
    return form.errors.current_password || form.errors.password || form.errors.password_confirmation;
});

const errorMessage = computed(() => {
    if (form.errors.current_password) return form.errors.current_password;
    if (form.errors.password) return form.errors.password;
    if (form.errors.password_confirmation) return form.errors.password_confirmation;
    return null;
});

// Flash messages from Laravel
const flashError = computed(() => page.props.flash?.error || null);
const flashSuccess = computed(() => page.props.flash?.success || null);

// Indicateurs de force du mot de passe
const passwordStrength = computed(() => {
    const password = form.password;
    let strength = 0;

    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;

    return strength;
});

const strengthLabel = computed(() => {
    const strength = passwordStrength.value;
    if (strength === 0) return '';
    if (strength <= 2) return 'Faible';
    if (strength <= 3) return 'Moyen';
    if (strength <= 4) return 'Fort';
    return 'Tres fort';
});

const strengthColor = computed(() => {
    const strength = passwordStrength.value;
    if (strength === 0) return '#e0e0e0';
    if (strength <= 2) return '#e74c3c';
    if (strength <= 3) return '#f39c12';
    if (strength <= 4) return '#27ae60';
    return '#2ecc71';
});

const passwordsMatch = computed(() => {
    return form.password && form.password_confirmation && form.password === form.password_confirmation;
});

const passwordsDontMatch = computed(() => {
    return form.password && form.password_confirmation && form.password !== form.password_confirmation;
});

function handleSubmit() {
    form.post(route('password.change'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Changer le mot de passe" />

    <div class="container-login100 login-wrapper" style="opacity: 0.9;">
        <div class="login-content">
            <!-- Logo SmilPay centre en haut -->
            <div class="login-logo-container">
                <img :src="$page.props.appConfig?.logo_login || $page.props.appConfig?.logo || '/images/image.png'" alt="AGREE SIKUL" class="login-logo" />
            </div>

            <div class="wrap-login100 p-6">
                <form class="login100-form validate-form" @submit.prevent="handleSubmit">
                    <span class="login100-form-title">Changer le mot de passe</span>

                    <div class="login-separater text-center mb-4">
                        <span>Entrez votre mot de passe actuel et votre nouveau mot de passe</span>
                        <hr />
                    </div>

                    <!-- Messages d'erreur -->
                    <div v-if="flashError" class="alert alert-danger" role="alert">
                        {{ flashError }}
                    </div>

                    <div v-if="flashSuccess" class="alert alert-success" role="alert">
                        {{ flashSuccess }}
                    </div>

                    <div v-if="hasError && !flashError" class="alert alert-danger" role="alert">
                        {{ errorMessage }}
                    </div>

                    <!-- Mot de passe actuel -->
                    <div class="wrap-input100 validate-input mb-3">
                        <div class="input-row">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                                    <path d="M0 0h24v24H0V0z" fill="none"/>
                                    <path d="M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                                </svg>
                            </span>
                            <input
                                v-model="form.current_password"
                                :type="showCurrentPassword ? 'text' : 'password'"
                                class="form-control input-field"
                                name="current_password"
                                placeholder="Mot de passe actuel"
                                required
                            />
                            <span class="toggle-icon" @click="showCurrentPassword = !showCurrentPassword">
                                <i :class="showCurrentPassword ? 'bx bx-show' : 'bx bx-hide'" class="toggle-password"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Nouveau mot de passe -->
                    <div class="wrap-input100 validate-input mb-3">
                        <div class="input-row">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                                    <path d="M0 0h24v24H0V0z" fill="none"/>
                                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                                </svg>
                            </span>
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="form-control input-field"
                                name="password"
                                placeholder="Nouveau mot de passe"
                                required
                            />
                            <span class="toggle-icon" @click="showPassword = !showPassword">
                                <i :class="showPassword ? 'bx bx-show' : 'bx bx-hide'" class="toggle-password"></i>
                            </span>
                        </div>
                        <!-- Indicateur de force -->
                        <div v-if="form.password" class="password-strength">
                            <div class="strength-bar">
                                <div
                                    class="strength-fill"
                                    :style="{
                                        width: (passwordStrength * 20) + '%',
                                        backgroundColor: strengthColor
                                    }"
                                ></div>
                            </div>
                            <small :style="{ color: strengthColor }">
                                Force : {{ strengthLabel }}
                            </small>
                        </div>
                    </div>

                    <!-- Confirmation mot de passe -->
                    <div class="wrap-input100 validate-input mb-3">
                        <div class="input-row">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                                    <path d="M0 0h24v24H0V0z" fill="none"/>
                                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                                </svg>
                            </span>
                            <input
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirm ? 'text' : 'password'"
                                class="form-control input-field"
                                name="password_confirmation"
                                placeholder="Confirmer le mot de passe"
                                required
                            />
                            <span class="toggle-icon" @click="showPasswordConfirm = !showPasswordConfirm">
                                <i :class="showPasswordConfirm ? 'bx bx-show' : 'bx bx-hide'" class="toggle-password"></i>
                            </span>
                        </div>
                        <!-- Indicateur de correspondance -->
                        <div v-if="form.password_confirmation" class="password-match">
                            <small v-if="passwordsMatch" class="text-success">
                                <i class="fa fa-check"></i> Les mots de passe correspondent
                            </small>
                            <small v-else-if="passwordsDontMatch" class="text-danger">
                                <i class="fa fa-times"></i> Les mots de passe ne correspondent pas
                            </small>
                        </div>
                    </div>

                    <!-- Regles de mot de passe -->
                    <div class="password-rules">
                        <p class="rules-title">Le mot de passe doit contenir :</p>
                        <ul class="rules-list">
                            <li :class="{ 'valid': form.password.length >= 8 }">
                                <i :class="form.password.length >= 8 ? 'fa fa-check' : 'fa fa-times'"></i>
                                Au moins 8 caracteres
                            </li>
                            <li :class="{ 'valid': /[a-z]/.test(form.password) }">
                                <i :class="/[a-z]/.test(form.password) ? 'fa fa-check' : 'fa fa-times'"></i>
                                Une lettre minuscule
                            </li>
                            <li :class="{ 'valid': /[A-Z]/.test(form.password) }">
                                <i :class="/[A-Z]/.test(form.password) ? 'fa fa-check' : 'fa fa-times'"></i>
                                Une lettre majuscule
                            </li>
                            <li :class="{ 'valid': /[0-9]/.test(form.password) }">
                                <i :class="/[0-9]/.test(form.password) ? 'fa fa-check' : 'fa fa-times'"></i>
                                Un chiffre
                            </li>
                            <li :class="{ 'valid': /[^a-zA-Z0-9]/.test(form.password) }">
                                <i :class="/[^a-zA-Z0-9]/.test(form.password) ? 'fa fa-check' : 'fa fa-times'"></i>
                                Un caractere special
                            </li>
                        </ul>
                    </div>

                    <br>

                    <div class="container-login100-form-btn">
                        <button
                            type="submit"
                            class="login100-form-btn btn-primary"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">
                                <i class="fas fa-spinner fa-spin"></i> Modification...
                            </span>
                            <span v-else>Changer le mot de passe</span>
                        </button>
                    </div>

                    <br>

                    <!-- Lien retour -->
                    <div class="text-center">
                        <Link :href="route('home')" class="txt1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" style="fill: currentColor; vertical-align: middle;">
                                <path d="M20 11H7.41l5.3-5.29c.39-.39.39-1.02 0-1.41-.39-.39-1.02-.39-1.41 0l-7.7 7.71c-.39.39-.39 1.02 0 1.41l7.7 7.71c.39.39 1.02.39 1.41 0 .39-.39.39-1.02 0-1.41l-5.29-5.29H20c.55 0 1-.45 1-1s-.45-1-1-1z"/>
                            </svg>
                            Retour au tableau de bord
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-wrapper {
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.login-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    max-width: 480px;
}

.login-logo-container {
    text-align: center;
    margin-bottom: 20px;
}

.login-logo {
    width: 120px;
    height: 120px;
    object-fit: contain;
}

.wrap-login100 {
    width: 100%;
}

.mb-3 {
    margin-bottom: 1rem;
}

.input-row {
    display: flex;
    align-items: center;
    width: 100%;
}

.input-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    padding-right: 10px;
}

.input-icon svg {
    fill: #666;
}

.input-field {
    flex: 1;
    min-width: 0;
}

.toggle-icon {
    cursor: pointer;
    padding-left: 10px;
    display: flex;
    align-items: center;
}

.toggle-password {
    font-size: 20px;
    color: #666;
}

.password-strength {
    margin-top: 8px;
    padding-left: 34px;
}

.strength-bar {
    height: 6px;
    background: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-fill {
    height: 100%;
    transition: all 0.3s ease;
    border-radius: 3px;
}

.password-match {
    margin-top: 5px;
    padding-left: 34px;
}

.password-rules {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
}

.rules-title {
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 14px;
    color: #333;
}

.rules-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.rules-list li {
    color: #e74c3c;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.rules-list li.valid {
    color: #27ae60;
}

.rules-list li i {
    width: 14px;
    font-size: 11px;
}

.txt1 {
    color: #666;
    text-decoration: none;
    transition: color 0.3s;
}

.txt1:hover {
    color: #3498db;
}

.text-success {
    color: #27ae60;
}

.text-danger {
    color: #e74c3c;
}

.login100-form-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

@media (max-width: 576px) {
    .rules-list {
        grid-template-columns: 1fr;
    }

    .login-logo {
        width: 100px;
        height: 100px;
    }
}
</style>
