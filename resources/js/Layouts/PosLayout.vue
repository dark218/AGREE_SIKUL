<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import NotificationManager from '@/Components/NotificationManager.vue';
import NotificationModal from '@/Components/Common/NotificationModal.vue';
import { useLoader } from '@/Composables/useLoader';
import { useLocale } from '@/Composables/useLocale';
import { useTimeAgo } from '@/Composables/useTimeAgo';

const props = defineProps({
    title: {
        type: String,
        default: null
    },
    showBackButton: {
        type: Boolean,
        default: false
    },
    backRoute: {
        type: String,
        default: null
    },
    backRouteParams: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['back']);

const page = usePage();
const { currentLocale, changeLocale, t } = useLocale();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showLoader, hideLoader } = useLoader();
const { formatTimeAgo } = useTimeAgo();

// Languages
const languages = [
    { code: 'fr', label: 'Français', flag: `/assets/images/flags/french_flag.jpg` },
    { code: 'en', label: 'English', flag: `/assets/images/flags/us_flag.jpg` }
];

const showLanguageDropdown = ref(false);
const showGuideDropdown = ref(false);
const showNotifications = ref(false);
const showNotificationModal = ref(false);
const selectedNotification = ref(null);
const languageRef = ref(null);
const guideRef = ref(null);
const notificationRef = ref(null);
const showLogoutConfirm = ref(false);

const currentLanguageLabel = computed(() => {
    const lang = languages.find(l => l.code === currentLocale.value);
    return lang?.label || 'Français';
});

const currentUser = computed(() => page.props.auth?.user || {});
const notifications = computed(() => page.props.notifications || []);
const unreadCount = computed(() => page.props.unreadNotificationsCount || 0);

// Titre dynamique : props > pageTitle (props Inertia) > 'POS' par défaut
const displayTitle = computed(() => {
    return props.title || page.props.pageTitle || 'POS';
});

// Récupérer la session active depuis les props de la page (caissier seulement)
const sessionActive = computed(() => page.props.sessionActive || null);

// Détecter si l'utilisateur est un manager (basé sur la route actuelle)
const isManager = computed(() => {
    const currentUrl = page.url || '';
    return currentUrl.includes('/pos/manager') || currentUrl.includes('/session-caisse-manager');
});

// Items du guide selon le rôle
const guideItems = computed(() => {
    if (isManager.value) {
        return [
            {
                icon: 'fas fa-users-cog',
                title: t('pos.manager.guideSessions'),
                description: t('pos.manager.guideSessionsDesc')
            },
            {
                icon: 'fas fa-check-double',
                title: t('pos.manager.guideValidation'),
                description: t('pos.manager.guideValidationDesc')
            },
            {
                icon: 'fas fa-chart-bar',
                title: t('pos.manager.guideReports'),
                description: t('pos.manager.guideReportsDesc')
            }
        ];
    }
    return [
        {
            icon: 'fas fa-shopping-cart',
            title: t('pos.caissier.guideNewSale'),
            description: t('pos.caissier.guideNewSaleDesc')
        },
        {
            icon: 'fas fa-search',
            title: t('pos.caissier.guideSearch'),
            description: t('pos.caissier.guideSearchDesc')
        },
        {
            icon: 'fas fa-check-circle',
            title: t('pos.caissier.guideValidate'),
            description: t('pos.caissier.guideValidateDesc')
        }
    ];
});

// Titre du guide selon le rôle
const guideTitle = computed(() => {
    return isManager.value ? t('pos.manager.quickGuide') : t('pos.caissier.quickGuide');
});

function selectLanguage(langCode) {
    changeLocale(langCode);
    showLanguageDropdown.value = false;
}

function handleNotificationClick(notification) {
    selectedNotification.value = notification;
    showNotificationModal.value = true;
    showNotifications.value = false;
}

function handleNotificationMarkedAsRead(notificationId) {
    // Mettre à jour la notification dans la liste
    const index = page.props.notifications.findIndex(n => n.id === notificationId);
    if (index !== -1) {
        page.props.notifications[index].is_read = true;
    }

    // Décrémenter le compteur de notifications non lues
    if (page.props.unreadNotificationsCount > 0) {
        page.props.unreadNotificationsCount--;
    }
}

async function markAllAsRead() {
    try {
        await axios.post('/notification/mark-all-as-read');
        // Recharger toutes les données partagées
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('Erreur lors du marquage de toutes les notifications:', error);
    }
}

async function clearAllNotifications() {
    try {
        await axios.post('/notification/clear-all');
        showNotifications.value = false;
        // Recharger toutes les données partagées
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('Erreur lors de la suppression des notifications:', error);
    }
}

function handleClickOutside(event) {
    if (languageRef.value && !languageRef.value.contains(event.target)) {
        showLanguageDropdown.value = false;
    }
    if (guideRef.value && !guideRef.value.contains(event.target)) {
        showGuideDropdown.value = false;
    }
    if (notificationRef.value && !notificationRef.value.contains(event.target)) {
        showNotifications.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Navigation
const goBack = () => {
    if (props.backRoute) {
        router.visit(route(props.backRoute, props.backRouteParams));
    } else {
        emit('back');
    }
};

// Vérifier si l'utilisateur a le rôle Manager
const userRoles = computed(() => page.props.auth?.user?.roles || []);
const isUserManager = computed(() => {
    return userRoles.value.some(role => role.name === 'Manager');
});

// Redirection vers l'index selon le rôle de l'utilisateur
// Utilise window.location.href pour forcer un rechargement complet (CSS)
const goToIndex = () => {
    if (isUserManager.value) {
        window.location.href = route('session-caisse-manager.index');
    } else {
        window.location.href = route('session-caisse-caissier.index');
    }
};

// Logout
const logout = () => {
    showLogoutConfirm.value = false;
    showLoader(t('pos.caissier.loggingOut'), t('pos.caissier.pleaseWait'), 'primary');
    router.post(route('logout'));
};

// Expose pour les composants enfants
defineExpose({
    showLoader,
    hideLoader,
    t
});
</script>

<template>
    <Head :title="displayTitle" />

    <div class="pos-container">
        <!-- Header POS -->
        <header class="pos-navbar">
            <div class="navbar-left">
                <!-- Back Button -->
                <button v-if="showBackButton" @click="goBack" class="btn-nav-back">
                    <i class="fas fa-arrow-left"></i>
                </button>

                <!-- Logo (cliquable pour retourner à l'index) -->
                <div class="pos-logo" @click="goToIndex" style="cursor: pointer;">
                    <img :src="$page.props.appConfig?.logo || $page.props.appConfig?.logo_login || '/images/image.png'" alt="AGREE SIKUL" class="logo-image" />
                </div>

                <!-- Title -->
                <h1 class="pos-title">{{ displayTitle }}</h1>
            </div>

            <div class="navbar-right">
                <!-- Guide Dropdown -->
                <div class="guide-wrapper" ref="guideRef">
                    <button class="guide-toggle-btn" @click.stop="showGuideDropdown = !showGuideDropdown">
                        <i class="fas fa-question-circle"></i>
                        <span class="guide-label">{{ t('pos.caissier.help') }}</span>
                        <i class="fas fa-chevron-down guide-chevron" :class="{ 'rotated': showGuideDropdown }"></i>
                    </button>
                    <Transition name="dropdown">
                        <div v-if="showGuideDropdown" class="guide-dropdown">
                            <div class="guide-dropdown-header">
                                <i class="fas fa-lightbulb"></i>
                                <span>{{ guideTitle }}</span>
                            </div>
                            <div class="guide-dropdown-content">
                                <div v-for="(item, index) in guideItems" :key="index" class="guide-item">
                                    <div class="guide-item-icon">
                                        <i :class="item.icon"></i>
                                    </div>
                                    <div class="guide-item-text">
                                        <strong>{{ item.title }}</strong>
                                        <span>{{ item.description }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- Language Selector -->
                <div class="language-wrapper" ref="languageRef">
                    <button class="language-toggle-btn" @click.stop="showLanguageDropdown = !showLanguageDropdown">
                        <img
                            :src="languages.find(l => l.code === currentLocale)?.flag"
                            :alt="currentLanguageLabel"
                            class="flag-icon"
                        />
                        <span class="language-label">{{ currentLanguageLabel }}</span>
                        <i class="fas fa-chevron-down language-chevron" :class="{ 'rotated': showLanguageDropdown }"></i>
                    </button>
                    <Transition name="dropdown">
                        <div v-if="showLanguageDropdown" class="language-dropdown">
                            <button
                                v-for="lang in languages"
                                :key="lang.code"
                                class="language-option"
                                :class="{ 'active': currentLocale === lang.code }"
                                @click="selectLanguage(lang.code)"
                            >
                                <img :src="lang.flag" :alt="lang.label" class="flag-icon" />
                                <span>{{ lang.label }}</span>
                                <i v-if="currentLocale === lang.code" class="fas fa-check"></i>
                            </button>
                        </div>
                    </Transition>
                </div>

                <!-- Notifications -->
                <div class="notification-wrapper" ref="notificationRef">
                    <button
                        class="notification-btn"
                        @click.stop="showNotifications = !showNotifications"
                    >
                        <i class="fas fa-bell"></i>
                        <span v-if="unreadCount > 0" class="notification-badge">
                            {{ unreadCount > 99 ? '99+' : unreadCount }}
                        </span>
                    </button>

                    <Transition name="dropdown">
                        <div v-if="showNotifications" class="notification-dropdown">
                            <div class="dropdown-header">
                                <h4>{{ t('Notifications') }}</h4>
                                <div class="notification-actions" v-if="notifications.length > 0">
                                    <button
                                        @click="markAllAsRead"
                                        class="notification-action-btn"
                                        :title="t('Marquer tout comme lu')"
                                    >
                                        <i class="fas fa-check-double"></i>
                                    </button>
                                    <button
                                        @click="clearAllNotifications"
                                        class="notification-action-btn"
                                        :title="t('Tout supprimer')"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="notification-list">
                                <template v-if="notifications.length > 0">
                                    <div
                                        v-for="notification in notifications"
                                        :key="notification.id"
                                        class="notification-item"
                                        :class="{ 'unread': !notification.is_read }"
                                        @click="handleNotificationClick(notification)"
                                    >
                                        <div class="notification-avatar">
                                            <img :src="`/backend/images/default/profile-default.webp`" alt="user" />
                                        </div>
                                        <div class="notification-content">
                                            <h5 class="notification-title">{{ notification.title }}</h5>
                                            <p class="notification-body">{{ notification.body }}</p>
                                            <span class="notification-time">{{ formatTimeAgo(notification.created_at) }}</span>
                                        </div>
                                    </div>
                                </template>
                                <div v-else class="notification-empty">
                                    <i class="fas fa-bell"></i>
                                    <p>{{ t('listeempty') }}</p>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- User Info -->
                <div class="user-info">
                    <span class="user-name">{{ currentUser.prenoms }} {{ currentUser.nom }}<template v-if="!isManager && sessionActive?.id"> (Session {{ sessionActive.reference }})</template></span>
                </div>

                <!-- Logout -->
                <button class="btn-logout" @click="showLogoutConfirm = true">
                    <i class="fas fa-power-off"></i>
                    <span>{{ t('pos.caissier.logout') }}</span>
                </button>
            </div>
        </header>

        <!-- Alert Messages -->
        <div class="alert-container">
            <AlertMessage />
        </div>

        <!-- Main Content (slot) -->
        <main class="pos-main-wrapper">
            <slot></slot>
        </main>

        <!-- Modal Confirmation Deconnexion -->
        <ConfirmModal
            v-model:show="showLogoutConfirm"
            :title="t('pos.caissier.logoutConfirmTitle')"
            :message="t('pos.caissier.logoutConfirmDesc')"
            :confirm-text="t('pos.caissier.confirmLogout')"
            :cancel-text="t('common.cancel')"
            variant="danger"
            @confirm="logout"
        />

        <!-- Loader -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />

        <!-- Gestionnaire de notifications -->
        <NotificationManager />

        <!-- Modal notification -->
        <NotificationModal
            :is-open="showNotificationModal"
            :notification="selectedNotification"
            @close="showNotificationModal = false"
            @marked-as-read="handleNotificationMarkedAsRead"
        />
    </div>
</template>

<style>
/* ========================================
   POS LAYOUT - Container Principal
   ======================================== */
.pos-container {
    display: flex;
    flex-direction: column;
    height: 100vh;
    width: 100%;
    background: var(--pos-bg, #F1F3F6);
    overflow: hidden;
    font-family: 'Outfit', sans-serif;
}

/* ========================================
   POS NAVBAR
   ======================================== */
.pos-container .pos-navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 1.5rem;
    height: 70px;
    background: #FFFFFF;
    border-bottom: 1px solid var(--pos-border, #E2E8F0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    flex-shrink: 0;
    z-index: 100;
}

.pos-container .navbar-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.pos-container .navbar-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Back Button */
.pos-container .btn-nav-back {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: var(--pos-bg, #F1F3F6);
    border: none;
    border-radius: 10px;
    color: var(--pos-text, #2D3748);
    cursor: pointer;
    transition: all 0.2s;
}

.pos-container .btn-nav-back:hover {
    background: var(--pos-border, #E2E8F0);
}

/* Logo */
.pos-container .pos-logo {
    display: flex;
    align-items: center;
}

.pos-container .logo-image {
    height: 40px;
    width: auto;
}

/* Title */
.pos-container .pos-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--pos-text, #2D3748);
    margin: 0;
}

/* Language Selector */
.pos-container .language-wrapper {
    position: relative;
}

.pos-container .language-toggle-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--pos-bg, #F1F3F6);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.pos-container .language-toggle-btn:hover {
    background: var(--pos-border, #E2E8F0);
}

.pos-container .flag-icon {
    width: 24px;
    height: 16px;
    object-fit: cover;
    border-radius: 2px;
}

.pos-container .language-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--pos-text, #2D3748);
}

.pos-container .language-chevron {
    font-size: 0.75rem;
    color: var(--pos-text-muted, #718096);
    transition: transform 0.2s;
}

.pos-container .language-chevron.rotated {
    transform: rotate(180deg);
}

.pos-container .language-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #FFFFFF;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    min-width: 160px;
    z-index: 1000;
}

.pos-container .language-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.75rem 1rem;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    transition: background 0.15s;
}

.pos-container .language-option:hover {
    background: var(--pos-bg, #F1F3F6);
}

.pos-container .language-option.active {
    background: rgba(0, 0, 0, 0.05);
}

.pos-container .language-option span {
    flex: 1;
    font-size: 0.9rem;
}

.pos-container .language-option i {
    color: var(--pos-success, #2ECC71);
}

/* Notifications */
.pos-container .notification-wrapper {
    position: relative;
}

.pos-container .notification-btn {
    position: relative;
    padding: 8px;
    border-radius: 12px;
    color: #2D3748;
    transition: all 0.15s ease;
    background: var(--pos-bg, #F1F3F6);
    border: none;
    cursor: pointer;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pos-container .notification-btn:hover {
    background: var(--pos-border, #E2E8F0);
    color: #000;
}

.pos-container .notification-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: #E74C3C;
    color: white;
    font-size: 11px;
    font-weight: 700;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(231, 76, 60, 0.4);
    border: 2px solid white;
}

.pos-container .notification-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 360px;
    max-height: 450px;
    background: #FFFFFF;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid var(--pos-border, #E2E8F0);
    overflow: hidden;
    z-index: 1000;
}

.pos-container .dropdown-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid var(--pos-border, #E2E8F0);
}

.pos-container .dropdown-header h4 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--pos-text, #2D3748);
    margin: 0;
}

.pos-container .notification-actions {
    display: flex;
    gap: 8px;
}

.pos-container .notification-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: var(--pos-bg, #F1F3F6);
    color: var(--pos-text-muted, #718096);
    cursor: pointer;
    transition: all 0.2s ease;
}

.pos-container .notification-action-btn:hover {
    background: var(--pos-border, #E2E8F0);
    color: #000;
}

.pos-container .notification-action-btn:active {
    transform: scale(0.95);
}

.pos-container .notification-list {
    max-height: 350px;
    overflow-y: auto;
}

.pos-container .notification-item {
    display: flex;
    gap: 12px;
    padding: 16px;
    border-bottom: 1px solid var(--pos-border, #E2E8F0);
    cursor: pointer;
    transition: all 0.15s ease;
}

.pos-container .notification-item:hover {
    background: var(--pos-bg, #F1F3F6);
}

.pos-container .notification-item.unread {
    background: rgba(52, 152, 219, 0.05);
}

.pos-container .notification-item.unread .notification-title {
    color: #E74C3C;
    font-weight: 600;
}

.pos-container .notification-avatar {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
}

.pos-container .notification-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pos-container .notification-content {
    flex: 1;
    min-width: 0;
}

.pos-container .notification-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--pos-text, #2D3748);
    margin: 0 0 2px 0;
}

.pos-container .notification-body {
    font-size: 0.75rem;
    color: var(--pos-text-muted, #718096);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0 0 4px 0;
}

.pos-container .notification-time {
    font-size: 0.75rem;
    color: #A0AEC0;
}

.pos-container .notification-empty {
    padding: 48px;
    text-align: center;
    color: #A0AEC0;
}

.pos-container .notification-empty i {
    font-size: 48px;
    opacity: 0.5;
    display: block;
    margin-bottom: 8px;
}

/* Guide Dropdown */
.pos-container .guide-wrapper {
    position: relative;
}

.pos-container .guide-toggle-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(52, 152, 219, 0.1);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    color: #000;
}

.pos-container .guide-toggle-btn:hover {
    background: rgba(52, 152, 219, 0.2);
}

.pos-container .guide-label {
    font-size: 0.9rem;
    font-weight: 500;
}

.pos-container .guide-chevron {
    font-size: 0.75rem;
    transition: transform 0.2s;
}

.pos-container .guide-chevron.rotated {
    transform: rotate(180deg);
}

.pos-container .guide-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #FFFFFF;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    min-width: 320px;
    z-index: 1000;
}

.pos-container .guide-dropdown-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #000 0%, #000 100%);
    color: #fff;
    font-weight: 600;
}

.pos-container .guide-dropdown-header i {
    font-size: 1.25rem;
}

.pos-container .guide-dropdown-content {
    padding: 0.75rem;
}

.pos-container .guide-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.875rem;
    border-radius: 10px;
    transition: background 0.15s;
}

.pos-container .guide-item:hover {
    background: var(--pos-bg, #F1F3F6);
}

.pos-container .guide-item-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(52, 152, 219, 0.1);
    color: #000;
    border-radius: 8px;
    flex-shrink: 0;
}

.pos-container .guide-item-text {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.pos-container .guide-item-text strong {
    font-size: 0.9rem;
    color: var(--pos-text, #2D3748);
}

.pos-container .guide-item-text span {
    font-size: 0.8rem;
    color: var(--pos-text-muted, #718096);
}

/* User Info */
.pos-container .user-info {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: var(--pos-bg, #F1F3F6);
    border-radius: 10px;
}

.pos-container .user-name {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--pos-text, #2D3748);
}

/* Logout Button */
.pos-container .btn-logout {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(231, 76, 60, 0.1);
    border: none;
    border-radius: 10px;
    color: var(--pos-danger, #E74C3C);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.pos-container .btn-logout:hover {
    background: var(--pos-danger, #E74C3C);
    color: #FFFFFF;
}

/* ========================================
   ALERT CONTAINER
   ======================================== */
.pos-container .alert-container {
    padding: 0 1.5rem;
    flex-shrink: 0;
}

/* ========================================
   MAIN CONTENT WRAPPER
   ======================================== */
.pos-container .pos-main-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* ========================================
   TRANSITIONS
   ======================================== */
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* ========================================
   RESPONSIVE
   ======================================== */
@media (max-width: 768px) {
    .pos-container .pos-navbar {
        padding: 0 1rem;
        height: 60px;
    }

    .pos-container .pos-title {
        font-size: 1rem;
    }

    .pos-container .language-label,
    .pos-container .btn-logout span {
        display: none;
    }

    .pos-container .user-info {
        display: none;
    }

    .pos-container .language-toggle-btn,
    .pos-container .btn-logout {
        padding: 0.5rem;
    }

    .pos-container .notification-dropdown {
        width: calc(100vw - 32px);
        right: -60px;
    }

    .pos-container .notification-btn {
        padding: 6px;
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .pos-container .pos-logo {
        display: none;
    }

    .pos-container .notification-dropdown {
        right: -100px;
        width: calc(100vw - 20px);
    }

    .pos-container .notification-btn {
        padding: 4px;
        font-size: 18px;
    }
}

</style>
<style>
/* ========================================
   MANAGER DASHBOARD STYLES
   ======================================== */

.manager-dashboard {
    padding: 1.5rem;
    max-width: 1600px;
    margin: 0 auto;
}

/* Stats Section */
.stats-section {
    margin-bottom: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-card.stat-primary .stat-icon {
    background: rgba(0, 0, 0, 0.08);
    color: #000;
}

.stat-card.stat-warning .stat-icon {
    background: rgba(243, 156, 18, 0.15);
    color: #F39C12;
}

.stat-card.stat-success .stat-icon {
    background: rgba(46, 204, 113, 0.15);
    color: #2ECC71;
}

.stat-card.stat-info .stat-icon {
    background: rgba(52, 152, 219, 0.15);
    color: #3498DB;
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--pos-text, #2D3748);
    line-height: 1.2;
}

.stat-label {
    font-size: 0.85rem;
    color: #000;
    margin-top: 0.25rem;
}

/* Tabs Navigation */
.tabs-navigation {
    display: flex;
    gap: 0.5rem;
    background: #fff;
    padding: 0.5rem;
    border-radius: 14px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.tab-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.25rem;
    background: transparent;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--pos-text-muted, #718096);
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.tab-btn:hover {
    background: var(--pos-bg, #F1F3F6);
    color: var(--pos-text, #2D3748);
}

.tab-btn.active {
    background: #000;
    color: #fff;
}

.tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: #E74C3C;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 10px;
}

.tab-badge.warning {
    background: #F39C12;
}

/* Tab Content */
.tab-content {
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Dashboard Grid */
.dashboard-grid {
    /* display: grid; */
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

/* Quick Actions Card */
.quick-actions-card,
.pending-card,
.recent-sales-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.card-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--pos-text, #2D3748);
    margin-bottom: 1.25rem;
}

.card-title i {
    color: var(--pos-text-muted, #718096);
}

.card-title.warning {
    color: #F39C12;
}

.card-title.warning i {
    color: #F39C12;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.25rem;
    border: none;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn i {
    font-size: 1.5rem;
}

.action-btn.action-primary {
    background: #000;
    color: #fff;
}

.action-btn.action-primary:hover {
    background: #333;
}

.action-btn.action-secondary {
    background: var(--pos-bg, #F1F3F6);
    color: #000;
}

.action-btn.action-secondary i {
    color: #000;
}

.action-btn.action-secondary:hover {
    background: var(--pos-border, #E2E8F0);
}

/* Pending Card */
.pending-text {
    color: var(--pos-text-muted, #718096);
    margin-bottom: 1rem;
}

.btn-view-pending {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #F39C12;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-view-pending:hover {
    background: #E67E22;
}

/* Recent Sales */
.recent-sales-card {
    grid-column: span 2;
}

.recent-sales-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.recent-sale-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem 1rem;
    background: var(--pos-bg, #F1F3F6);
    border-radius: 10px;
}

.sale-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.sale-ref {
    font-weight: 600;
    color: var(--pos-text, #2D3748);
}

.sale-caissier {
    font-size: 0.85rem;
    color: var(--pos-text-muted, #718096);
}

.sale-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.sale-amount {
    font-weight: 600;
    color: var(--pos-text, #2D3748);
}

.sale-status {
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.empty-recent {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 2rem;
    color: var(--pos-text-muted, #718096);
}

.empty-recent i {
    font-size: 2rem;
    opacity: 0.5;
}

/* Existing styles from previous version */
.content-header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.header-left .result-count {
    font-size: 0.95rem;
    color: var(--pos-text-muted, #718096);
}

.btn-action.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-action.btn-primary:hover {
    background: #333;
}

/* Filters Card */
.filters-card {
    background: #fff;
    border-radius: 15px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.filters-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    min-width: 180px;
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-filter {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-filter.btn-search {
    background: #000;
    color: #fff;
}

.btn-filter.btn-search:hover {
    background: #333;
}

.btn-filter.btn-reset {
    background: var(--pos-bg, #F1F3F6);
    color: var(--pos-text, #2D3748);
}

.btn-filter.btn-reset:hover {
    background: var(--pos-border, #E2E8F0);
}

/* Table Card */
.table-card {
    background: #fff;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.table-responsive {
    overflow-x: auto;
}

.pos-table {
    width: 100%;
    border-collapse: collapse;
}

.pos-table th,
.pos-table td {
    padding: 1rem 1.25rem;
    text-align: left;
}

.pos-table th {
    background: var(--pos-bg, #F1F3F6);
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--pos-text-muted, #718096);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.pos-table tbody tr {
    border-bottom: 1px solid var(--pos-border, #E2E8F0);
}

.pos-table tbody tr:hover {
    background: rgba(0, 0, 0, 0.02);
}

.cell-main {
    font-weight: 500;
    color: var(--pos-text, #2D3748);
}

.cell-muted {
    color: var(--pos-text-muted, #718096);
    font-size: 0.9rem;
}

.ref-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    background: var(--pos-bg, #F1F3F6);
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
}

.amount-cell {
    font-weight: 600;
    color: var(--pos-text, #2D3748);
}

.mode-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    background: rgba(52, 152, 219, 0.1);
    color: #3498DB;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 0.35rem 0.85rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-success {
    background: rgba(46, 204, 113, 0.15);
    color: #27AE60;
}

.badge-warning {
    background: rgba(243, 156, 18, 0.15);
    color: #F39C12;
}

.badge-danger {
    background: rgba(231, 76, 60, 0.15);
    color: #E74C3C;
}

.badge-info {
    background: rgba(52, 152, 219, 0.15);
    color: #3498DB;
}

.badge-secondary {
    background: rgba(108, 117, 125, 0.15);
    color: #6C757D;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.btn-table-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: var(--pos-bg, #F1F3F6);
    border: none;
    border-radius: 8px;
    color: var(--pos-text, #2D3748);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-table-action:hover {
    background: #000;
    color: #fff;
}

.table-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--pos-border, #E2E8F0);
}

.empty-state {
    padding: 3rem;
    text-align: center;
}

.empty-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    color: var(--pos-text-muted, #718096);
}

.empty-content i {
    font-size: 2.5rem;
    opacity: 0.5;
}

/* Server Error */
.server-error {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    margin: 1rem 1.5rem;
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid rgba(231, 76, 60, 0.3);
    border-radius: 12px;
    color: #E74C3C;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-container {
    background: #fff;
    border-radius: 20px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 1px solid var(--pos-border, #E2E8F0);
}

.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.modal-icon-primary {
    background: rgba(0, 0, 0, 0.08);
    color: #000;
}

.modal-title {
    flex: 1;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.modal-close {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--pos-bg, #F1F3F6);
    border: none;
    border-radius: 10px;
    color: var(--pos-text-muted, #718096);
    cursor: pointer;
    transition: all 0.2s;
}

.modal-close:hover {
    background: var(--pos-border, #E2E8F0);
    color: var(--pos-text, #2D3748);
}

.modal-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--pos-text, #2D3748);
}

.text-danger {
    color: #E74C3C;
}

.attribution-summary {
    background: rgba(52, 152, 219, 0.1);
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1rem;
}

.summary-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--pos-info, #3498DB);
    margin-bottom: 0.75rem;
}

.summary-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.summary-list li {
    padding: 0.35rem 0;
    font-size: 0.9rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.btn-modal {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-modal.btn-secondary {
    background: var(--pos-bg, #F1F3F6);
    color: var(--pos-text, #2D3748);
}

.btn-modal.btn-secondary:hover {
    background: var(--pos-border, #E2E8F0);
}

.btn-modal.btn-primary {
    background: #000;
    color: #fff;
}

.btn-modal.btn-primary:hover:not(:disabled) {
    background: #333;
}

.btn-modal:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Modal Large (pour détails session) */
.modal-large {
    max-width: 700px;
}

.modal-large .modal-header {
    flex-wrap: wrap;
    gap: 0.75rem;
}

.modal-large .modal-header .status-badge {
    margin-left: auto;
    margin-right: 0.5rem;
}

/* Section Title in Modal */
.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--pos-text, #2D3748);
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--pos-border, #E2E8F0);
}

.section-title i {
    color: var(--pos-text-muted, #718096);
}

/* Info Section */
.info-section {
    margin-bottom: 1.5rem;
}

.info-section:last-of-type {
    margin-bottom: 0;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem;
    background: var(--pos-bg, #F1F3F6);
    border-radius: 12px;
}

.info-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
    color: #fff;
    border-radius: 10px;
    font-size: 1rem;
    flex-shrink: 0;
}

.info-content {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.info-label {
    font-size: 0.8rem;
    color: var(--pos-text-muted, #718096);
}

.info-value {
    font-weight: 600;
    color: var(--pos-text, #2D3748);
    word-break: break-word;
}

/* Financial Grid */
.financial-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.financial-item {
    padding: 1rem;
    background: var(--pos-bg, #F1F3F6);
    border-radius: 12px;
    text-align: center;
}

.financial-item.highlight {
    background: rgba(0, 0, 0, 0.08);
    border: 2px solid #000;
}

.financial-label {
    display: block;
    font-size: 0.85rem;
    color: var(--pos-text-muted, #718096);
    margin-bottom: 0.5rem;
}

.financial-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--pos-text, #2D3748);
}

.text-success { color: var(--pos-success, #2ECC71) !important; }
.text-danger { color: var(--pos-danger, #E74C3C) !important; }

/* Session Modal Actions */
.session-modal-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--pos-border, #E2E8F0);
    justify-content: center;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-action.btn-success {
    background: var(--pos-success, #2ECC71);
    color: #fff;
}

.btn-action.btn-success:hover {
    background: #27AE60;
    transform: translateY(-2px);
}

.btn-action.btn-danger {
    background: var(--pos-danger, #E74C3C);
    color: #fff;
}

.btn-action.btn-danger:hover {
    background: #C0392B;
    transform: translateY(-2px);
}

.btn-action.btn-secondary {
    background: var(--pos-bg, #F1F3F6);
    color: var(--pos-text, #2D3748);
}

.btn-action.btn-secondary:hover {
    background: var(--pos-border, #E2E8F0);
}

.btn-action.btn-warning {
    background: var(--pos-warning, #F39C12);
    color: #fff;
}

.btn-action.btn-warning:hover {
    background: #E67E22;
    transform: translateY(-2px);
}

.btn-action.btn-primary {
    background: #000;
    color: #fff;
}

.btn-action.btn-primary:hover {
    background: #333;
    transform: translateY(-2px);
}

/* Modal message */
.modal-message {
    text-align: center;
    color: var(--pos-text, #2D3748);
    margin-bottom: 1.5rem;
}

/* Modal icon success/danger */
.modal-icon-success {
    background: rgba(46, 204, 113, 0.15);
    color: #2ECC71;
}

.modal-icon-danger {
    background: rgba(231, 76, 60, 0.15);
    color: #E74C3C;
}

/* Form control */
.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--pos-border, #E2E8F0);
    border-radius: 12px;
    font-size: 0.95rem;
    transition: border-color 0.2s;
    resize: vertical;
}

.form-control:focus {
    outline: none;
    border-color: #000;
}

/* Button success in modal */
.btn-modal.btn-success {
    background: var(--pos-success, #2ECC71);
    color: #fff;
}

.btn-modal.btn-success:hover {
    background: #27AE60;
}

.btn-modal.btn-danger {
    background: var(--pos-danger, #E74C3C);
    color: #fff;
}

.btn-modal.btn-danger:hover:not(:disabled) {
    background: #C0392B;
}

.btn-modal.btn-warning {
    background: var(--pos-warning, #F39C12);
    color: #fff;
}

.btn-modal.btn-warning:hover:not(:disabled) {
    background: #E67E22;
}

/* Modal icon warning */
.modal-icon-warning {
    background: rgba(243, 156, 18, 0.15);
    color: #F39C12;
}

/* Refund vente info */
.refund-vente-info {
    background: var(--pos-bg, #F1F3F6);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.refund-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.refund-info-row:not(:last-child) {
    border-bottom: 1px solid var(--pos-border, #E2E8F0);
}

.refund-info-label {
    color: var(--pos-text-muted, #718096);
    font-size: 0.9rem;
}

.refund-info-value {
    font-weight: 600;
    color: var(--pos-text, #2D3748);
}

.refund-info-value.highlight {
    color: #000;
    font-size: 1.1rem;
}

.refund-info-value.text-warning {
    color: var(--pos-warning, #F39C12);
}

/* Form hint */
.form-hint {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: var(--pos-text-muted, #718096);
}

/* Modal Transition */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
    transform: scale(0.9);
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .manager-dashboard {
        padding: 1rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .tabs-navigation {
        flex-wrap: wrap;
    }

    .tab-btn {
        flex: 1 1 auto;
        min-width: calc(50% - 0.25rem);
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .recent-sales-card {
        grid-column: span 1;
    }

    .actions-grid {
        grid-template-columns: 1fr;
    }

    .filters-form {
        flex-direction: column;
    }

    .filter-group {
        min-width: 100%;
    }

    .filter-actions {
        width: 100%;
        justify-content: flex-end;
    }

    /* Modal responsive */
    .modal-large {
        max-width: 95%;
    }

    .info-grid,
    .financial-grid {
        grid-template-columns: 1fr;
    }

    .session-modal-actions {
        flex-direction: column;
    }

    .session-modal-actions .btn-action {
        width: 100%;
        justify-content: center;
    }
}
</style>
