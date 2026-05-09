<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const logoUrl = ['/images/image.png'].join('');
</script>

<template>
    <Head :title="t('welcome_title')" />

    <div class="welcome-container">
        <!-- Background Image with Overlay -->
        <div class="bg-image"></div>
        <div class="overlay"></div>

        <!-- Main Content -->
        <div class="content-wrapper">
            <!-- Left Section -->
            <div class="left-section">
                <div class="logo-badge">
                    <img :src="logoUrl" alt="AGREE SIKUL" class="logo" />
                </div>

                <h1 class="main-title">AGREE SIKUL</h1>

                <p class="tagline">{{ t('platform_description') }}</p>

                <!-- Features - Clean Lines -->
                <div class="features-list">
                    <div class="feature-line">
                        <span class="check-mark">✓</span>
                        <span>Gestion Complète des Écoles</span>
                    </div>
                    <div class="feature-line">
                        <span class="check-mark">✓</span>
                        <span>Performance et Sécurité</span>
                    </div>
                    <div class="feature-line">
                        <span class="check-mark">✓</span>
                        <span>Support 24/7 Disponible</span>
                    </div>
                </div>

                <!-- CTA Button -->
                <Link
                    :href="!$page.props.auth.user ? route('login') : route('home')"
                    class="cta-button"
                >
                    {{ !$page.props.auth.user ? t('go_to_login') : t('go_to_home') }}
                </Link>

                <p class="footer-text">© 2026 <strong>AGREE SIKUL</strong>. {{ t('all_rights_reserved') }}</p>
            </div>

        </div>
    </div>
</template>

<style scoped>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.welcome-container {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    overflow: hidden;
    font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif;
}

/* Background Image */
.bg-image {
    position: absolute;
    inset: 0;
    background: url('https://images.unsplash.com/photo-1552664730-d307ca884978?w=1920&h=1080&fit=crop&q=80') center/cover no-repeat fixed;
    z-index: -2;
}

.overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(11, 86, 151, 0.75) 0%, rgba(26, 109, 184, 0.7) 50%, rgba(15, 188, 175, 0.65) 100%);
    z-index: -1;
}

/* Content Layout */
.content-wrapper {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 1fr;
    gap: 80px;
    align-items: center;
    max-width: 700px;
    width: 100%;
}

/* Left Section */
.left-section {
    display: flex;
    flex-direction: column;
    gap: 28px;
    color: white;
    animation: slideInLeft 0.8s ease-out;
}

.logo-badge {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(12px);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.25);
    animation: slideDown 0.8s ease-out;
}

.logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
}

.main-title {
    font-size: 64px;
    font-weight: 900;
    line-height: 1.1;
    letter-spacing: -1px;
    animation: slideUp 0.8s ease-out 0.1s both;
}

.tagline {
    font-size: 18px;
    line-height: 1.6;
    opacity: 0.95;
    max-width: 450px;
    animation: slideUp 0.8s ease-out 0.2s both;
}

/* Features - Clean Style */
.features-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin: 16px 0;
    animation: slideUp 0.8s ease-out 0.3s both;
}

.feature-line {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 15px;
    opacity: 0.95;
    transition: all 0.3s ease;
}

.feature-line:hover {
    opacity: 1;
    transform: translateX(4px);
}

.check-mark {
    color: #0FBCAF;
    font-weight: bold;
    font-size: 18px;
    flex-shrink: 0;
}

/* CTA Button */
.cta-button {
    align-self: flex-start;
    padding: 14px 42px;
    background: white;
    color: #0B5697;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
    animation: slideUp 0.8s ease-out 0.4s both;
}

.cta-button:hover {
    transform: translateY(-3px);
    background: #E5590C;
    color: white;
    box-shadow: 0 16px 48px rgba(229, 89, 12, 0.35);
}

.footer-text {
    font-size: 12px;
    opacity: 0.75;
    animation: slideUp 0.8s ease-out 0.5s both;
}

.footer-text strong {
    color: #E5590C;
    opacity: 1;
}


/* Animations */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-25px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(25px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-40px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(40px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Responsive */
@media (max-width: 1200px) {
    .content-wrapper {
        gap: 60px;
    }
}

@media (max-width: 1024px) {
    .content-wrapper {
        grid-template-columns: 1fr;
        gap: 40px;
    }

    .right-section {
        grid-template-columns: repeat(2, 1fr);
    }

    .stat-item-full {
        grid-column: auto;
    }

    .main-title {
        font-size: 52px;
    }
}

@media (max-width: 768px) {
    .welcome-container {
        padding: 20px;
    }

    .content-wrapper {
        gap: 30px;
    }

    .main-title {
        font-size: 42px;
    }

    .tagline {
        font-size: 16px;
        max-width: 100%;
    }

    .features-list {
        gap: 12px;
    }

    .feature-line {
        font-size: 14px;
    }

    .right-section {
        grid-template-columns: 1fr;
    }

    .stat-item {
        padding: 24px 16px;
    }

    .stat-number {
        font-size: 36px;
    }
}

@media (max-width: 480px) {
    .welcome-container {
        min-height: auto;
        padding: 20px 16px;
    }

    .content-wrapper {
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .main-title {
        font-size: 36px;
    }

    .tagline {
        font-size: 15px;
    }

    .cta-button {
        width: 100%;
        justify-content: center;
    }

    .stat-item {
        padding: 20px 16px;
    }

    .stat-number {
        font-size: 32px;
    }
}
</style>
