<script setup>
import { ref, onMounted, onUnmounted, provide, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import TheSidebar from '@/Components/Layout/TheSidebar.vue';
import TheNavbar from '@/Components/Layout/TheNavbar.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import NotificationManager from '@/Components/NotificationManager.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    }
});

// State
const isSidebarCollapsed = ref(false);
const isMobileSidebarOpen = ref(false);
const showLogoutModal = ref(false);
const page = usePage();

// Indicateur de navigation Inertia (évite l'impression d'écran "figé")
const isNavigating = ref(false);
let navDelayTimer = null;
let removeStartListener = null;
let removeFinishListener = null;

// Provide logout function pour les enfants
provide('openLogoutModal', () => {
    showLogoutModal.value = true;
});

provide('sidebarState', {
    isCollapsed: isSidebarCollapsed,
    isMobileOpen: isMobileSidebarOpen,
    toggle: () => isSidebarCollapsed.value = !isSidebarCollapsed.value,
    toggleMobile: () => isMobileSidebarOpen.value = !isMobileSidebarOpen.value,
    closeMobile: () => isMobileSidebarOpen.value = false
});

function handleLogout() {
    showLogoutModal.value = false;
    router.post(route('logout'));
}

// Gestion du responsive
function handleResize() {
    if (window.innerWidth <= 768) {
        isSidebarCollapsed.value = false;
    }
}

// Fermer le sidebar mobile UNIQUEMENT si on est vraiment en mode mobile (< 768px)
watch(
    () => page.url,
    () => {
        if (window.innerWidth <= 768) {
            isMobileSidebarOpen.value = false;
        }
    }
);

onMounted(() => {
      if (!sessionStorage.getItem('home')) {
        sessionStorage.setItem('home', '1');
        window.location.reload();
    }
    window.addEventListener('resize', handleResize);
    handleResize();

    // Écoute les navigations Inertia pour afficher un voile de chargement.
    // Un délai de 200ms évite tout clignotement sur les pages rapides.
    removeStartListener = router.on('start', () => {
        navDelayTimer = setTimeout(() => {
            isNavigating.value = true;
        }, 200);
    });

    removeFinishListener = router.on('finish', () => {
        clearTimeout(navDelayTimer);
        isNavigating.value = false;
    });
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
    clearTimeout(navDelayTimer);
    if (removeStartListener) removeStartListener();
    if (removeFinishListener) removeFinishListener();
});
</script>

<template>
    <Head :title="title" />
    
    <div class="dashboard-layout" :class="{ 'sidebar-collapsed': isSidebarCollapsed }">
        <!-- Sidebar -->
        <TheSidebar 
            :is-collapsed="isSidebarCollapsed"
            :is-mobile-open="isMobileSidebarOpen"
        />

        <!-- Overlay mobile -->
        <div 
            v-if="isMobileSidebarOpen" 
            class="sidebar-overlay"
            @click="isMobileSidebarOpen = false"
        ></div>

        <!-- Contenu principal -->
        <div class="dashboard-main">
            <!-- Header / Navbar -->
            <TheNavbar />

            <!-- Contenu de la page -->
            <main class="dashboard-content" :class="{ 'is-navigating': isNavigating }">
                <slot />

                <!-- Voile de navigation (chargement d'une nouvelle page Inertia) -->
                <Transition name="nav-veil-fade">
                    <div v-if="isNavigating" class="nav-veil">
                        <div class="nav-veil-spinner"></div>
                        <span class="nav-veil-text">Chargement…</span>
                    </div>
                </Transition>
            </main>
        </div>

        <!-- Modal de confirmation de déconnexion -->
        <ConfirmModal
            :is-open="showLogoutModal"
            title="Déconnexion"
            message="Êtes-vous sûr de vouloir vous déconnecter ?"
            confirm-text="Déconnexion"
            cancel-text="Annuler"
            variant="danger"
            @confirm="handleLogout"
            @cancel="showLogoutModal = false"
        />

        <!-- Gestionnaire de notifications -->
        <NotificationManager />
    </div>
</template>

<style scoped>
.dashboard-layout {
    min-height: 100vh;
    background-color: var(--bg-page);
    display: flex;
}

.dashboard-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    margin-left: 250px;
    min-height: 100vh;
    min-width: 0;
    max-width: 100%;
    transition: margin-left 0.3s ease;
}

.sidebar-collapsed .dashboard-main {
    margin-left: 80px;
}

.dashboard-content {
    flex: 1;
    padding: 24px;
    min-width: 0;
    max-width: 100%;
    overflow-y: auto;
    overflow-x: auto;
    position: relative;
}

/* Pendant la navigation : contenu légèrement atténué + curseur d'attente */
.dashboard-content.is-navigating {
    cursor: progress;
}

.dashboard-content.is-navigating > :not(.nav-veil) {
    opacity: 0.55;
    transition: opacity 0.2s ease;
    pointer-events: none;
}

/* Voile de navigation */
.nav-veil {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    z-index: 50;
    background: rgba(255, 255, 255, 0.35);
    backdrop-filter: blur(1px);
}

.nav-veil-spinner {
    width: 44px;
    height: 44px;
    border: 4px solid rgba(229, 89, 12, 0.2);
    border-top-color: #E5590C;
    border-radius: 50%;
    animation: nav-veil-spin 0.7s linear infinite;
}

.nav-veil-text {
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #E5590C;
    letter-spacing: 0.3px;
}

@keyframes nav-veil-spin {
    to { transform: rotate(360deg); }
}

.nav-veil-fade-enter-active,
.nav-veil-fade-leave-active {
    transition: opacity 0.2s ease;
}

.nav-veil-fade-enter-from,
.nav-veil-fade-leave-to {
    opacity: 0;
}

/* Overlay mobile */
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
    backdrop-filter: blur(4px);
}

/* Responsive - Tablettes */
@media (max-width: 992px) {
    .dashboard-main {
        margin-left: 80px;
    }

    .sidebar-collapsed .dashboard-main {
        margin-left: 80px;
    }

    .dashboard-content {
        padding: 16px;
    }
}

/* Responsive - Mobile */
@media (max-width: 768px) {
    .dashboard-main {
        margin-left: 0;
    }

    .sidebar-collapsed .dashboard-main {
        margin-left: 0;
    }

    .sidebar-overlay {
        display: block;
    }

    .dashboard-content {
        padding: 12px;
    }
}

/* Responsive - Très petit écran */
@media (max-width: 480px) {
    .dashboard-content {
        padding: 8px;
    }
}
</style>
