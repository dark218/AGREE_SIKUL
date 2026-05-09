<template>
  <div class="autocomplete-wrapper" ref="root">
    <!-- Input avec icône -->
    <div class="autocomplete-input-container">
      <input
        ref="input"
        type="text"
        class="autocomplete-input"
        :class="{ 'is-open': open, 'is-disabled': disabled }"
        :value="inputDisplay"
        :placeholder="placeholder"
        autocomplete="off"
        :disabled="disabled"
        @focus="openList"
        @input="onInput"
        @blur="onBlur"
        @keydown.down.prevent="navigateDown"
        @keydown.up.prevent="navigateUp"
        @keydown.enter.prevent="selectHighlighted"
        @keydown.escape="close"
      />
      <span class="autocomplete-icon" :class="{ 'is-open': open }">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
      </span>
      <!-- Bouton clear -->
      <button
        v-if="modelValue && !disabled"
        type="button"
        class="autocomplete-clear"
        @mousedown.prevent="clearSelection"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
        </svg>
      </button>
    </div>

    <!-- Dropdown menu -->
    <teleport to="body">
      <Transition name="autocomplete-dropdown">
        <div
          v-if="open"
          ref="menu"
          class="autocomplete-menu"
          :style="menuStyle"
        >
          <ul class="autocomplete-list" ref="listRef">
            <li
              v-for="(opt, index) in filtered"
              :key="getValue(opt)"
              :ref="el => setItemRef(el, index)"
              class="autocomplete-item"
              :class="{
                'is-selected': isSelected(opt),
                'is-highlighted': highlightedIndex === index
              }"
              @mousedown.prevent="select(opt)"
              @mouseenter="highlightedIndex = index"
            >
              <span class="item-label">{{ getLabel(opt) }}</span>
              <span v-if="isSelected(opt)" class="item-check">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                </svg>
              </span>
            </li>
            <li v-if="filtered.length === 0" class="autocomplete-empty">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="empty-icon">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
              <span>{{ $t('no_results') }}</span>
            </li>
          </ul>
        </div>
      </Transition>
    </teleport>
  </div>
</template>

<script setup>
import {
  computed,
  ref,
  onMounted,
  onBeforeUnmount,
  watch,
  nextTick,
} from "vue";

const props = defineProps({
  options: { type: Array, default: () => [] },
  optionValue: { type: String, default: "id" },
  optionLabel: { type: [String, Function], default: "name" },
  modelValue: { default: "" },
  placeholder: { type: String, default: "Rechercher..." },
  emptyText: { type: String, default: "Aucun résultat trouvé" },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

const open = ref(false);
const query = ref("");
const root = ref(null);
const input = ref(null);
const menu = ref(null);
const listRef = ref(null);
const menuStyle = ref({});
const highlightedIndex = ref(-1);
const itemRefs = ref([]);

const setItemRef = (el, index) => {
  if (el) {
    itemRefs.value[index] = el;
  }
};

const getValue = (o) =>
  typeof o === "object" ? o[props.optionValue] ?? o.id : o;

const getLabel = (o) => {
  if (typeof props.optionLabel === "function") {
    return props.optionLabel(o);
  }
  return typeof o === "object"
    ? o[props.optionLabel] ?? o.label ?? o.name ?? o.libelle
    : String(o);
};

const selectedLabel = computed(() => {
  const found = props.options.find(
    (o) => String(getValue(o)) === String(props.modelValue)
  );
  return found ? getLabel(found) : "";
});

const isSelected = (o) => String(getValue(o)) === String(props.modelValue);

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase();
  if (!q) return props.options;
  return props.options.filter((o) => {
    const label = getLabel(o);
    return label?.toString().toLowerCase().includes(q);
  });
});

const inputDisplay = computed(() =>
  open.value ? query.value : selectedLabel.value
);

const positionMenu = () => {
  const inputEl = input.value;
  if (!inputEl) return;

  const rect = inputEl.getBoundingClientRect();
  if (rect.width === 0 || rect.height === 0) {
    setTimeout(() => positionMenu(), 10);
    return;
  }

  const viewportSpaceBelow = window.innerHeight - rect.bottom;
  const maxMenuHeight = 280;
  const openUp = viewportSpaceBelow < maxMenuHeight && rect.top > viewportSpaceBelow;

  let top = openUp ? rect.top - maxMenuHeight - 4 : rect.bottom + 4;

  // S'assurer que le menu reste dans le viewport
  if (top < 8) top = 8;
  if (top + maxMenuHeight > window.innerHeight - 8) {
    top = window.innerHeight - maxMenuHeight - 8;
  }

  menuStyle.value = {
    position: 'fixed',
    left: `${rect.left}px`,
    top: `${top}px`,
    width: `${rect.width}px`,
    maxHeight: `${maxMenuHeight}px`,
    zIndex: 9999,
  };
};

const openList = () => {
  if (props.disabled) return;
  open.value = true;
  query.value = "";
  highlightedIndex.value = -1;
  nextTick(() => {
    positionMenu();
  });
};

const close = () => {
  open.value = false;
  highlightedIndex.value = -1;
};

const select = (opt) => {
  emit("update:modelValue", getValue(opt));
  query.value = "";
  close();
};

const clearSelection = () => {
  emit("update:modelValue", "");
  query.value = "";
};

const selectHighlighted = () => {
  if (highlightedIndex.value >= 0 && highlightedIndex.value < filtered.value.length) {
    select(filtered.value[highlightedIndex.value]);
  }
};

const navigateDown = () => {
  if (!open.value) {
    openList();
    return;
  }
  if (highlightedIndex.value < filtered.value.length - 1) {
    highlightedIndex.value++;
    scrollToHighlighted();
  }
};

const navigateUp = () => {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--;
    scrollToHighlighted();
  }
};

const scrollToHighlighted = () => {
  nextTick(() => {
    const item = itemRefs.value[highlightedIndex.value];
    if (item && listRef.value) {
      item.scrollIntoView({ block: 'nearest' });
    }
  });
};

const onInput = (e) => {
  query.value = e.target.value;
  open.value = true;
  highlightedIndex.value = 0;
};

const onBlur = () => {
  setTimeout(() => {
    if (query.value.trim() === "" && !props.modelValue) {
      emit("update:modelValue", "");
    }
    close();
  }, 150);
};

const onClickOutside = (e) => {
  const inRoot = root.value && root.value.contains(e.target);
  const inMenu = menu.value && menu.value.contains(e.target);
  if (!inRoot && !inMenu) close();
};

const onScrollOrResize = () => {
  if (open.value) positionMenu();
};

watch(open, (isOpen) => {
  if (isOpen) {
    nextTick(() => positionMenu());
  }
});

watch(() => filtered.value.length, () => {
  highlightedIndex.value = filtered.value.length > 0 ? 0 : -1;
});

onMounted(() => {
  document.addEventListener("click", onClickOutside);
  window.addEventListener("scroll", onScrollOrResize, true);
  window.addEventListener("resize", onScrollOrResize);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", onClickOutside);
  window.removeEventListener("scroll", onScrollOrResize, true);
  window.removeEventListener("resize", onScrollOrResize);
});
</script>

<style scoped>
.autocomplete-wrapper {
  position: relative;
  width: 100%;
}

.autocomplete-input-container {
  position: relative;
  display: flex;
  align-items: center;
}

.autocomplete-input {
  width: 100%;
  padding: 10px 40px 10px 14px;
  font-size: 14px;
  line-height: 1.5;
  color: #1f2937;
  background-color: #fff;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  transition: all 0.2s ease;
  outline: none;
}

.autocomplete-input::placeholder {
  color: #9ca3af;
}

.autocomplete-input:hover:not(.is-disabled) {
  border-color: #9ca3af;
}

.autocomplete-input:focus:not(.is-disabled) {
  border-color: #f97316;
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
}

.autocomplete-input.is-open {
  border-color: #f97316;
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
}

.autocomplete-input.is-disabled {
  background-color: #f3f4f6;
  color: #9ca3af;
  cursor: not-allowed;
}

.autocomplete-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  color: #9ca3af;
  pointer-events: none;
  transition: transform 0.2s ease, color 0.2s ease;
}

.autocomplete-icon.is-open {
  transform: translateY(-50%) rotate(180deg);
  color: #f97316;
}

.autocomplete-clear {
  position: absolute;
  right: 34px;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  padding: 0;
  border: none;
  background: none;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.15s ease;
}

.autocomplete-clear:hover {
  color: #ef4444;
  background-color: #fef2f2;
}

.autocomplete-clear svg {
  width: 14px;
  height: 14px;
}

/* Dropdown Menu */
.autocomplete-menu {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.2), 0 4px 12px -2px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.autocomplete-list {
  max-height: 260px;
  overflow-y: auto;
  padding: 6px;
  margin: 0;
  list-style: none;
}

.autocomplete-list::-webkit-scrollbar {
  width: 6px;
}

.autocomplete-list::-webkit-scrollbar-track {
  background: #f9fafb;
  border-radius: 3px;
}

.autocomplete-list::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

.autocomplete-list::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

.autocomplete-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  font-size: 14px;
  color: #374151;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.autocomplete-item:hover,
.autocomplete-item.is-highlighted {
  background-color: #fff7ed;
  color: #ea580c;
}

.autocomplete-item.is-selected {
  background-color: #fff7ed;
  color: #ea580c;
  font-weight: 500;
}

.autocomplete-item.is-selected.is-highlighted {
  background-color: #ffedd5;
}

.item-label {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item-check {
  flex-shrink: 0;
  width: 18px;
  height: 18px;
  margin-left: 8px;
  color: #f97316;
}

.item-check svg {
  width: 100%;
  height: 100%;
}

.autocomplete-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  color: #9ca3af;
  font-size: 14px;
  gap: 8px;
}

.empty-icon {
  width: 24px;
  height: 24px;
  opacity: 0.5;
}

/* Transitions */
.autocomplete-dropdown-enter-active,
.autocomplete-dropdown-leave-active {
  transition: all 0.2s ease;
}

.autocomplete-dropdown-enter-from,
.autocomplete-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
