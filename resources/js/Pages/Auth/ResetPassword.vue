<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
    token: {
        type: String,
        required: true,
    },
    login: {
        type: String,
        default: '',
    },
    status: String,
});

const page = usePage();

const showPassword = ref(false);
const showPasswordConfirm = ref(false);

const form = useForm({
    token: props.token,
    login: props.login,
    password: '',
    password_confirmation: '',
});

const hasError = computed(() => {
    return form.errors.login || form.errors.token || form.errors.password;
});

const errorMessage = computed(() => {
    if (form.errors.token) return form.errors.token;
    if (form.errors.login) return form.errors.login;
    if (form.errors.password) return form.errors.password;
    return null;
});

function handleSubmit() {
    form.post(route('custompassword.update'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <Head title="Réinitialiser le mot de passe" />

    <div class="container-login100 login-wrapper" style="opacity: 0.9;">
        <div class="login-content">
            <!-- Logo SmilPay centré en haut -->
            <div class="login-logo-container">
                <img :src="$page.props.appConfig?.logo_login || $page.props.appConfig?.logo || '/images/image.png'" alt="AGREE SIKUL" class="login-logo" />
            </div>

            <div class="wrap-login100 p-6">
                <form class="login100-form validate-form" @submit.prevent="handleSubmit">
                <span class="login100-form-title">Réinitialisation du mot de passe</span>

                <div class="login-separater text-center mb-4">
                    <span>Entrez votre nouveau mot de passe</span>
                    <hr/>
                </div>

                <!-- Message d'erreur -->
                <div v-if="hasError" class="alert alert-danger" role="alert">
                    {{ errorMessage }}
                </div>

                <!-- Champs cachés -->
                <input type="hidden" name="token" :value="form.token">
                <input type="hidden" name="login" :value="form.login">

                <!-- Nouveau mot de passe -->
                <div class="wrap-input100 validate-input mb-3" data-validate="Password is required">
                    <div style="display: flex; align-items: center;">
                        <span class="">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                                <path d="M0 0h24v24H0V0z" fill="none"/>
                                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                            </svg>
                        </span>
                        &nbsp;
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="form-control"
                            name="password"
                            placeholder="Nouveau mot de passe"
                            required
                            style="width: 250px;"
                        />
                        &nbsp;
                        <span style="cursor: pointer;" @click="showPassword = !showPassword">
                            <i :class="showPassword ? 'bx bx-show' : 'bx bx-hide'" class="toggle-password"></i>
                        </span>
                    </div>
                </div>

                <!-- Confirmation mot de passe -->
                <div class="wrap-input100 validate-input mb-3" data-validate="Confirm password">
                    <div style="display: flex; align-items: center;">
                        <span class="">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                                <path d="M0 0h24v24H0V0z" fill="none"/>
                                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                            </svg>
                        </span>
                        &nbsp;
                        <input
                            v-model="form.password_confirmation"
                            :type="showPasswordConfirm ? 'text' : 'password'"
                            class="form-control"
                            name="password_confirmation"
                            placeholder="Confirmer le mot de passe"
                            required
                            style="width: 250px;"
                        />
                        &nbsp;
                        <span style="cursor: pointer;" @click="showPasswordConfirm = !showPasswordConfirm">
                            <i :class="showPasswordConfirm ? 'bx bx-show' : 'bx bx-hide'" class="toggle-password"></i>
                        </span>
                    </div>
                </div>

                <br>

                <div class="container-login100-form-btn">
                    <button
                        type="submit"
                        class="login100-form-btn btn-primary"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">
                            <i class="fas fa-spinner fa-spin"></i> Réinitialisation...
                        </span>
                        <span v-else>Réinitialiser le mot de passe</span>
                    </button>
                </div>

                <br>

                <!-- Lien retour connexion -->
                <div class="text-center">
                    <Link :href="route('login')" class="txt1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" style="fill: currentColor; vertical-align: middle;">
                            <path d="M20 11H7.41l5.3-5.29c.39-.39.39-1.02 0-1.41-.39-.39-1.02-.39-1.41 0l-7.7 7.71c-.39.39-.39 1.02 0 1.41l7.7 7.71c.39.39 1.02.39 1.41 0 .39-.39.39-1.02 0-1.41l-5.29-5.29H20c.55 0 1-.45 1-1s-.45-1-1-1z"/>
                        </svg>
                        Retour à la connexion
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
    max-width: 450px;
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

.txt1 {
    color: #666;
    text-decoration: none;
    transition: color 0.3s;
}
.txt1:hover {
    color: #3498db;
}
.mb-3 {
    margin-bottom: 1rem;
}
</style>
