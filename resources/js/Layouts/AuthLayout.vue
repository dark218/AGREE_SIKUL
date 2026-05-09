<script setup>
import { Head } from '@inertiajs/vue3';
import AppLogo from '@/Components/Common/AppLogo.vue';

defineProps({
    title: {
        type: String,
        default: 'SmilPay'
    }
});
</script>

<template>
    <Head :title="title" />
    
    <div class="auth-layout">
        <!-- Background animé -->
        <div class="auth-background">
            <div class="bg-shape bg-shape-1"></div>
            <div class="bg-shape bg-shape-2"></div>
            <div class="bg-shape bg-shape-3"></div>
        </div>

        <!-- Contenu principal -->
        <main class="auth-content">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.auth-layout {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #e8f4fd 0%, #f8f9fc 50%, #fef3f3 100%);
}

/* Background shapes animées */
.auth-background {
    position: absolute;
    inset: 0;
    overflow: hidden;
    z-index: 0;
}

.bg-shape {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.5;
    animation: float 20s infinite ease-in-out;
}

.bg-shape-1 {
    width: 600px;
    height: 600px;
    background: linear-gradient(135deg, #3498DB 0%, #3498DB 100%);
    top: -200px;
    right: -100px;
    animation-delay: 0s;
}

.bg-shape-2 {
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    bottom: -100px;
    left: -100px;
    animation-delay: -5s;
}

.bg-shape-3 {
    width: 300px;
    height: 300px;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation-delay: -10s;
}

@keyframes float {
    0%, 100% {
        transform: translate(0, 0) scale(1);
    }
    25% {
        transform: translate(30px, -30px) scale(1.05);
    }
    50% {
        transform: translate(-20px, 20px) scale(0.95);
    }
    75% {
        transform: translate(-30px, -20px) scale(1.02);
    }
}

/* Contenu */
.auth-content {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 500px;
    padding: 24px;
}

/* Responsive */
@media (max-width: 576px) {
    .auth-content {
        padding: 16px;
    }
    
    .bg-shape {
        opacity: 0.3;
    }
}
</style>
