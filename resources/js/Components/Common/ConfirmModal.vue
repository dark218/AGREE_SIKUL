<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    isOpen: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Confirmation'
    },
    message: {
        type: String,
        default: 'Êtes-vous sûr ?'
    },
    subMessage: {
        type: String,
        default: ''
    },
    confirmText: {
        type: String,
        default: 'Confirmer'
    },
    cancelText: {
        type: String,
        default: 'Annuler'
    },
    confirmClass: {
        type: String,
        default: ''
    },
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'danger', 'warning', 'success'].includes(value)
    }
});

const emit = defineEmits(['confirm', 'cancel', 'update:show', 'update:isOpen', 'close']);

// Support pour les deux props (show et isOpen)
const isVisible = computed(() => props.show || props.isOpen);

const confirmButtonClass = computed(() => {
    if (props.confirmClass) return props.confirmClass;
    const classes = {
        default: 'btn-primary',
        danger: 'btn-danger',
        warning: 'btn-warning',
        success: 'btn-success'
    };
    return classes[props.variant] || 'btn-primary';
});

function closeModal() {
    // Émettre tous les événements de fermeture de manière synchrone
    emit('update:show', false);
    emit('update:isOpen', false);
    emit('close');
}

function handleConfirm() {
    emit('confirm');
    // Utiliser nextTick pour s'assurer que l'événement confirm est traité avant la fermeture
    setTimeout(() => {
        closeModal();
    }, 0);
}

function handleCancel() {
    emit('cancel');
    closeModal();
}

// Gérer le scroll du body
watch(isVisible, (visible) => {
    if (visible) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="isVisible" class="modal-overlay" @click.self="handleCancel">
                <div class="modal-container" :class="`modal-${variant}`">
                    <div class="modal-header">
                        <h3 class="modal-title">{{ title }}</h3>
                        <button class="modal-close" @click="handleCancel" aria-label="Fermer">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="modal-body">
                        <h4 v-if="message" class="message-title">{{ message }}</h4>
                        <p v-if="subMessage" class="message-sub">{{ subMessage }}</p>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-outline-dark" @click="handleCancel">
                            {{ cancelText }}
                        </button>
                        <button 
                            class="btn" 
                            :class="confirmButtonClass"
                            @click="handleConfirm"
                        >
                            {{ confirmText }}
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
    max-width: 400px;
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
    background: none;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
}

.modal-close:hover {
    background: #EEF1F6;
    color: #2D3748;
}

.modal-body {
    padding: 24px;
}

.modal-body .message-title {
    color: #2D3748;
    font-weight: 600;
    font-size: 1rem;
    margin: 0 0 8px 0;
}

.modal-body .message-sub {
    color: #718096;
    line-height: 1.6;
    margin: 0;
    font-size: 0.875rem;
}

.modal-body p {
    color: #718096;
    line-height: 1.6;
    margin: 0;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 24px;
    background: #F8F9FC;
}

/* Variants */
.modal-danger .modal-title {
    color: #E74C3C;
}

.modal-warning .modal-title {
    color: #F39C12;
}

.modal-success .modal-title {
    color: #2ECC71;
}

/* Boutons */
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

.btn-primary {
    background: #000;
    color: #fff;
}

.btn-primary:hover {
    background: #333;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #EEF1F6;
    color: #2D3748;
}

.btn-secondary:hover {
    background: #E2E8F0;
}

.btn-danger {
    background: #E74C3C;
    color: #fff;
}

.btn-danger:hover {
    background: #C0392B;
}

.btn-warning {
    background: #F39C12;
    color: #fff;
}

.btn-warning:hover {
    background: #E67E22;
}

.btn-success {
    background: #2ECC71;
    color: #fff;
}

.btn-success:hover {
    background: #27AE60;
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
