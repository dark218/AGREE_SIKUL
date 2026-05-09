<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    notifications: { type: Object, default: () => ({ data: [] }) },
    unreadCount: { type: Number, default: 0 },
});

const filterMode = ref('all');

function markAsRead(id) {
    router.post(route('apprenant.notifications.read', id), {}, { preserveScroll: true });
}

function markAllAsRead() {
    router.post(route('apprenant.notifications.mark-all-read'), {}, { preserveScroll: true });
}

function filteredNotifs() {
    const list = props.notifications?.data || [];
    if (filterMode.value === 'unread') return list.filter(n => !n.is_read);
    if (filterMode.value === 'read') return list.filter(n => n.is_read);
    return list;
}

function timeAgo(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return "À l'instant";
    if (diff < 3600) return Math.floor(diff / 60) + ' min';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    if (diff < 604800) return Math.floor(diff / 86400) + 'j';
    return date.toLocaleDateString('fr-FR');
}
</script>

<template>
    <div class="eleve-dashboard">
        <!-- Header -->
        <div class="ed-header" style="background: linear-gradient(135deg, #8B5CF6, #6D28D9);">
            <div class="ed-header-left">
                <div class="ed-avatar"><i class="bx bxs-bell-ring"></i></div>
                <div>
                    <h1 class="ed-title">Mes Notifications</h1>
                    <p class="ed-subtitle">{{ unreadCount }} non lue{{ unreadCount > 1 ? 's' : '' }} sur {{ (notifications?.data || []).length }} notification{{ (notifications?.data || []).length > 1 ? 's' : '' }}</p>
                </div>
            </div>
            <button v-if="unreadCount > 0" class="header-action-btn" @click="markAllAsRead">
                <i class="bx bx-check-double"></i> Tout marquer comme lu
            </button>
        </div>

        <div class="ed-content">
            <!-- Stats -->
            <div class="ed-stats-row">
                <div class="ed-stat" style="border-left: 4px solid #8B5CF6;">
                    <div class="ed-stat-icon" style="background:#F3EEFF;"><i class="bx bxs-bell" style="color:#8B5CF6;"></i></div>
                    <div><div class="ed-stat-val" style="color:#8B5CF6;">{{ (notifications?.data || []).length }}</div><div class="ed-stat-lbl">Total</div></div>
                </div>
                <div class="ed-stat" style="border-left: 4px solid #E5590C;">
                    <div class="ed-stat-icon" style="background:#FFF4ED;"><i class="bx bxs-bell-ring" style="color:#E5590C;"></i></div>
                    <div><div class="ed-stat-val" style="color:#E5590C;">{{ unreadCount }}</div><div class="ed-stat-lbl">Non lues</div></div>
                </div>
                <div class="ed-stat" style="border-left: 4px solid #0FBCAF;">
                    <div class="ed-stat-icon" style="background:#E6FAF8;"><i class="bx bxs-check-circle" style="color:#0FBCAF;"></i></div>
                    <div><div class="ed-stat-val" style="color:#0FBCAF;">{{ (notifications?.data || []).length - unreadCount }}</div><div class="ed-stat-lbl">Lues</div></div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="notif-filters">
                <button class="notif-filter" :class="{ active: filterMode === 'all' }" @click="filterMode = 'all'">
                    <i class="bx bx-list-ul"></i> Toutes
                </button>
                <button class="notif-filter" :class="{ active: filterMode === 'unread' }" @click="filterMode = 'unread'">
                    <i class="bx bxs-bell-ring"></i> Non lues
                    <span v-if="unreadCount" class="filter-badge">{{ unreadCount }}</span>
                </button>
                <button class="notif-filter" :class="{ active: filterMode === 'read' }" @click="filterMode = 'read'">
                    <i class="bx bxs-check-circle"></i> Lues
                </button>
            </div>

            <!-- Liste des notifications -->
            <div class="ed-card">
                <div class="ed-card-header"><i class="bx bxs-inbox"></i><h3>Notifications</h3></div>

                <div v-if="filteredNotifs().length" class="notif-list">
                    <div
                        v-for="(notif, idx) in filteredNotifs()"
                        :key="notif.id"
                        class="notif-item"
                        :class="{ 'notif-unread': !notif.is_read }"
                        :style="{ '--delay': idx * 0.04 + 's' }"
                    >
                        <!-- Indicateur non-lu -->
                        <div class="notif-indicator">
                            <div v-if="!notif.is_read" class="notif-dot-pulse"></div>
                            <div class="notif-icon-wrap" :class="notif.is_read ? 'icon-read' : 'icon-unread'">
                                <i class="bx" :class="notif.is_read ? 'bxs-bell' : 'bxs-bell-ring'"></i>
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="notif-body">
                            <div class="notif-title-row">
                                <h4 class="notif-title">{{ notif.title }}</h4>
                                <span class="notif-time"><i class="bx bx-time-five"></i> {{ timeAgo(notif.created_at) }}</span>
                            </div>
                            <p class="notif-message">{{ notif.body }}</p>
                        </div>

                        <!-- Action -->
                        <div class="notif-action">
                            <button v-if="!notif.is_read" class="notif-read-btn" @click="markAsRead(notif.id)" title="Marquer comme lu">
                                <i class="bx bx-check"></i>
                            </button>
                            <span v-else class="notif-read-done"><i class="bx bxs-check-circle"></i></span>
                        </div>
                    </div>
                </div>

                <div v-else class="ed-empty">
                    <i class="bx bx-bell-off" style="font-size:48px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                    <template v-if="filterMode === 'unread'">Aucune notification non lue</template>
                    <template v-else-if="filterMode === 'read'">Aucune notification lue</template>
                    <template v-else>Aucune notification</template>
                </div>

                <!-- Pagination -->
                <div v-if="notifications?.links && notifications.last_page > 1" class="notif-pagination">
                    <template v-for="link in notifications.links" :key="link.label">
                        <button
                            v-if="link.url"
                            class="page-btn"
                            :class="{ 'page-active': link.active }"
                            @click="router.get(link.url)"
                            v-html="link.label"
                        ></button>
                        <span v-else class="page-disabled" v-html="link.label"></span>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ===== Header ===== */
.ed-header { padding: 28px; border-radius: 16px; margin-bottom: 24px; color: white; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.ed-header-left { display: flex; align-items: center; gap: 16px; }
.ed-avatar { width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; }
.ed-title { font-size: 22px; font-weight: 800; margin: 0; }
.ed-subtitle { font-size: 13px; opacity: 0.85; margin: 4px 0 0; }

.header-action-btn { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 12px; cursor: pointer; font-size: 13px; font-weight: 700; font-family: inherit; transition: all 0.25s; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(6px); }
.header-action-btn:hover { background: rgba(255,255,255,0.35); transform: translateY(-1px); }

.ed-content { display: flex; flex-direction: column; gap: 20px; }

/* ===== Stats ===== */
.ed-stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.ed-stat { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px; transition: transform 0.2s, box-shadow 0.2s; }
.ed-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.ed-stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.ed-stat-icon i { font-size: 22px; }
.ed-stat-val { font-size: 22px; font-weight: 800; }
.ed-stat-lbl { font-size: 12px; color: #94a3b8; }

/* ===== Filtres ===== */
.notif-filters { display: flex; gap: 10px; flex-wrap: wrap; }
.notif-filter { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 12px; border: 2px solid #e2e8f0; background: white; color: #64748b; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.25s; font-family: inherit; }
.notif-filter:hover { border-color: #8B5CF6; color: #8B5CF6; background: #faf5ff; }
.notif-filter.active { background: #8B5CF6; color: white; border-color: #8B5CF6; box-shadow: 0 4px 14px rgba(139,92,246,0.3); }
.notif-filter.active .filter-badge { background: rgba(255,255,255,0.3); color: white; }
.filter-badge { background: #E5590C; color: white; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; min-width: 20px; text-align: center; transition: all 0.25s; }

/* ===== Card ===== */
.ed-card { background: white; border-radius: 14px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.ed-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9; }
.ed-card-header i { font-size: 20px; color: #8B5CF6; }
.ed-card-header h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; }

/* ===== Liste notifications ===== */
.notif-list { display: flex; flex-direction: column; gap: 4px; }

.notif-item { display: flex; align-items: flex-start; gap: 14px; padding: 16px; border-radius: 12px; transition: all 0.2s; animation: fadeSlideIn 0.3s ease both; animation-delay: var(--delay, 0s); }
.notif-item:hover { background: #f8fafc; }
.notif-unread { background: #faf5ff; border-left: 3px solid #8B5CF6; }
.notif-unread:hover { background: #f3eeff; }

/* Indicateur */
.notif-indicator { position: relative; flex-shrink: 0; }
.notif-dot-pulse { position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; border-radius: 50%; background: #E5590C; z-index: 1; animation: pulse 2s infinite; }
.notif-icon-wrap { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.icon-unread { background: linear-gradient(135deg, #8B5CF6, #7c3aed); color: white; }
.icon-read { background: #f1f5f9; color: #94a3b8; }

/* Contenu */
.notif-body { flex: 1; min-width: 0; }
.notif-title-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 4px; }
.notif-title { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.3; }
.notif-time { font-size: 11px; color: #94a3b8; display: flex; align-items: center; gap: 4px; white-space: nowrap; flex-shrink: 0; }
.notif-message { font-size: 13px; color: #64748b; margin: 0; line-height: 1.5; }

/* Action */
.notif-action { flex-shrink: 0; }
.notif-read-btn { width: 38px; height: 38px; border-radius: 50%; border: 2px solid #8B5CF6; background: white; color: #8B5CF6; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; transition: all 0.25s; }
.notif-read-btn:hover { background: #8B5CF6; color: white; transform: scale(1.1); box-shadow: 0 4px 14px rgba(139,92,246,0.35); }
.notif-read-done { color: #0FBCAF; font-size: 20px; }

/* ===== Pagination ===== */
.notif-pagination { display: flex; gap: 6px; justify-content: center; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; }
.page-btn { padding: 8px 14px; border: 2px solid #e2e8f0; border-radius: 10px; background: white; cursor: pointer; font-size: 12px; font-weight: 600; color: #64748b; transition: all 0.2s; font-family: inherit; }
.page-btn:hover { background: #faf5ff; border-color: #8B5CF6; color: #8B5CF6; }
.page-active { background: #8B5CF6; color: white; border-color: #8B5CF6; }
.page-disabled { padding: 8px 14px; font-size: 12px; color: #cbd5e1; }

/* ===== Empty ===== */
.ed-empty { text-align: center; padding: 40px; color: #94a3b8; font-size: 14px; }

/* ===== Animations ===== */
@keyframes fadeSlideIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.4); opacity: 0.6; }
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
    .ed-stats-row { grid-template-columns: 1fr; }
    .notif-filters { gap: 6px; }
    .notif-filter { padding: 8px 12px; font-size: 12px; }
    .notif-title-row { flex-direction: column; gap: 2px; }
    .notif-item { gap: 10px; }
}
</style>
