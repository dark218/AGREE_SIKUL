<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    message: {
        type: String,
        default: 'Traitement en cours...',
    },
    subMessage: {
        type: String,
        default: 'Veuillez patienter',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'success', 'warning', 'danger', 'info'].includes(value),
    },
});

const colorClass = computed(() => {
    const colors = {
        primary: '#667eea',
        success: '#28a745',
        warning: '#ffc107',
        danger: '#dc3545',
        info: '#17a2b8',
    };
    return colors[props.variant] || colors.primary;
});
</script>

<template>
    <Teleport to="body">
        <Transition name="loader-fade">
            <div v-if="show" class="fullpage-loader-overlay">
                <div class="loader-content">
                    <!-- Animated Logo/Spinner -->
                    <div class="loader-spinner-container">
                        <div class="loader-spinner">
                            <div class="spinner-ring" :style="{ borderTopColor: colorClass }"></div>
                            <div class="spinner-ring" :style="{ borderTopColor: colorClass }"></div>
                            <div class="spinner-ring" :style="{ borderTopColor: colorClass }"></div>
                        </div>
                        <div class="loader-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" :fill="colorClass">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" v-if="variant === 'success'"/>
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" v-else-if="variant === 'warning' || variant === 'danger'"/>
                                <path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z" v-else-if="variant === 'info'"/>
                                <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z" v-else/>
                            </svg>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="loader-messages">
                        <h4 class="loader-title">{{ message }}</h4>
                        <p class="loader-subtitle">{{ subMessage }}</p>
                    </div>

                    <!-- Progress dots -->
                    <div class="loader-dots">
                        <span class="dot" :style="{ backgroundColor: colorClass }"></span>
                        <span class="dot" :style="{ backgroundColor: colorClass }"></span>
                        <span class="dot" :style="{ backgroundColor: colorClass }"></span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fullpage-loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(5px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loader-content {
    text-align: center;
    padding: 40px;
}

.loader-spinner-container {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 30px;
}

.loader-spinner {
    position: absolute;
    width: 100%;
    height: 100%;
}

.spinner-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid transparent;
    border-radius: 50%;
    animation: spin 1.5s linear infinite;
}

.spinner-ring:nth-child(1) {
    animation-delay: 0s;
    border-width: 3px;
}

.spinner-ring:nth-child(2) {
    width: 80%;
    height: 80%;
    top: 10%;
    left: 10%;
    animation-delay: 0.15s;
    animation-direction: reverse;
    border-width: 3px;
    opacity: 0.8;
}

.spinner-ring:nth-child(3) {
    width: 60%;
    height: 60%;
    top: 20%;
    left: 20%;
    animation-delay: 0.3s;
    border-width: 3px;
    opacity: 0.6;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

.loader-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
    50% {
        transform: translate(-50%, -50%) scale(1.1);
        opacity: 0.8;
    }
}

.loader-messages {
    margin-bottom: 20px;
}

.loader-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    animation: fadeInUp 0.5s ease-out;
}

.loader-subtitle {
    font-size: 0.95rem;
    color: #666;
    margin: 0;
    animation: fadeInUp 0.5s ease-out 0.1s both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.loader-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    animation: bounce 1.4s infinite ease-in-out both;
}

.dot:nth-child(1) {
    animation-delay: -0.32s;
}

.dot:nth-child(2) {
    animation-delay: -0.16s;
}

.dot:nth-child(3) {
    animation-delay: 0s;
}

@keyframes bounce {
    0%, 80%, 100% {
        transform: scale(0.6);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Transition */
.loader-fade-enter-active {
    transition: opacity 0.3s ease;
}

.loader-fade-leave-active {
    transition: opacity 0.5s ease;
}

.loader-fade-enter-from,
.loader-fade-leave-to {
    opacity: 0;
}
</style>
