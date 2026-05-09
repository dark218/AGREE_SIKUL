<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>

    <Head title="Session expirée" />

    <div class="container-login100" style="opacity: 0.9;">
        <div class="wrap-login100 p-6">
            <div class="login100-form text-center">
                <!-- Logo -->
                <div class="mb-4">
                    <Link :href="route('login')" class="logo-link">
                    <img :src="$page.props.appConfig?.logo_login || $page.props.appConfig?.logo || '/images/image.png'" alt="AGREE SIKUL" style="max-width: 150px; height: auto;" />
                    </Link>
                </div>

                <!-- Icône d'expiration -->
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"
                        style="fill: #f39c12;">
                        <path
                            d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
                    </svg>
                </div>

                <span class="login100-form-title" style="font-size: 1.5rem;">
                    Session expirée
                </span>

                <div class="login-separater text-center mb-4">
                    <hr />
                </div>

                <p class="text-muted mb-4">
                    Votre session a expiré. Veuillez vous reconnecter pour continuer.
                </p>

                <div class="container-login100-form-btn">
                    <Link :href="route('login')" class="login100-form-btn btn-primary">
                    Se reconnecter
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.mb-4 {
    margin-bottom: 1.5rem;
}

.text-muted {
    color: #6c757d;
}

.logo-link {
    display: inline-block;
}
</style>
