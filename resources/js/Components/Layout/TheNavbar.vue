<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useTimeAgo } from '@/Composables/useTimeAgo';
import { useLocale } from '@/Composables/useLocale';
import NotificationModal from '@/Components/Common/NotificationModal.vue';

const page = usePage();
const sidebarState = inject('sidebarState');
const { formatTimeAgo } = useTimeAgo();
const { currentLocale, changeLocale, t } = useLocale();

// Refs
const notificationRef = ref(null);
const languageRef = ref(null);

// State
const showNotifications = ref(false);
const showNotificationModal = ref(false);
const selectedNotification = ref(null);
const showLanguageDropdown = ref(false);
const showUserDropdown = ref(false);
const userDropdownRef = ref(null);

// Logout
function handleLogout() {
    showUserDropdown.value = false;
    sessionStorage.removeItem('login');
    sessionStorage.removeItem('home');
    router.post(route('logout'));
}

// Close user dropdown on click outside
function handleClickOutsideUser(e) {
    if (userDropdownRef.value && !userDropdownRef.value.contains(e.target)) {
        showUserDropdown.value = false;
    }
}
// console.log(page.props.menu);
// Computed depuis les props Laravel
const currentMenu = computed(() => page.props.menu || 'dashboard');
const notifications = computed(() => page.props.notifications || []);
const unreadCount = computed(() => page.props.unreadNotificationsCount || 0);
const userImage = computed(() => page.props.userimages || null);
const user = computed(() => page.props.auth?.user);
const userProfileLink = computed(() => {
    return user.value?.id ? route('administration.users.editprofile', user.value.id) : null;
});

// Languages
const languages = [
    { code: 'fr', label: 'Français', flag: `/assets/images/flags/french_flag.jpg` },
    { code: 'en', label: 'English', flag: `/assets/images/flags/us_flag.jpg` }
];

const currentLanguageLabel = computed(() => {
    const lang = languages.find(l => l.code === currentLocale.value);
    return lang?.label || 'Français';
});

// Methods
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

function selectLanguage(langCode) {
    changeLocale(langCode);
    showLanguageDropdown.value = false;
}

// Click outside handler
function handleClickOutside(event) {
    if (notificationRef.value && !notificationRef.value.contains(event.target)) {
        showNotifications.value = false;
    }
    if (languageRef.value && !languageRef.value.contains(event.target)) {
        showLanguageDropdown.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('click', handleClickOutsideUser);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('click', handleClickOutsideUser);
});
</script>

<template>
    <header class="navbar">
        <div class="navbar-left">
            <!-- Bouton toggle sidebar desktop -->
            <button
                v-if="sidebarState"
                class="sidebar-toggle-desktop"
                @click="sidebarState.toggle"
                aria-label="Toggle sidebar"
            >
                <i class="fas fa-exchange-alt"></i>
            </button>

            <!-- Bouton menu mobile -->
            <button
                v-if="sidebarState"
                class="navbar-toggle"
                @click="sidebarState.toggleMobile"
                aria-label="Toggle menu"
            >
                <i class="fas fa-exchange-alt"></i>
            </button>

            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <span class="breadcrumb-text">{{ t('home') }}</span>
                <i class="fas fa-chevron-right"></i>
                <span class="breadcrumb-text">{{ page.props.title }}</span>

            </nav>
        </div>

        <!-- Search Bar -->
        <div class="navbar-search">
            <input
                type="text"
                placeholder="Rechercher..."
                class="search-input"
            />
            <i class="fas fa-search search-icon"></i>
        </div>

        <div class="navbar-right">
            <!-- User Info Mini -->
            <div v-if="user" class="user-info-mini">
                <div class="user-info-text">
                    <p class="user-name">{{ user.name }}</p>
                    <p class="user-email">{{ user.email }}</p>
                </div>
            </div>

            <!-- Sélecteur de langue -->
            <div class="language-wrapper" ref="languageRef">
                <button
                    class="language-toggle-btn"
                    @click.stop="showLanguageDropdown = !showLanguageDropdown"
                >
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

            <!-- User avatar with dropdown -->
            <div class="user-dropdown-wrapper" ref="userDropdownRef">
                <button class="user-avatar-btn" @click="showUserDropdown = !showUserDropdown">
                    <img
                        :src="userImage ? `/images/${userImage}` : `/backend/images/default/profile-default.webp`"
                        alt="Profile"
                    />
                </button>
                <Transition name="dropdown">
                    <div v-if="showUserDropdown" class="user-dropdown">
                        <div class="user-dropdown-header">
                            <img
                                :src="userImage ? `/images/${userImage}` : `/backend/images/default/profile-default.webp`"
                                class="user-dropdown-avatar"
                            />
                            <div class="user-dropdown-info">
                                <strong>{{ user?.prenoms || '' }} {{ user?.nom || '' }}</strong>
                                <small>{{ user?.email || '' }}</small>
                            </div>
                        </div>
                        <div class="user-dropdown-divider"></div>
                        <Link v-if="userProfileLink" :href="userProfileLink" class="user-dropdown-item" @click="showUserDropdown = false">
                            <i class="fa fa-user"></i>
                            <span>Mon Profil</span>
                        </Link>
                        <button class="user-dropdown-item user-dropdown-logout" @click="handleLogout">
                            <i class="fa fa-sign-out-alt"></i>
                            <span>Déconnexion</span>
                        </button>
                    </div>
                </Transition>
            </div>
        </div>
    </header>

    <!-- Modal notification -->
    <NotificationModal
        :is-open="showNotificationModal"
        :notification="selectedNotification"
        @close="showNotificationModal = false"
        @marked-as-read="handleNotificationMarkedAsRead"
    />
</template>

<style scoped>
.navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 70px;
    padding: 0 24px;
    margin-left: 20px;
    border-radius: 10px;
    background: #0B5697;
    border-bottom: 1px solid #0B5697;
    position: sticky;
    top: 0;
    z-index: 100;
}

.navbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

/* Search Bar */
.navbar-search {
    position: relative;
    display: flex;
    align-items: center;
    width: 260px;
    margin: 0 20px;
}

.search-input {
    width: 100%;
    height: 40px;
    padding: 0 40px 0 16px;
    border: 1px solid #E2E8F0;
    border-radius: 999px;
    font-size: 0.875rem;
    color: #2D3748;
    background: #F7FAFC;
    transition: all 0.3s ease;
    outline: none;
}

.search-input::placeholder {
    color: #A0AEC0;
}

.search-input:focus {
    background: #fff;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.search-icon {
    position: absolute;
    right: 14px;
    color: #718096;
    font-size: 14px;
    pointer-events: none;
}


.sidebar-toggle-desktop:hover {
   
}

.sidebar-toggle-desktop i {
    font-size: 18px;
}

.navbar-toggle {
    display: none;
    padding: 8px;
    border-radius: 6px;
    color: #718096;
    background: none;
    border: none;
    cursor: pointer;
}

.navbar-toggle:hover {
    background: #EEF1F6;
    color: #2D3748;
}

/* Breadcrumb */
.breadcrumb {
    padding-top: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
}

.breadcrumb-text {
    font-weight: 500;
    color: #fff;
}

/* User Info Mini */
.user-info-mini {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 16px;
    border-right: 1px solid rgba(255, 255, 255, 0.2);
}

.user-info-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
    text-align: right;
}

.user-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    margin: 0;
}

.user-email {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

/* Navbar right */
.navbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Language selector */
.language-wrapper {
    position: relative;
}

.language-toggle-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    font-size: 0.875rem;
    color: #fff;
    font-weight: 500;
    border-radius: 12px;
    transition: all 0.2s ease;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    cursor: pointer;
}

.language-toggle-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.language-toggle-btn:active {
    transform: translateY(0);
}

.language-chevron {
    font-size: 10px;
    transition: transform 0.2s ease;
    color: #718096;
}

.language-chevron.rotated {
    transform: rotate(180deg);
}

.flag-icon {
    width: 20px;
    height: 14px;
    object-fit: cover;
    border-radius: 2px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.language-label {
    font-size: 0.875rem;
}

.language-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    min-width: 160px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.2);
    border: 1px solid #E2E8F0;
    overflow: hidden;
    z-index: 100;
}

.language-option {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 12px 16px;
    font-size: 0.875rem;
    color: #2D3748;
    background: none;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
}

.language-option:hover {
    background: #F7FAFC;
}

.language-option.active {
    background: #EBF8FF;
    color: #3182CE;
}

.language-option i {
    margin-left: auto;
    font-size: 12px;
    color: #3182CE;
}

/* Notifications */
.notification-wrapper {
    position: relative;
}

.notification-btn {
    position: relative;
    padding: 10px;
    border-radius: 12px;
    color: #fff;
    transition: all 0.15s ease;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    cursor: pointer;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
}

.notification-badge {
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

.notification-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    width: 360px;
    max-height: 450px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid #E2E8F0;
    overflow: hidden;
    z-index: 100;
}

.dropdown-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid #E2E8F0;
}

.dropdown-header h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #2D3748;
    margin: 0;
}

.notification-actions {
    display: flex;
    gap: 8px;
}

.notification-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    background: #F7FAFC;
    color: #718096;
    cursor: pointer;
    transition: all 0.2s ease;
}

.notification-action-btn:hover {
    background: #EEF1F6;
    color: #2D3748;
}

.notification-action-btn:active {
    transform: scale(0.95);
}

.notification-list {
    max-height: 350px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    gap: 12px;
    padding: 16px;
    border-bottom: 1px solid #E2E8F0;
    cursor: pointer;
    transition: all 0.15s ease;
}

.notification-item:hover {
    background: #F8F9FC;
}

.notification-item.unread {
    background: rgba(52, 152, 219, 0.05);
}

.notification-item.unread .notification-title {
    color: #E74C3C;
    font-weight: 600;
}

.notification-avatar {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
}

.notification-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: #2D3748;
    margin: 0 0 2px 0;
}

.notification-body {
    font-size: 0.75rem;
    color: #718096;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0 0 4px 0;
}

.notification-time {
    font-size: 0.75rem;
    color: #A0AEC0;
}

.notification-empty {
    padding: 48px;
    text-align: center;
    color: #A0AEC0;
}

.notification-empty i {
    font-size: 48px;
    opacity: 0.5;
    display: block;
    margin-bottom: 8px;
}

/* User avatar */
.user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: all 0.2s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.user-avatar:hover {
    border-color: #E5590C;
    transform: scale(1.05);
    box-shadow: 0 3px 12px rgba(229, 89, 12, 0.25);
}

.user-avatar img, .user-avatar-btn img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* User dropdown */
.user-dropdown-wrapper { position: relative; }

.user-avatar-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: all 0.2s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    background: none;
    padding: 0;
}

.user-avatar-btn:hover {
    border-color: #E5590C;
    transform: scale(1.05);
    box-shadow: 0 3px 12px rgba(229, 89, 12, 0.25);
}

.user-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 260px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border: 1px solid #f0f0f0;
    z-index: 9999;
    overflow: hidden;
}

.user-dropdown-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #fafafa;
}

.user-dropdown-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #E5590C;
}

.user-dropdown-info {
    flex: 1;
    min-width: 0;
}

.user-dropdown-info strong {
    display: block;
    font-size: 13px;
    color: #0c1e36;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-dropdown-info small {
    font-size: 11px;
    color: #94a3b8;
}

.user-dropdown-divider {
    height: 1px;
    background: #f0f0f0;
}

.user-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    font-size: 13px;
    font-weight: 500;
    color: #333;
    text-decoration: none;
    transition: all 0.15s;
    cursor: pointer;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    font-family: inherit;
}

.user-dropdown-item i {
    width: 18px;
    text-align: center;
    color: #E5590C;
    font-size: 14px;
}

.user-dropdown-item:hover {
    background: #fef6f0;
    color: #E5590C;
}

.user-dropdown-logout:hover {
    background: #fef2f2;
    color: #dc3545;
}

.user-dropdown-logout:hover i {
    color: #dc3545;
}

/* Dropdown transition */
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Responsive - Tablettes */
@media (max-width: 992px) {
    .navbar {
        padding: 0 16px;
        margin-left: 10px;
        margin-right: 10px;
    }

    .navbar-right {
        gap: 8px;
    }

    .language-toggle-btn {
        padding: 6px 10px;
    }

    .language-label {
        display: none;
    }
}

/* Responsive - Mobile */
@media (max-width: 768px) {
    .navbar {
        padding: 0 12px;
        margin-left: 0;
        margin-right: 0;
        border-radius: 0;
        height: 60px;
    }

    .sidebar-toggle-desktop {
        display: none;
    }

    .navbar-toggle {
        display: block;
    }

    .breadcrumb {
        display: none;
    }

    .navbar-right {
        gap: 6px;
    }

    .notification-dropdown {
        width: calc(100vw - 32px);
        right: -60px;
    }

    .language-toggle-btn {
        padding: 6px 8px;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
    }

    .notification-btn {
        padding: 6px;
        font-size: 20px;
    }
}

/* Responsive - Très petit écran */
@media (max-width: 480px) {
    .navbar {
        padding: 0 10px;
        height: 56px;
    }

    .navbar-right {
        gap: 4px;
    }

    .language-toggle-btn {
        padding: 4px 6px;
    }

    .flag-icon {
        width: 18px;
        height: 12px;
    }

    .user-avatar {
        width: 32px;
        height: 32px;
    }

    .notification-btn {
        padding: 4px;
        font-size: 18px;
    }

    .notification-dropdown {
        right: -100px;
        width: calc(100vw - 20px);
    }

    .language-dropdown {
        right: -50px;
    }
}
</style>
