<script setup>
import { ref, nextTick, onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    otherUser: { type: Object, required: true },
    messages: { type: Array, default: () => [] },
    currentUserId: { type: Number, required: true },
});

const messagesContainer = ref(null);

const form = useForm({
    message: '',
});

function sendReply() {
    if (!form.message.trim()) return;

    form.post(route('admin-chat.reply', props.otherUser.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.message = '';
            scrollToBottom();
        },
    });
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
        sendReply();
    }
}

onMounted(scrollToBottom);
</script>

<template>
    <div class="acc-page">
        <!-- Header -->
        <div class="acc-header">
            <Link :href="route('admin-chat.index')" class="acc-back">
                <i class="fas fa-arrow-left"></i>
            </Link>
            <div class="acc-avatar">{{ otherUser.initials }}</div>
            <div>
                <div class="acc-name">{{ otherUser.nom }}</div>
                <div class="acc-email">{{ otherUser.email }}</div>
            </div>
        </div>

        <!-- Messages -->
        <div class="acc-messages" ref="messagesContainer">
            <div v-if="messages.length === 0" class="acc-empty">
                <i class="fas fa-comments"></i>
                <p>Aucun message dans cette conversation</p>
            </div>

            <template v-for="(msg, idx) in messages" :key="msg.id">
                <div
                    v-if="idx === 0 || messages[idx - 1]?.created_at?.split(' ')[0] !== msg.created_at?.split(' ')[0]"
                    class="acc-date-sep"
                >
                    {{ msg.created_at?.split(' ')[0] }}
                </div>

                <div class="acc-bubble-wrap" :class="msg.is_mine ? 'acc-mine' : 'acc-theirs'">
                    <div class="acc-bubble" :class="msg.is_mine ? 'bubble-mine' : 'bubble-theirs'">
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
        <div class="acc-input-area">
            <div class="acc-input-wrap">
                <textarea
                    v-model="form.message"
                    placeholder="Repondre..."
                    class="acc-input"
                    rows="1"
                    @keydown="handleKeydown"
                ></textarea>
                <button class="acc-send-btn" @click="sendReply" :disabled="!form.message.trim() || form.processing">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="acc-hint">
                <i class="fas fa-info-circle"></i>
                L'apprenant recevra aussi la reponse par email et dans ses notifications
            </div>
        </div>
    </div>
</template>

<style scoped>
.acc-page { display: flex; flex-direction: column; height: calc(100vh - 100px); min-height: 500px; background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; }

.acc-header { display: flex; align-items: center; gap: 14px; padding: 16px 24px; border-bottom: 1px solid #f1f5f9; background: #fafafa; }
.acc-back { width: 36px; height: 36px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b; text-decoration: none; transition: all 0.2s; }
.acc-back:hover { background: #e2e8f0; color: #1e293b; }
.acc-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #8B5CF6, #6D28D9); color: white; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; }
.acc-name { font-size: 15px; font-weight: 700; color: #1e293b; }
.acc-email { font-size: 11px; color: #94a3b8; }

.acc-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 8px; background: #f8fafc; }

.acc-empty { text-align: center; margin: auto; color: #94a3b8; }
.acc-empty i { font-size: 48px; display: block; margin-bottom: 12px; }

.acc-date-sep { text-align: center; font-size: 11px; color: #94a3b8; background: #e2e8f0; display: inline-block; padding: 4px 14px; border-radius: 20px; margin: 8px auto; }

.acc-bubble-wrap { display: flex; }
.acc-mine { justify-content: flex-end; }
.acc-theirs { justify-content: flex-start; }

.acc-bubble { max-width: 70%; padding: 10px 16px; border-radius: 16px; }
.bubble-mine { background: #E5590C; color: white; border-bottom-right-radius: 4px; }
.bubble-theirs { background: white; color: #1e293b; border-bottom-left-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }

.bubble-text { font-size: 13px; line-height: 1.5; word-break: break-word; white-space: pre-wrap; }
.bubble-meta { display: flex; align-items: center; gap: 6px; justify-content: flex-end; margin-top: 4px; }
.bubble-time { font-size: 10px; opacity: 0.7; }
.bubble-meta i { font-size: 10px; opacity: 0.7; }
.bubble-read { color: #60a5fa; opacity: 1; }
.bubble-theirs .bubble-meta { color: #94a3b8; }

.acc-input-area { padding: 16px 24px; border-top: 1px solid #f1f5f9; background: white; }
.acc-input-wrap { display: flex; align-items: flex-end; gap: 10px; background: #f8fafc; border-radius: 14px; padding: 8px 12px; border: 1px solid #e2e8f0; transition: border-color 0.2s; }
.acc-input-wrap:focus-within { border-color: #E5590C; }

.acc-input { flex: 1; border: none; background: transparent; resize: none; font-size: 13px; font-family: inherit; outline: none; padding: 6px 0; max-height: 100px; min-height: 20px; color: #1e293b; }

.acc-send-btn { width: 40px; height: 40px; border-radius: 50%; border: none; background: #E5590C; color: white; cursor: pointer; font-size: 16px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.acc-send-btn:hover { background: #c24a0a; }
.acc-send-btn:disabled { background: #cbd5e1; cursor: not-allowed; }

.acc-hint { font-size: 11px; color: #94a3b8; margin-top: 8px; display: flex; align-items: center; gap: 4px; }
</style>
