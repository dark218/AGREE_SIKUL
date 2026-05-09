<template>
    <Transition name="slide-fade">
        <div v-if="visible"
             class="push-notification"
             :class="notificationClass"
             @click="handleClick">
            <div class="notification-icon">
                <span class="icon-emoji">{{ emoji }}</span>
            </div>
            <div class="notification-content">
                <div class="notification-title">{{ title }}</div>
                <div class="notification-body">{{ body }}</div>
            </div>
            <button class="notification-close" @click.stop="close">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    body: {
        type: String,
        required: true
    },
    type: {
        type: String,
        default: 'info' // info, success, warning, error
    },
    duration: {
        type: Number,
        default: 5000
    },
    url: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['close', 'click']);

const visible = ref(false);

const emoji = computed(() => {
    if (props.title.includes('✅')) return '✅';
    if (props.title.includes('❌')) return '❌';
    if (props.title.includes('⚠️')) return '⚠️';
    if (props.title.includes('📦')) return '📦';
    if (props.title.includes('🔔')) return '🔔';

    switch (props.type) {
        case 'success': return '✅';
        case 'error': return '❌';
        case 'warning': return '⚠️';
        default: return '🔔';
    }
});

const notificationClass = computed(() => {
    return `notification-${props.type}`;
});

const close = () => {
    visible.value = false;
    setTimeout(() => {
        emit('close');
    }, 300);
};

const handleClick = () => {
    if (props.url) {
        window.location.href = props.url;
    }
    emit('click');
    close();
};

onMounted(() => {
    // Afficher la notification avec un petit délai
    setTimeout(() => {
        visible.value = true;
    }, 100);

    // Auto-fermer après la durée spécifiée
    if (props.duration > 0) {
        setTimeout(() => {
            close();
        }, props.duration);
    }
});
</script>

<style scoped>
.push-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    min-width: 320px;
    max-width: 400px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2), 0 4px 10px rgba(0, 0, 0, 0.1);
    padding: 16px 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    z-index: 9999;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
}

.push-notification:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25), 0 6px 15px rgba(0, 0, 0, 0.15);
}

.notification-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}

.icon-emoji {
    font-size: 24px;
}

.notification-content {
    flex: 1;
    min-width: 0;
    color: white;
}

.notification-title {
    font-weight: 600;
    font-size: 15px;
    margin-bottom: 4px;
    line-height: 1.4;
    color: white;
}

.notification-body {
    font-size: 13px;
    line-height: 1.5;
    opacity: 0.95;
    color: rgba(255, 255, 255, 0.95);
}

.notification-close {
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    border-radius: 8px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    transition: all 0.2s;
    padding: 0;
}

.notification-close:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: scale(1.1);
}

/* Types de notifications */
.notification-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.notification-error {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
}

.notification-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.notification-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Animations */
.slide-fade-enter-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-fade-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-fade-enter-from {
    transform: translateX(400px) scale(0.9);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateX(400px) scale(0.9);
    opacity: 0;
}

/* Responsive */
@media (max-width: 640px) {
    .push-notification {
        top: 10px;
        right: 10px;
        left: 10px;
        min-width: auto;
        max-width: none;
    }

    .slide-fade-enter-from,
    .slide-fade-leave-to {
        transform: translateY(-100px) scale(0.9);
    }
}
</style>
