<script>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import PosLayout from '@/Layouts/PosLayout.vue';

// Layout dynamique selon l'état de connexion et le rôle
export default {
    layout: (h, page) => {
        const isAuthenticated = page.props.auth?.user;
        const userRole = page.props.auth?.user?.role?.toLowerCase();

        // Déterminer le layout selon le rôle
        let Layout = AuthLayout;
        if (isAuthenticated) {
            if (userRole === 'caissier' || userRole === 'manager') {
                Layout = PosLayout;
            } else {
                Layout = DashboardLayout;
            }
        }

        return h(Layout, () => page);
    }
};
</script>

<script setup>
const { t } = useI18n();
const page = usePage();

const props = defineProps({
    message: {
        type: String,
        default: null
    }
});

const isAuthenticated = computed(() => !!page.props.auth?.user);

const homeRoute = computed(() => {
    return isAuthenticated.value ? route('home') : route('login');
});

const homeLabel = computed(() => {
    return isAuthenticated.value ? t('back_to_dashboard') : t('back_to_login');
});
</script>

<template>
    <Head :title="t('unauthorized_title')" />

    <div class="unauthorized-container">
        <div class="unauthorized-card">
            <!-- Icon -->
            <div class="unauthorized-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M4.93 4.93l14.14 14.14" />
                </svg>
            </div>

            <!-- Logo -->
            <div class="unauthorized-logo">
                <img :src="page.props.appConfig?.logo_login || page.props.appConfig?.logo || '/images/image.png'" alt="AGREE SIKUL" />
            </div>

            <!-- Title -->
            <h1 class="unauthorized-title">{{ t('unauthorized_title') }}</h1>

            <!-- Message -->
            <p class="unauthorized-message">
                {{ message || t('unauthorized_message') }}
            </p>

            <!-- Description -->
            <p class="unauthorized-description">
                {{ t('unauthorized_description') }}
            </p>

            <!-- Actions -->
            <div class="unauthorized-actions">
                <Link :href="homeRoute" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    {{ homeLabel }}
                </Link>
            </div>

            <!-- Contact support -->
            <!-- <p class="unauthorized-support">
                {{ t('need_help') }}
                <a href="mailto:support@smilpay.com">{{ t('contact_support') }}</a>
            </p> -->
        </div>
    </div>
</template>

<style scoped>
.unauthorized-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 20px;
}

.unauthorized-card {
    background: white;
    border-radius: 24px;
    padding: 48px 40px;
    max-width: 480px;
    width: 100%;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
}

.unauthorized-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.unauthorized-icon svg {
    width: 40px;
    height: 40px;
    color: #dc2626;
}

.unauthorized-logo {
    margin-bottom: 24px;
}

.unauthorized-logo img {
    width: 120px;
    height: auto;
}

.unauthorized-title {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 12px;
}

.unauthorized-message {
    font-size: 16px;
    color: #64748b;
    margin: 0 0 8px;
    line-height: 1.6;
}

.unauthorized-description {
    font-size: 14px;
    color: #94a3b8;
    margin: 0 0 32px;
    line-height: 1.5;
}

.unauthorized-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
}

.btn-primary,
.btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -5px rgba(30, 41, 59, 0.4);
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-secondary:hover {
    background: #e2e8f0;
}

.unauthorized-support {
    font-size: 13px;
    color: #94a3b8;
    margin: 0;
}

.unauthorized-support a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.unauthorized-support a:hover {
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 480px) {
    .unauthorized-card {
        padding: 32px 24px;
        border-radius: 16px;
    }

    .unauthorized-icon {
        width: 64px;
        height: 64px;
    }

    .unauthorized-icon svg {
        width: 32px;
        height: 32px;
    }

    .unauthorized-title {
        font-size: 24px;
    }

    .unauthorized-logo img {
        width: 100px;
    }
}
</style>
