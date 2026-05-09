<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const showPassword = ref(false);

const form = useForm({
    password: '',
});

function handleSubmit() {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset('password');
        },
    });
}
</script>

<template>
    <Head title="Confirmer le mot de passe" />
    
    <div class="container-login100" style="opacity: 0.9;">
        <div class="wrap-login100 p-6">
            <form class="login100-form validate-form" @submit.prevent="handleSubmit">
                <span class="login100-form-title">Confirmation du mot de passe</span>
                
                <div class="login-separater text-center mb-4">
                    <span>Veuillez confirmer votre mot de passe avant de continuer</span>
                    <hr/>
                </div>

                <!-- Message d'erreur -->
                <div v-if="form.errors.password" class="alert alert-danger" role="alert">
                    {{ form.errors.password }}
                </div>

                <!-- Mot de passe -->
                <div class="wrap-input100 validate-input" data-validate="Password is required">
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
                            placeholder="Mot de passe"
                            required
                            autofocus
                            style="width: 250px;"
                        />
                        &nbsp;
                        <span style="cursor: pointer;" @click="showPassword = !showPassword">
                            <i :class="showPassword ? 'bx bx-show' : 'bx bx-hide'" class="toggle-password"></i>
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
                            <i class="fas fa-spinner fa-spin"></i> Vérification...
                        </span>
                        <span v-else>Confirmer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
