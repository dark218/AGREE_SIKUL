<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
    status: String,
});

const page = usePage();

const form = useForm({});

const verificationLinkSent = computed(() => {
    return props.status === 'verification-link-sent' || 
           page.props.flash?.status?.includes('lien de vérification');
});

function resendVerification() {
    form.post(route('verification.resend'));
}
</script>

<template>
    <Head title="Vérification de l'email" />
    
    <div class="container-login100" style="opacity: 0.9;">
        <div class="wrap-login100 p-6">
            <div class="login100-form">
                <span class="login100-form-title">Vérification de l'adresse email</span>
                
                <div class="login-separater text-center mb-4">
                    <hr/>
                </div>

                <!-- Message de succès -->
                <div v-if="verificationLinkSent" class="alert alert-success" role="alert">
                    Un nouveau lien de vérification a été envoyé à votre adresse email.
                </div>

                <div class="text-center mb-4">
                    <div class="mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" style="fill: #3498db;">
                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </div>
                    
                    <p class="text-muted">
                        Merci de votre inscription ! Avant de commencer, pourriez-vous vérifier 
                        votre adresse email en cliquant sur le lien que nous venons de vous envoyer ?
                    </p>
                    
                    <p class="text-muted">
                        Si vous n'avez pas reçu l'email, nous vous en enverrons un autre avec plaisir.
                    </p>
                </div>

                <div class="container-login100-form-btn mb-3">
                    <button 
                        type="button"
                        class="login100-form-btn btn-primary"
                        :disabled="form.processing"
                        @click="resendVerification"
                    >
                        <span v-if="form.processing">
                            <i class="fas fa-spinner fa-spin"></i> Envoi en cours...
                        </span>
                        <span v-else>Renvoyer l'email de vérification</span>
                    </button>
                </div>

                <div class="text-center">
                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button"
                        class="btn btn-link txt1"
                    >
                        Se déconnecter
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.txt1 {
    color: #666;
    text-decoration: none;
    transition: color 0.3s;
    background: none;
    border: none;
    cursor: pointer;
}
.txt1:hover {
    color: #333;
}
.mb-3 {
    margin-bottom: 1rem;
}
.mb-4 {
    margin-bottom: 1.5rem;
}
.text-muted {
    color: #6c757d;
}
</style>
