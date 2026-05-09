<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
    status: String,
});

const page = usePage();

const form = useForm({
    email: '',
});

const flashStatus = computed(() => page.props.flash?.status || props.status);

function handleSubmit() {
    form.post(route('custompassword.forget'), {
        onFinish: () => {
            // Garder l'email en cas d'erreur
        },
    });
}
</script>

<template>
    <Head title="Mot de passe oublié" />
    
    <div class="container-login100" style="opacity: 0.9;">
        <div class="wrap-login100 p-6">
            <form class="login100-form validate-form" @submit.prevent="handleSubmit">
                <span class="login100-form-title">Réinitialisation du mot de passe</span>
                
                <div class="login-separater text-center mb-4">
                    <span>Entrez votre adresse email pour recevoir un lien de réinitialisation</span>
                    <hr/>
                </div>

                <!-- Message de succès -->
                <div v-if="flashStatus" class="alert alert-success" role="alert">
                    {{ flashStatus }}
                </div>

                <!-- Message d'erreur -->
                <div v-if="form.errors.email" class="alert alert-danger" role="alert">
                    {{ form.errors.email }}
                </div>

                <!-- Champ Email -->
                <div class="wrap-input100 validate-input" data-validate="Email is required">
                    <span class="">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </span>
                    &nbsp;
                    <input 
                        v-model="form.email"
                        type="email" 
                        class="form-control" 
                        name="email"
                        placeholder="Adresse email"
                        required
                        autofocus
                    />
                </div>

                <br>
                
                <div class="container-login100-form-btn">
                    <button 
                        type="submit" 
                        class="login100-form-btn btn-primary"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">
                            <i class="fas fa-spinner fa-spin"></i> Envoi en cours...
                        </span>
                        <span v-else>Envoyer le lien</span>
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
</template>

<style scoped>
.txt1 {
    color: #666;
    text-decoration: none;
    transition: color 0.3s;
}
.txt1:hover {
    color: #333;
}
</style>
