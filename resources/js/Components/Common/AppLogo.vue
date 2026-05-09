<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const appLogoSrc = computed(
    () => page.props.appConfig?.logo || page.props.appConfig?.logo_login || '/images/image.png'
);

const props = defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    href: {
        type: String,
        default: '/'
    },
    useImage: {
        type: Boolean,
        default: true
    }
});

const sizes = {
    xs: 40,
    sm: 60,
    md: 120,
    lg: 180,
    xl: 250
};

const computedSize = computed(() => sizes[props.size] || sizes.md);
</script>

<template>
    <div>
        <Link :href="href" class="logo-link">
            <div class="logo-container">
                <!-- Utiliser l'image du dossier public -->
                <img
                    v-if="useImage"
                    :src="appLogoSrc"
                    alt="AGREE SIKUL"
                    :width="computedSize"
                    :height="computedSize"
                    class="logo-image"
                />
                <!-- Ou le SVG si pas d'image -->
                <svg 
                    v-else
                    :width="computedSize" 
                    :height="computedSize" 
                    viewBox="0 0 200 200" 
                    fill="none" 
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <circle cx="100" cy="100" r="100" fill="#1A1A1A"/>
                    <rect x="80" y="40" width="16" height="80" rx="2" fill="white"/>
                    <path d="M110 40 L150 70 L110 100 Z" fill="white"/>
                    <circle cx="60" cy="80" r="14" fill="#FFD700"/>
                    <path 
                        d="M65 130 Q100 170 135 130" 
                        stroke="#E63946" 
                        stroke-width="16" 
                        stroke-linecap="round"
                        fill="none"
                    />
                </svg>
            </div>
        </Link>
    </div>
</template>

<style scoped>
.logo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.logo-link {
    display: block;
    transition: transform 0.3s ease;
}

.logo-link:hover {
    transform: scale(1.05);
}

.logo-container {
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
}

.logo-image {
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
    object-fit: contain;
    background: transparent !important;
}

.logo-link:hover .logo-image,
.logo-link:hover svg {
    filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.2));
}
</style>
