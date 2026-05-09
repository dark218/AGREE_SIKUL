<script setup>
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    conversations: { type: Array, default: () => [] },
    currentUserId: { type: Number, required: true },
});
</script>

<template>
    <div class="ac-page">
        <div class="ac-header">
            <div class="ac-header-left">
                <div class="ac-header-icon"><i class="fas fa-comments"></i></div>
                <div>
                    <h1 class="ac-title">Chat - Messages des apprenants</h1>
                    <p class="ac-subtitle">{{ conversations.length }} conversation(s)</p>
                </div>
            </div>
        </div>

        <div class="ac-card">
            <div v-if="conversations.length" class="conv-list">
                <Link
                    v-for="conv in conversations"
                    :key="conv.user_id"
                    :href="route('admin-chat.show', conv.user_id)"
                    class="conv-item"
                >
                    <div class="conv-avatar">{{ conv.user_initials }}</div>
                    <div class="conv-info">
                        <div class="conv-name">{{ conv.user_nom }}</div>
                        <div class="conv-email">{{ conv.user_email }}</div>
                        <div class="conv-last">{{ conv.last_message }}</div>
                    </div>
                    <div class="conv-meta">
                        <div class="conv-time">{{ conv.last_time }}</div>
                        <div v-if="conv.unread_count > 0" class="conv-badge">{{ conv.unread_count }}</div>
                    </div>
                </Link>
            </div>

            <div v-else class="ac-empty">
                <i class="fas fa-inbox"></i>
                <p>Aucune conversation</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ac-page { padding: 0; }

.ac-header { background: linear-gradient(135deg, #E5590C, #c24a0a); padding: 28px; border-radius: 16px; margin-bottom: 24px; color: white; display: flex; align-items: center; }
.ac-header-left { display: flex; align-items: center; gap: 16px; }
.ac-header-icon { width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 24px; }
.ac-title { font-size: 22px; font-weight: 800; margin: 0; }
.ac-subtitle { font-size: 13px; opacity: 0.85; margin: 4px 0 0; }

.ac-card { background: white; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; }

.conv-list { display: flex; flex-direction: column; }
.conv-item { display: flex; align-items: center; gap: 14px; padding: 16px 24px; border-bottom: 1px solid #f1f5f9; text-decoration: none; transition: all 0.2s; }
.conv-item:hover { background: #fff7ed; }

.conv-avatar { width: 46px; height: 46px; border-radius: 50%; background: linear-gradient(135deg, #8B5CF6, #6D28D9); color: white; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; flex-shrink: 0; }
.conv-info { flex: 1; min-width: 0; }
.conv-name { font-size: 14px; font-weight: 700; color: #1e293b; }
.conv-email { font-size: 11px; color: #94a3b8; }
.conv-last { font-size: 12px; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.conv-meta { text-align: right; flex-shrink: 0; }
.conv-time { font-size: 11px; color: #94a3b8; }
.conv-badge { background: #E5590C; color: white; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px; margin-top: 4px; display: inline-block; }

.ac-empty { text-align: center; padding: 60px; color: #94a3b8; }
.ac-empty i { font-size: 48px; display: block; margin-bottom: 12px; }
</style>
