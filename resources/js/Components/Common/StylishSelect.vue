<template>
  <div class="stylish-select-wrapper" ref="wrapper">
    <!-- Trigger Button -->
    <button
      type="button"
      class="stylish-select-trigger"
      :class="{
        'is-open': isOpen,
        'is-disabled': disabled,
        'has-value': hasValue
      }"
      @click="toggleDropdown"
      :disabled="disabled"
    >
      <span class="trigger-text" :class="{ 'is-placeholder': !selectedLabel }">
        {{ selectedLabel || placeholder }}
      </span>
      <span class="trigger-actions">
        <!-- Clear button -->
        <button
          v-if="clearable && hasValue && !disabled"
          type="button"
          class="clear-btn"
          @click.stop="clearSelection"
          :title="clearText"
        >
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
          </svg>
        </button>
        <!-- Dropdown icon -->
        <span class="trigger-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            :class="{ 'rotated': isOpen }"
          >
            <path
              fill-rule="evenodd"
              d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
              clip-rule="evenodd"
            />
          </svg>
        </span>
      </span>
    </button>

    <!-- Dropdown -->
    <Teleport to="body">
      <Transition name="dropdown">
        <div
          v-if="isOpen"
          ref="dropdown"
          class="stylish-select-dropdown"
          :style="dropdownStyle"
        >
          <!-- Search Input -->
          <div v-if="searchable" class="dropdown-search">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="search-icon">
              <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
            </svg>
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              class="search-input"
              :placeholder="searchPlaceholder"
              @keydown.escape="closeDropdown"
              @keydown.enter.prevent="selectHighlighted"
              @keydown.down.prevent="highlightNext"
              @keydown.up.prevent="highlightPrev"
            />
          </div>

          <!-- Options List -->
          <ul class="dropdown-options" ref="optionsList">
            <li
              v-for="(option, index) in filteredOptions"
              :key="getOptionValue(option)"
              class="dropdown-option"
              :class="{
                'is-selected': isSelected(option),
                'is-highlighted': highlightedIndex === index
              }"
              @click="selectOption(option)"
              @mouseenter="highlightedIndex = index"
            >
              <span class="option-label">{{ getOptionLabel(option) }}</span>
              <svg
                v-if="isSelected(option)"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                class="option-check"
              >
                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
              </svg>
            </li>
            <li v-if="filteredOptions.length === 0" class="dropdown-empty">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
              </svg>
              <span>{{ $t('no_results') }}</span>
            </li>
          </ul>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = defineProps({
  modelValue: { default: '' },
  options: { type: Array, default: () => [] },
  optionValue: { type: String, default: 'id' },
  optionLabel: { type: [String, Function], default: 'label' },
  placeholder: { type: String, default: 'Sélectionner...' },
  searchPlaceholder: { type: String, default: 'Rechercher...' },
  emptyText: { type: String, default: 'Aucun résultat' },
  clearText: { type: String, default: 'Effacer' },
  disabled: { type: Boolean, default: false },
  searchable: { type: Boolean, default: true },
  clearable: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue']);

const wrapper = ref(null);
const dropdown = ref(null);
const searchInput = ref(null);
const optionsList = ref(null);
const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);
const dropdownStyle = ref({});

// Helpers
const getOptionValue = (option) => {
  if (typeof option === 'object' && option !== null) {
    return option[props.optionValue] ?? option.id ?? option.value;
  }
  return option;
};

const getOptionLabel = (option) => {
  if (typeof props.optionLabel === 'function') {
    return props.optionLabel(option);
  }
  if (typeof option === 'object' && option !== null) {
    return option[props.optionLabel] ?? option.label ?? option.name ?? option.libelle ?? String(option);
  }
  return String(option);
};

const selectedLabel = computed(() => {
  if (!Array.isArray(props.options)) {
    return '';
  }
  const selected = props.options.find(
    (opt) => String(getOptionValue(opt)) === String(props.modelValue)
  );
  return selected ? getOptionLabel(selected) : '';
});

const hasValue = computed(() => {
  return props.modelValue !== '' && props.modelValue !== null && props.modelValue !== undefined;
});

const filteredOptions = computed(() => {
  if (!Array.isArray(props.options)) {
    return [];
  }
  if (!searchQuery.value.trim()) return props.options;
  const query = searchQuery.value.toLowerCase().trim();
  return props.options.filter((opt) =>
    getOptionLabel(opt).toLowerCase().includes(query)
  );
});

const isSelected = (option) => {
  return String(getOptionValue(option)) === String(props.modelValue);
};

// Position dropdown - TOUJOURS vers le bas par défaut
const positionDropdown = () => {
  if (!wrapper.value) return;

  const rect = wrapper.value.getBoundingClientRect();
  const spaceBelow = window.innerHeight - rect.bottom;
  const maxHeight = 320;

  // Calculer la hauteur réelle du dropdown (basée sur le nombre d'options)
  const optionsCount = filteredOptions.value.length;
  const estimatedHeight = Math.min(maxHeight, optionsCount * 44 + 60); // 44px par option + search bar

  // Toujours ouvrir vers le bas, sauf si vraiment pas assez de place (moins de 150px)
  // ET qu'il y a plus de place en haut
  const openUp = spaceBelow < 150 && rect.top > spaceBelow;

  let top;
  if (openUp) {
    // Ouvrir vers le haut
    top = rect.top - estimatedHeight - 4;
    if (top < 8) top = 8;
  } else {
    // Ouvrir vers le bas (défaut)
    top = rect.bottom + 4;
  }

  dropdownStyle.value = {
    position: 'fixed',
    left: `${rect.left}px`,
    top: `${top}px`,
    width: `${rect.width}px`,
    maxHeight: `${Math.min(maxHeight, openUp ? rect.top - 12 : spaceBelow - 12)}px`,
    zIndex: 9999,
  };
};

// Actions
const toggleDropdown = () => {
  if (props.disabled) return;
  isOpen.value ? closeDropdown() : openDropdown();
};

const openDropdown = () => {
  isOpen.value = true;
  searchQuery.value = '';
  highlightedIndex.value = -1;

  nextTick(() => {
    positionDropdown();
    if (props.searchable && searchInput.value) {
      searchInput.value.focus({ preventScroll: true });
    }
  });
};

const closeDropdown = () => {
  isOpen.value = false;
  searchQuery.value = '';
  highlightedIndex.value = -1;
};

const selectOption = (option) => {
  emit('update:modelValue', getOptionValue(option));
  closeDropdown();
};

const clearSelection = () => {
  emit('update:modelValue', '');
};

const selectHighlighted = () => {
  if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredOptions.value.length) {
    selectOption(filteredOptions.value[highlightedIndex.value]);
  }
};

const highlightNext = () => {
  if (highlightedIndex.value < filteredOptions.value.length - 1) {
    highlightedIndex.value++;
    scrollToHighlighted();
  }
};

const highlightPrev = () => {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--;
    scrollToHighlighted();
  }
};

const scrollToHighlighted = () => {
  nextTick(() => {
    if (optionsList.value) {
      const highlighted = optionsList.value.querySelector('.is-highlighted');
      if (highlighted) {
        highlighted.scrollIntoView({ block: 'nearest' });
      }
    }
  });
};

// Click outside handler
const handleClickOutside = (e) => {
  if (!wrapper.value?.contains(e.target) && !dropdown.value?.contains(e.target)) {
    closeDropdown();
  }
};

// Scroll/resize handler
const handleScrollResize = () => {
  if (isOpen.value) positionDropdown();
};

// Reset highlighted index when search changes
watch(searchQuery, () => {
  highlightedIndex.value = filteredOptions.value.length > 0 ? 0 : -1;
});

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  window.addEventListener('scroll', handleScrollResize, true);
  window.addEventListener('resize', handleScrollResize);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  window.removeEventListener('scroll', handleScrollResize, true);
  window.removeEventListener('resize', handleScrollResize);
});
</script>

<style scoped>
.stylish-select-wrapper {
  position: relative;
  width: 100%;
}

/* Trigger Button */
.stylish-select-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 12px 16px;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 8px rgba(30, 41, 59, 0.08);
}

.stylish-select-trigger:hover:not(.is-disabled) {
  border-color: #3b82f6;
  box-shadow: 0 4px 16px rgba(59, 130, 246, 0.15);
  transform: translateY(-1px);
}

.stylish-select-trigger.is-open {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15), 0 4px 16px rgba(59, 130, 246, 0.15);
}

.stylish-select-trigger.is-disabled {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #94a3b8;
  cursor: not-allowed;
}

.stylish-select-trigger.has-value .trigger-text {
  color: #1e293b;
}

.trigger-text {
  flex: 1;
  text-align: left;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.trigger-text.is-placeholder {
  color: #64748b;
}

.trigger-actions {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.clear-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  padding: 0;
  border: none;
  background: #f1f5f9;
  border-radius: 50%;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
}

.clear-btn:hover {
  background: #fee2e2;
  color: #dc2626;
}

.clear-btn svg {
  width: 14px;
  height: 14px;
}

.trigger-icon {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  margin-left: 4px;
  color: #64748b;
  transition: transform 0.3s ease, color 0.3s ease;
}

.stylish-select-trigger:hover:not(.is-disabled) .trigger-icon {
  color: #3b82f6;
}

.stylish-select-trigger.is-open .trigger-icon {
  color: #3b82f6;
}

.trigger-icon svg {
  width: 100%;
  height: 100%;
  transition: transform 0.3s ease;
}

.trigger-icon svg.rotated {
  transform: rotate(180deg);
}

/* Dropdown */
.stylish-select-dropdown {
  background: #ffffff;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  box-shadow:
    0 20px 40px -10px rgba(30, 41, 59, 0.2),
    0 8px 16px -4px rgba(30, 41, 59, 0.1);
  overflow: hidden;
}

/* Search */
.dropdown-search {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #f1f5f9;
  background: #fafbfc;
}

.search-icon {
  width: 18px;
  height: 18px;
  color: #94a3b8;
  flex-shrink: 0;
}

.search-input {
  flex: 1;
  margin-left: 10px;
  padding: 0;
  border: none;
  background: transparent;
  font-size: 14px;
  color: #1e293b;
  outline: none;
}

.search-input::placeholder {
  color: #94a3b8;
}

/* Options */
.dropdown-options {
  max-height: 260px;
  overflow-y: auto;
  padding: 8px;
  margin: 0;
  list-style: none;
}

.dropdown-options::-webkit-scrollbar {
  width: 6px;
}

.dropdown-options::-webkit-scrollbar-track {
  background: #f8fafc;
  border-radius: 3px;
}

.dropdown-options::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.dropdown-options::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.dropdown-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.dropdown-option:hover,
.dropdown-option.is-highlighted {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.dropdown-option.is-selected {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: #ffffff;
}

.dropdown-option.is-selected:hover,
.dropdown-option.is-selected.is-highlighted {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.option-label {
  flex: 1;
  font-size: 14px;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.option-check {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  margin-left: 8px;
}

.dropdown-option.is-selected .option-check {
  color: #ffffff;
}

/* Empty State */
.dropdown-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  color: #94a3b8;
  font-size: 14px;
  gap: 8px;
}

.dropdown-empty svg {
  width: 32px;
  height: 32px;
  opacity: 0.5;
}

/* Transitions */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.96);
}
</style>
