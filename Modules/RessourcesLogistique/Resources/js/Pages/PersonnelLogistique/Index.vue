<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const modules = [
    {
        title: 'Exemplaires',
        description: 'Gestion des copies/exemplaires de livres',
        icon: 'fa-copy',
        route: 'exemplaires.index',
        color: 'primary',
    },
    {
        title: 'Ouvrages',
        description: 'Gestion des ouvrages et livres',
        icon: 'fa-book',
        route: 'ouvrages.index',
        color: 'info',
    },
    {
        title: 'Emprunts',
        description: 'Gestion des emprunts de livres',
        icon: 'fa-handshake',
        route: 'emprunts.index',
        color: 'success',
    },
    {
        title: 'Réservations',
        description: 'Gestion des réservations',
        icon: 'fa-calendar-check',
        route: 'reservations.index',
        color: 'warning',
    },
    {
        title: 'Bibliothèques',
        description: 'Gestion des bibliothèques',
        icon: 'fa-building',
        route: 'bibliotheques.index',
        color: 'danger',
    },
];
</script>

<template>
    <Head :title="t('common.personnel_logistique') || 'Personnel Logistique'" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-cubes"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('common.personnel_logistique') || 'Personnel Logistique' }}</h5>
                            </div>
                        </div>
                        <div class="dash-payment-body">
                            <div class="row g-3">
                                <div v-for="module in modules" :key="module.route" class="col-md-6 col-lg-4">
                                    <Link :href="route(module.route)" class="text-decoration-none">
                                        <div :class="`card border-${module.color} h-100 shadow-sm hover-shadow transition`">
                                            <div class="card-body text-center">
                                                <div :class="`text-${module.color} mb-3`" style="font-size: 2.5rem;">
                                                    <i :class="`fa ${module.icon}`"></i>
                                                </div>
                                                <h6 class="card-title fw-bold">{{ module.title }}</h6>
                                                <p class="card-text text-muted small">{{ module.description }}</p>
                                                <button :class="`btn btn-sm btn-${module.color}`">
                                                    <i class="fa fa-arrow-right"></i> Accéder
                                                </button>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-2px);
}

.transition {
    transition: all 0.3s ease;
}
</style>
