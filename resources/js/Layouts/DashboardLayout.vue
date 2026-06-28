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
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
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
            <main class="dashboard-content">
                <slot />
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
