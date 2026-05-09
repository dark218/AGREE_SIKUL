<script setup>
import { watch } from 'vue';
import axios from 'axios';
import { useTimeAgo } from '@/Composables/useTimeAgo';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    notification: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'marked-as-read']);
const { formatTimeAgo } = useTimeAgo();

function close() {
    emit('close');
}

async function markAsRead() {
    if (props.notification && !props.notification.is_read) {
        try {
            await axios.post(`/notification/${props.notification.id}/mark-as-read`);
            // Émettre un événement pour notifier le parent
            emit('marked-as-read', props.notification.id);
        } catch (error) {
            console.error('Erreur lors du marquage de la notification:', error);
        }
    }
}

// Gérer le scroll du body et marquer comme lu
watch(() => props.isOpen, (isOpen) => {
    if (isOpen) {
        document.body.style.overflow = 'hidden';
        markAsRead();
    } else {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="isOpen" class="modal-overlay" @click.self="close">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3 class="modal-title">Notification</h3>
                        <button class="modal-close" @click="close" aria-label="Fermer">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="modal-body" v-if="notification">
                        <h4 class="notification-title">{{ notification.title }}</h4>
                        <p class="notification-message">{{ notification.body || notification.message }}</p>
                        <span class="notification-time">{{ formatTimeAgo(notification.created_at) }}</span>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="close">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 16px;
}

.modal-container {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    max-width: 450px;
    width: 100%;
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    border-bottom: 1px solid #E2E8F0;
}

.modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #2D3748;
    margin: 0;
}

.modal-close {
    padding: 4px;
    border-radius: 6px;
    color: #718096;
    transition: all 0.15s ease;
    background: none;
    border: none;
    cursor: pointer;
}

.modal-close:hover {
    background: #EEF1F6;
    color: #2D3748;
}

.modal-body {
    padding: 24px;
}

.notification-title {
    font-size: 1rem;
    font-weight: 600;
    color: #2D3748;
    margin: 0 0 8px 0;
}

.notification-message {
    font-size: 0.875rem;
    color: #718096;
    line-height: 1.6;
    margin: 0 0 16px 0;
}

.notification-time {
    font-size: 0.75rem;
    color: #A0AEC0;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 24px;
    background: #F8F9FC;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 24px;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 12px;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
}

.btn-secondary {
    background: #EEF1F6;
    color: #2D3748;
}

.btn-secondary:hover {
    background: #E2E8F0;
}

/* Transitions */
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
    transform: scale(0.9) translateY(-20px);
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
    transition: transform 0.3s ease;
}
</style>
