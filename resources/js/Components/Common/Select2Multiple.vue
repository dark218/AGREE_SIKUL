<!--
  Select2Multiple.vue — Multi-select 100 % Vue 3 natif.

  Historique : ce composant utilisait select2 (jQuery) qui échouait
  silencieusement en prod (jQuery pas prêt au mount / conflit Inertia
  navigation) → le `<select multiple>` natif restait sans habillage et
  s'affichait comme un dropdown single ridicule. Cause racine des
  screenshots DevTools qui montraient `.select2-*` absent du DOM.

  Cette réécriture supprime jQuery/select2 et offre une VRAIE zone type
  "textarea de tags" — grande dès le départ, search filter, tags cliquables
  pour retirer, dropdown pop-under. Aucune dépendance externe.

  API compatible avec l'ancienne version :
    <Select2Multiple v-model="form.matieres_ids" placeholder="…">
      <option v-for="m in matieres" :key="m.id" :value="m.id">{{ m.libelle }}</option>
    </Select2Multiple>
-->
<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, useSlots, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Sélectionner…',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    searchPlaceholder: {
        type: String,
        default: 'Rechercher…',
    },
});

const emit = defineEmits(['update:modelValue']);
const slots = useSlots();

const root = ref(null);
const searchInput = ref(null);
const query = ref('');
const isOpen = ref(false);

// Extrait les options depuis le slot <option v-for=...>.
// Réactif : recalculé à chaque render du slot par défaut.
const options = computed(() => {
    if (!slots.default) return [];
    const vnodes = slots.default();
    const out = [];
    const walk = (nodes) => {
        for (const n of nodes) {
            if (!n) continue;
            if (Array.isArray(n.children)) walk(n.children);
            else if (Array.isArray(n)) walk(n);
            // Un <option> apparaît comme un VNode avec type === 'option'.
            if (n.type === 'option') {
                const value = n.props?.value ?? n.props?.['value'];
                let label = '';
                const kids = n.children;
                if (typeof kids === 'string') label = kids;
                else if (Array.isArray(kids)) label = kids.filter(c => typeof c === 'string').join(' ').trim();
                else if (kids && typeof kids.default === 'function') {
                    const inner = kids.default();
                    label = inner?.map(c => (typeof c === 'string' ? c : c?.children || '')).join(' ').trim();
                }
                out.push({ value, label: String(label ?? value) });
            }
        }
    };
    walk(vnodes);
    return out;
});

// Options filtrées par la recherche.
const filteredOptions = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return options.value;
    return options.value.filter(o => o.label.toLowerCase().includes(q));
});

// Options actuellement sélectionnées.
const selectedOptions = computed(() => {
    const values = (props.modelValue || []).map(v => String(v));
    return options.value.filter(o => values.includes(String(o.value)));
});

const isSelected = (opt) => (props.modelValue || []).map(String).includes(String(opt.value));

const toggle = (opt) => {
    if (props.disabled) return;
    const values = (props.modelValue || []).map(String);
    const strVal = String(opt.value);
    let next;
    if (values.includes(strVal)) {
        next = values.filter(v => v !== strVal);
    } else {
        next = [...values, strVal];
    }
    // Cast en number quand la valeur d'origine est numérique.
    const casted = next.map(v => {
        const n = Number(v);
        return Number.isFinite(n) && String(n) === v ? n : v;
    });
    emit('update:modelValue', casted);
};

const remove = (opt) => {
    if (props.disabled) return;
    toggle(opt);
};

const openDropdown = () => {
    if (props.disabled) return;
    isOpen.value = true;
    nextTick(() => searchInput.value?.focus());
};

const closeDropdown = () => {
    isOpen.value = false;
    query.value = '';
};

// Ferme au click extérieur.
const onDocClick = (e) => {
    if (!root.value) return;
    if (!root.value.contains(e.target)) closeDropdown();
};

onMounted(() => {
    document.addEventListener('mousedown', onDocClick);
});
onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocClick);
});

// Assainit la valeur : purge les IDs qui ne matchent aucune option (orphelins).
watch([() => props.modelValue, options], ([mv, opts]) => {
    if (!Array.isArray(mv) || !opts.length) return;
    const valid = new Set(opts.map(o => String(o.value)));
    const cleaned = mv.filter(v => valid.has(String(v)));
    if (cleaned.length !== mv.length) {
        emit('update:modelValue', cleaned);
    }
}, { immediate: true, deep: true });
</script>

<template>
    <div
        ref="root"
        class="ssv-wrapper"
        :class="{ 'ssv-open': isOpen, 'ssv-disabled': disabled }"
    >
        <!-- Zone d'affichage type textarea -->
        <div class="ssv-control" @click="openDropdown">
            <div class="ssv-tags">
                <span
                    v-for="opt in selectedOptions"
                    :key="opt.value"
                    class="ssv-tag"
                    @click.stop
                >
                    <span class="ssv-tag-label">{{ opt.label }}</span>
                    <button
                        v-if="!disabled"
                        type="button"
                        class="ssv-tag-remove"
                        @click.stop="remove(opt)"
                        aria-label="Retirer"
                    >×</button>
                </span>
                <span v-if="!selectedOptions.length" class="ssv-placeholder">
                    {{ placeholder }}
                </span>
            </div>
            <span class="ssv-chevron" :class="{ 'ssv-chevron-open': isOpen }">▾</span>
        </div>

        <!-- Dropdown -->
        <div v-if="isOpen" class="ssv-dropdown">
            <div class="ssv-search-wrap">
                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    class="ssv-search"
                    :placeholder="searchPlaceholder"
                    @keydown.esc="closeDropdown"
                />
            </div>
            <ul class="ssv-options">
                <li
                    v-for="opt in filteredOptions"
                    :key="opt.value"
                    class="ssv-option"
                    :class="{ 'ssv-option-selected': isSelected(opt) }"
                    @mousedown.prevent="toggle(opt)"
                >
                    <span class="ssv-check">
                        <span v-if="isSelected(opt)">✓</span>
                    </span>
                    <span class="ssv-option-label">{{ opt.label }}</span>
                </li>
                <li v-if="!filteredOptions.length" class="ssv-empty">
                    Aucun résultat
                </li>
            </ul>
        </div>
    </div>
</template>

<style>
.ssv-wrapper {
    position: relative;
    width: 100%;
    font-size: 14px;
    line-height: 1.5;
}
.ssv-control {
    min-height: 90px;
    padding: 8px 40px 8px 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background-color: #fff;
    cursor: pointer;
    display: flex;
    align-items: flex-start;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.ssv-wrapper.ssv-open .ssv-control,
.ssv-control:hover {
    border-color: #0b5697;
}
.ssv-wrapper.ssv-open .ssv-control {
    box-shadow: 0 0 0 2px rgba(11, 86, 151, 0.15);
}
.ssv-wrapper.ssv-disabled .ssv-control {
    background-color: #f9fafb;
    cursor: not-allowed;
    border-color: #e5e7eb;
}
.ssv-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    width: 100%;
    max-height: 320px;
    overflow-y: auto;
    min-height: 74px;
    align-content: flex-start;
}
.ssv-placeholder {
    color: #9ca3af;
    padding: 4px 2px;
    align-self: center;
}
.ssv-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 6px 6px 10px;
    background-color: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.4;
    max-width: 100%;
    box-shadow: 0 1px 2px rgba(30, 64, 175, 0.06);
}
.ssv-tag-label {
    white-space: normal;
    word-break: break-word;
}
.ssv-tag-remove {
    border: none;
    background: transparent;
    color: #1e40af;
    font-size: 18px;
    font-weight: 700;
    line-height: 1;
    padding: 0 4px;
    cursor: pointer;
    border-radius: 4px;
    transition: color 0.15s, background 0.15s;
}
.ssv-tag-remove:hover {
    color: #fff;
    background: #dc2626;
}
.ssv-chevron {
    position: absolute;
    right: 12px;
    top: 12px;
    color: #6b7280;
    font-size: 12px;
    transition: transform 0.15s;
    pointer-events: none;
}
.ssv-chevron-open {
    transform: rotate(180deg);
}
.ssv-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    z-index: 1050;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}
.ssv-search-wrap {
    padding: 8px;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}
.ssv-search {
    width: 100%;
    padding: 8px 12px;
    font-size: 14px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    outline: none;
    transition: border-color 0.15s;
}
.ssv-search:focus {
    border-color: #0b5697;
    box-shadow: 0 0 0 2px rgba(11, 86, 151, 0.15);
}
.ssv-options {
    list-style: none;
    margin: 0;
    padding: 4px 0;
    max-height: 260px;
    overflow-y: auto;
}
.ssv-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1.5;
    transition: background 0.1s;
}
.ssv-option:hover {
    background: #f3f4f6;
}
.ssv-option-selected {
    background: #eff6ff;
    color: #1e40af;
    font-weight: 500;
}
.ssv-option-selected:hover {
    background: #dbeafe;
}
.ssv-check {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border: 1.5px solid #d1d5db;
    border-radius: 4px;
    color: #fff;
    background: #fff;
    font-size: 12px;
    font-weight: bold;
    flex-shrink: 0;
}
.ssv-option-selected .ssv-check {
    border-color: #0b5697;
    background: #0b5697;
}
.ssv-option-label {
    flex: 1;
    word-break: break-word;
}
.ssv-empty {
    padding: 14px;
    text-align: center;
    color: #9ca3af;
    font-style: italic;
}
</style>
