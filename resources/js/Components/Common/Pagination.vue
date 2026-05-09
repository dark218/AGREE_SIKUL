<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    data: {
        type: Object,
        required: true
    },
    preserveScroll: {
        type: Boolean,
        default: true
    },
    preserveState: {
        type: Boolean,
        default: true
    }
});

// Calculer les liens visibles (limiter à 7 pages max)
const visibleLinks = computed(() => {
    if (!props.data?.links) return [];

    const links = props.data.links;
    const current = props.data.current_page;
    const last = props.data.last_page;
    
    // Si moins de 8 pages, afficher toutes
    if (last <= 7) {
        return links;
    }
    
    // Sinon, afficher: prev, 1, ..., current-1, current, current+1, ..., last, next
    const result = [];
    
    // Bouton précédent
    result.push(links[0]);
    
    // Page 1
    result.push(links[1]);
    
    // Points de suspension gauche
    if (current > 3) {
        result.push({ label: '...', url: null, active: false });
    }
    
    // Pages autour de la page courante
    for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
        if (i > 1 && i < last) {
            result.push(links[i]);
        }
    }
    
    // Points de suspension droite
    if (current < last - 2) {
        result.push({ label: '...', url: null, active: false });
    }
    
    // Dernière page
    if (last > 1) {
        result.push(links[last]);
    }
    
    // Bouton suivant
    result.push(links[links.length - 1]);
    
    return result;
});

// Vérifier si on doit afficher la pagination
const showPagination = computed(() => {
    return props.data?.last_page > 1;
});

// Label pour précédent/suivant
const getPrevLabel = computed(() => 'previous');
const getNextLabel = computed(() => 'next');
</script>

<template>
    <div v-if="showPagination" class="pagination-wrapper">
        <nav class="pagination-nav">
            <ul class="pagination-list">
                <!-- Bouton Précédent -->
                <li class="pagination-item pagination-prev">
                    <a
                        v-if="data.prev_page_url"
                        :href="data.prev_page_url"
                        class="pagination-link pagination-link-nav"
                    >
                        <span class="pagination-icon">«</span>
                        <span class="pagination-text">{{ t("previous") }}</span>
                    </a>
                    <span v-else class="pagination-link pagination-link-nav disabled">
                        <span class="pagination-icon">«</span>
                        <span class="pagination-text">{{ t("previous") }}</span>
                    </span>
                </li>

                <!-- Pages numérotées -->
                <template v-for="(link, index) in data.links" :key="index">
                    <template v-if="index > 0 && index < data.links.length - 1">
                        <li class="pagination-item">
                            <a
                                v-if="link.url && !link.active"
                                :href="link.url"
                                class="pagination-link"
                                v-html="link.label"
                            />
                            <span
                                v-else-if="link.active"
                                class="pagination-link active"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="pagination-link disabled"
                                v-html="link.label"
                            />
                        </li>
                    </template>
                </template>

                <!-- Bouton Suivant -->
                <li class="pagination-item pagination-next">
                    <a
                        v-if="data.next_page_url"
                        :href="data.next_page_url"
                        class="pagination-link pagination-link-nav"
                    >
                        <span class="pagination-text">{{ t("next") }}</span>
                        <span class="pagination-icon">»</span>
                    </a>
                    <span v-else class="pagination-link pagination-link-nav disabled">
                        <span class="pagination-text">{{ t("next") }}</span>
                        <span class="pagination-icon">»</span>
                    </span>
                </li>
            </ul>
        </nav>

        <!-- Info pagination -->
        <div class="pagination-info">
            <span>
                {{t('affichagede')}} {{ data.from || 0 }} {{t('to')}} {{ data.to || 0 }} {{t('on')}} {{ data.total }} {{t('result')}}
            </span>
        </div>
    </div>
</template>

<style scoped>
.pagination-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    margin-top: 25px;
    padding: 20px 0;
}

.pagination-nav {
    display: flex;
    justify-content: center;
}

.pagination-list {
    display: flex;
    align-items: center;
    gap: 8px;
    list-style: none;
    padding: 0;
    margin: 0;
}

.pagination-item {
    display: flex;
}

.pagination-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 42px;
    height: 42px;
    padding: 0 12px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    background: #f1f5f9;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
}

.pagination-link:hover:not(.disabled):not(.active) {
    background: #e2e8f0;
    color: #334155;
    transform: translateY(-1px);
}

.pagination-link.active {
    background: #E5590C;
    color: #fff;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(229, 89, 12, 0.3);
}

.pagination-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.pagination-link-nav {
    gap: 8px;
    padding: 0 16px;
    background: transparent;
    color: #64748b;
}

.pagination-link-nav:hover:not(.disabled) {
    background: #f1f5f9;
    color: #1a1a2e;
}

.pagination-icon {
    font-size: 12px;
    font-weight: bold;
}

.pagination-text {
    font-size: 13px;
}

.pagination-prev .pagination-text,
.pagination-next .pagination-text {
    display: none;
}

.pagination-info {
    font-size: 13px;
    color: #94a3b8;
}

/* Responsive */
@media (min-width: 640px) {
    .pagination-prev .pagination-text,
    .pagination-next .pagination-text {
        display: inline;
    }
}

@media (max-width: 480px) {
    .pagination-list {
        gap: 4px;
    }
    
    .pagination-link {
        min-width: 36px;
        height: 36px;
        padding: 0 8px;
        font-size: 13px;
    }
    
    .pagination-link-nav {
        padding: 0 10px;
    }
}
</style>