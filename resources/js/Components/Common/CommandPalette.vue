<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAppFeatures } from '@/Composables/useAppFeatures';

const props = defineProps({
    open: { type: Boolean, default: false },
});
const emit = defineEmits(['update:open']);

const { searchFeatures, groupBySection } = useAppFeatures();

const query = ref('');
const selectedIndex = ref(0);
const inputRef = ref(null);

const results = computed(() => searchFeatures(query.value));
const groups = computed(() => groupBySection(results.value));

// Liste plate utilisée pour la navigation clavier (ordre affiché)
const flatResults = computed(() => {
    const out = [];
    Object.keys(groups.value).forEach((section) => {
        groups.value[section].forEach((f) => out.push(f));
    });
    return out;
});

watch(() => props.open, async (isOpen) => {
    if (isOpen) {
        query.value = '';
        selectedIndex.value = 0;
        await nextTick();
        inputRef.value?.focus();
    }
});

watch(query, () => {
    selectedIndex.value = 0;
});

function close() {
    emit('update:open', false);
}

function selectFeature(feature) {
    close();
    router.visit(feature.href);
}

function onKeyDown(event) {
    if (event.key === 'Escape') {
        event.preventDefault();
        close();
        return;
    }
    const total = flatResults.value.length;
    if (total === 0) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectedIndex.value = (selectedIndex.value + 1) % total;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectedIndex.value = (selectedIndex.value - 1 + total) % total;
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const target = flatResults.value[selectedIndex.value];
        if (target) selectFeature(target);
    }
}

function isSelected(feature) {
    return flatResults.value[selectedIndex.value]?.id === feature.id;
}
</script>

<template>
    <Transition name="palette">
        <div
            v-if="open"
            class="cp-overlay"
            @click.self="close"
        >
            <div class="cp-modal" @keydown="onKeyDown">
                <!-- Input -->
                <div class="cp-input-wrapper">
                    <svg class="cp-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input
                        ref="inputRef"
                        v-model="query"
                        type="text"
                        class="cp-input"
                        placeholder="Rechercher une fonctionnalité…"
                        spellcheck="false"
                        autocomplete="off"
                    />
                    <kbd class="cp-esc-hint">Esc</kbd>
                </div>

                <!-- Résultats -->
                <div class="cp-results">
                    <template v-if="flatResults.length === 0">
                        <div class="cp-empty">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;">
                                <circle cx="11" cy="11" r="8" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                            <p>Aucune fonctionnalité trouvée</p>
                            <span>Essayez un autre mot-clé ou vérifiez vos permissions.</span>
                        </div>
                    </template>
                    <template v-else>
                        <div v-for="(sectionFeatures, sectionName) in groups" :key="sectionName" class="cp-section">
                            <div class="cp-section-title">{{ sectionName }}</div>
                            <button
                                v-for="f in sectionFeatures"
                                :key="f.id"
                                type="button"
                                class="cp-item"
                                :class="{ 'cp-item-selected': isSelected(f) }"
                                @click="selectFeature(f)"
                                @mouseenter="selectedIndex = flatResults.findIndex((x) => x.id === f.id)"
                            >
                                <div class="cp-item-label">
                                    <span class="cp-item-icon"><i :class="f.icone || 'fas fa-circle-dot'"></i></span>
                                    <span class="cp-item-module">{{ f.module }}</span>
                                    <span class="cp-item-sep">›</span>
                                    <span class="cp-item-title">{{ f.label }}</span>
                                </div>
                                <svg class="cp-item-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                    <polyline points="12 5 19 12 12 19" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="cp-footer">
                    <span><kbd>↑</kbd><kbd>↓</kbd> naviguer</span>
                    <span><kbd>Enter</kbd> ouvrir</span>
                    <span><kbd>Esc</kbd> fermer</span>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.cp-overlay {
    position: fixed;
    inset: 0;
    background: rgba(12, 30, 54, 0.55);
    backdrop-filter: blur(4px);
    z-index: 2000;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 12vh;
}

.cp-modal {
    width: 100%;
    max-width: 620px;
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 70vh;
    font-family: 'Poppins', sans-serif;
}

.cp-input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #F2F4F7;
}

.cp-search-icon {
    color: #98A2B3;
    flex-shrink: 0;
}

.cp-input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 0.9375rem;
    color: #101828;
    background: transparent;
    font-family: inherit;
}

.cp-input::placeholder {
    color: #98A2B3;
}

.cp-esc-hint {
    display: inline-block;
    padding: 2px 8px;
    background: #F2F4F7;
    color: #667085;
    font-size: 0.6875rem;
    font-family: monospace;
    border-radius: 6px;
    border: 1px solid #EAECF0;
}

.cp-results {
    overflow-y: auto;
    flex: 1;
    padding: 8px;
}

.cp-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 48px 20px;
    color: #667085;
    text-align: center;
    gap: 8px;
}

.cp-empty p {
    margin: 0;
    font-weight: 600;
    color: #344054;
}

.cp-empty span {
    font-size: 0.8125rem;
}

.cp-section {
    margin-bottom: 8px;
}

.cp-section-title {
    padding: 8px 12px 4px;
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #98A2B3;
}

.cp-item {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    text-align: left;
    transition: background 0.1s;
    font-family: inherit;
}

.cp-item-selected {
    background: #FEF1E9;
}

.cp-item-selected .cp-item-arrow {
    color: #E5590C;
    opacity: 1;
}

.cp-item-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    min-width: 0;
}

.cp-item-icon {
    color: #E5590C;
    font-size: 0.8125rem;
    width: 16px;
    text-align: center;
    flex-shrink: 0;
}

.cp-item-module {
    color: #667085;
    font-weight: 500;
    white-space: nowrap;
}

.cp-item-sep {
    color: #D0D5DD;
}

.cp-item-title {
    color: #101828;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cp-item-arrow {
    color: #98A2B3;
    opacity: 0;
    flex-shrink: 0;
    transition: opacity 0.1s;
}

.cp-footer {
    display: flex;
    gap: 16px;
    padding: 10px 16px;
    border-top: 1px solid #F2F4F7;
    background: #FAFBFC;
    font-size: 0.75rem;
    color: #667085;
    flex-wrap: wrap;
}

.cp-footer kbd {
    display: inline-block;
    padding: 1px 6px;
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.6875rem;
    margin-right: 3px;
}

.palette-enter-active,
.palette-leave-active {
    transition: opacity 0.15s ease;
}
.palette-enter-active .cp-modal,
.palette-leave-active .cp-modal {
    transition: transform 0.15s ease, opacity 0.15s ease;
}
.palette-enter-from,
.palette-leave-to {
    opacity: 0;
}
.palette-enter-from .cp-modal,
.palette-leave-to .cp-modal {
    transform: translateY(-8px);
    opacity: 0;
}
</style>
