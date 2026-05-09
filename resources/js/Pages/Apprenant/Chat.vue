<script setup>
import { ref, nextTick, onMounted, onUnmounted, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    admins: { type: Array, default: () => [] },
    messages: { type: Array, default: () => [] },
    currentUserId: { type: Number, required: true },
});

const selectedAdmin = ref(props.admins.length > 0 ? props.admins[0] : null);
const chatMessages = ref([...props.messages]);
const messagesContainer = ref(null);
let pollInterval = null;

const form = useForm({
    receiver_id: selectedAdmin.value?.id || null,
    message: '',
});

// Filtrer les messages pour l'admin selectionne
const filteredMessages = computed(() => {
    if (!selectedAdmin.value) return [];
    return chatMessages.value.filter(m =>
        (m.sender_id === props.currentUserId && m.receiver_id === selectedAdmin.value.id) ||
        (m.sender_id === selectedAdmin.value.id && m.receiver_id === props.currentUserId)
    );
});

// Compter les messages non lus par admin
function unreadCountFor(adminId) {
    return chatMessages.value.filter(m =>
        m.sender_id === adminId &&
        m.receiver_id === props.currentUserId &&
        !m.is_read
    ).length;
}

function selectAdmin(admin) {
    selectedAdmin.value = admin;
    form.receiver_id = admin.id;
    scrollToBottom();
}

function sendMessage() {
    if (!form.message.trim() || !form.receiver_id) return;

    form.post(route('apprenant.chat.send'), {
        preserveScroll: true,
        onSuccess: () => {
            form.message = '';
            refreshMessages();
        },
    });
}

async function refreshMessages() {
    try {
        const response = await fetch(route('apprenant.chat.messages'));
        const data = await response.json();
        chatMessages.value = data;
        await nextTick();
        scrollToBottom();
    } catch (e) {
        // silently ignore polling errors
    }
}

function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
}

function handleKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

onMounted(() => {
    scrollToBottom();
    // Poll every 10 seconds for new messages
    pollInterval = setInterval(refreshMessages, 10000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <div class="chat-page">
        <!-- Header -->
        <div class="chat-header">
            <div class="chat-header-left">
                <div class="chat-header-icon"><i class="fas fa-comments"></i></div>
                <div>
                    <h1 class="chat-title">Chat avec l'administration</h1>
                    <p class="chat-subtitle">Envoyez vos messages et recevez les reponses ici et par email</p>
                </div>
            </div>
        </div>

        <div class="chat-container">
            <!-- Liste des admins (sidebar chat) -->
            <div class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <i class="fas fa-user-shield"></i>
                    <span>Contacts</span>
                </div>
                <div class="admin-list">
                    <div
                        v-for="admin in admins"
                        :key="admin.id"
                        class="admin-item"
                        :class="{ 'admin-active': selectedAdmin?.id === admin.id }"
                        @click="selectAdmin(admin)"
                    >
                        <div class="admin-avatar">{{ admin.initials }}</div>
                        <div class="admin-info">
                            <div class="admin-name">{{ admin.nom }}</div>
                            <div class="admin-email">{{ admin.email }}</div>
                        </div>
                        <div v-if="unreadCountFor(admin.id) > 0" class="admin-badge">{{ unreadCountFor(admin.id) }}</div>
                    </div>
                    <div v-if="admins.length === 0" class="admin-empty">
                        <i class="fas fa-user-slash"></i>
                        <p>Aucun administrateur disponible</p>
                    </div>
                </div>
            </div>

            <!-- Zone de chat -->
            <div class="chat-main">
                <template v-if="selectedAdmin">
                    <!-- Header du chat -->
                    <div class="chat-conv-header">
                        <div class="chat-conv-avatar">{{ selectedAdmin.initials }}</div>
                        <div>
                            <div class="chat-conv-name">{{ selectedAdmin.nom }}</div>
                            <div class="chat-conv-status">Administration</div>
                        </div>
                        <button class="chat-refresh-btn" @click="refreshMessages" title="Actualiser">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>

                    <!-- Messages -->
                    <div class="chat-messages" ref="messagesContainer">
                        <div v-if="filteredMessages.length === 0" class="chat-empty">
                            <i class="fas fa-paper-plane"></i>
                            <p>Commencez la conversation !</p>
                            <small>Envoyez un message a {{ selectedAdmin.nom }}</small>
                        </div>

                        <template v-for="(msg, idx) in filteredMessages" :key="msg.id">
                            <!-- Date separator -->
                            <div
                                v-if="idx === 0 || filteredMessages[idx - 1]?.created_at?.split(' ')[0] !== msg.created_at?.split(' ')[0]"
                                class="chat-date-sep"
                            >
                                {{ msg.created_at?.split(' ')[0] }}
                            </div>

                            <div class="chat-bubble-wrap" :class="msg.is_mine ? 'chat-mine' : 'chat-theirs'">
                                <div class="chat-bubble" :class="msg.is_mine ? 'bubble-mine' : 'bubble-theirs'">
                                    <div class="bubble-text">{{ msg.message }}</div>
                                    <div class="bubble-meta">
                                        <span class="bubble-time">{{ msg.time }}</span>
                                        <i v-if="msg.is_mine" class="fas" :class="msg.is_read ? 'fa-check-double bubble-read' : 'fa-check'"></i>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Input -->
                    <div class="chat-input-area">
                        <div class="chat-input-wrap">
                            <textarea
                                v-model="form.message"
                                placeholder="Ecrivez votre message..."
                                class="chat-input"
                                rows="1"
                                @keydown="handleKeydown"
                            ></textarea>
                            <button
                                class="chat-send-btn"
                                @click="sendMessage"
                                :disabled="!form.message.trim() || form.processing"
                            >
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div class="chat-input-hint">
                            <i class="fas fa-info-circle"></i>
                            Les reponses seront aussi envoyees par email
                        </div>
                    </div>
                </template>

                <div v-else class="chat-no-selection">
                    <i class="fas fa-comments"></i>
                    <p>Selectionnez un contact pour commencer</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.chat-page { padding: 0; }

.chat-header { background: linear-gradient(135deg, #E5590C, #c24a0a); padding: 28px; border-radius: 16px; margin-bottom: 24px; color: white; display: flex; align-items: center; }
.chat-header-left { display: flex; align-items: center; gap: 16px; }
.chat-header-icon { width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 24px; }
.chat-title { font-size: 22px; font-weight: 800; margin: 0; }
.chat-subtitle { font-size: 13px; opacity: 0.85; margin: 4px 0 0; }

.chat-container { display: flex; gap: 0; background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; height: calc(100vh - 250px); min-height: 500px; }

/* Sidebar */
.chat-sidebar { width: 280px; border-right: 1px solid #f1f5f9; display: flex; flex-direction: column; flex-shrink: 0; }
.chat-sidebar-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-weight: 700; color: #1e293b; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.chat-sidebar-header i { color: #E5590C; }

.admin-list { flex: 1; overflow-y: auto; }
.admin-item { display: flex; align-items: center; gap: 12px; padding: 14px 20px; cursor: pointer; transition: all 0.2s; border-bottom: 1px solid #f8fafc; }
.admin-item:hover { background: #f8fafc; }
.admin-active { background: #fff7ed; border-left: 3px solid #E5590C; }

.admin-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #E5590C, #c24a0a); color: white; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; flex-shrink: 0; }
.admin-info { flex: 1; min-width: 0; }
.admin-name { font-size: 13px; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.admin-email { font-size: 11px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.admin-badge { background: #E5590C; color: white; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
.admin-empty { text-align: center; padding: 30px; color: #94a3b8; font-size: 13px; }
.admin-empty i { font-size: 32px; display: block; margin-bottom: 8px; }

/* Main chat */
.chat-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }

.chat-conv-header { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid #f1f5f9; background: #fafafa; }
.chat-conv-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #E5590C, #c24a0a); color: white; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; flex-shrink: 0; }
.chat-conv-name { font-size: 14px; font-weight: 700; color: #1e293b; }
.chat-conv-status { font-size: 11px; color: #16a34a; }
.chat-refresh-btn { margin-left: auto; width: 36px; height: 36px; border-radius: 50%; border: 1px solid #e2e8f0; background: white; cursor: pointer; color: #64748b; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
.chat-refresh-btn:hover { background: #f8fafc; color: #E5590C; transform: rotate(180deg); }

/* Messages area */
.chat-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 8px; background: #f8fafc; }

.chat-empty { text-align: center; margin: auto; color: #94a3b8; }
.chat-empty i { font-size: 48px; display: block; margin-bottom: 12px; }
.chat-empty p { font-size: 16px; font-weight: 600; }
.chat-empty small { font-size: 12px; }

.chat-date-sep { text-align: center; padding: 8px 0; }
.chat-date-sep { font-size: 11px; color: #94a3b8; background: #e2e8f0; display: inline-block; padding: 4px 14px; border-radius: 20px; margin: 8px auto; }

.chat-bubble-wrap { display: flex; }
.chat-mine { justify-content: flex-end; }
.chat-theirs { justify-content: flex-start; }

.chat-bubble { max-width: 70%; padding: 10px 16px; border-radius: 16px; position: relative; }
.bubble-mine { background: #E5590C; color: white; border-bottom-right-radius: 4px; }
.bubble-theirs { background: white; color: #1e293b; border-bottom-left-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }

.bubble-text { font-size: 13px; line-height: 1.5; word-break: break-word; white-space: pre-wrap; }
.bubble-meta { display: flex; align-items: center; gap: 6px; justify-content: flex-end; margin-top: 4px; }
.bubble-time { font-size: 10px; opacity: 0.7; }
.bubble-meta i { font-size: 10px; opacity: 0.7; }
.bubble-read { color: #60a5fa; opacity: 1; }
.bubble-theirs .bubble-meta { color: #94a3b8; }

/* Input */
.chat-input-area { padding: 16px 20px; border-top: 1px solid #f1f5f9; background: white; }
.chat-input-wrap { display: flex; align-items: flex-end; gap: 10px; background: #f8fafc; border-radius: 14px; padding: 8px 12px; border: 1px solid #e2e8f0; transition: border-color 0.2s; }
.chat-input-wrap:focus-within { border-color: #E5590C; }

.chat-input { flex: 1; border: none; background: transparent; resize: none; font-size: 13px; font-family: inherit; outline: none; padding: 6px 0; max-height: 100px; min-height: 20px; color: #1e293b; }
.chat-input::placeholder { color: #94a3b8; }

.chat-send-btn { width: 40px; height: 40px; border-radius: 50%; border: none; background: #E5590C; color: white; cursor: pointer; font-size: 16px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.chat-send-btn:hover { background: #c24a0a; transform: scale(1.05); }
.chat-send-btn:disabled { background: #cbd5e1; cursor: not-allowed; transform: none; }

.chat-input-hint { font-size: 11px; color: #94a3b8; margin-top: 8px; display: flex; align-items: center; gap: 4px; }

.chat-no-selection { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; color: #94a3b8; }
.chat-no-selection i { font-size: 64px; margin-bottom: 16px; }
.chat-no-selection p { font-size: 16px; font-weight: 600; }

@media (max-width: 768px) {
    .chat-container { flex-direction: column; height: calc(100vh - 200px); }
    .chat-sidebar { width: 100%; height: auto; max-height: 150px; border-right: none; border-bottom: 1px solid #f1f5f9; }
    .admin-list { display: flex; overflow-x: auto; padding: 8px; }
    .admin-item { flex-shrink: 0; min-width: 140px; border-bottom: none; }
    .chat-bubble { max-width: 85%; }
}
</style>
