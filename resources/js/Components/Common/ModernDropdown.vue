<template>
  <div class="modern-dropdown-wrapper">
    <div class="modern-dropdown-field" @click="toggleOpen" :class="{ open: isOpen }">
      <div class="modern-dropdown-tags">
        <span v-if="selectedItems.length === 0" class="modern-dropdown-placeholder">
          {{ placeholder }}
        </span>
        <div v-for="item in selectedItems" :key="item.id" class="modern-dropdown-tag">
          <span>{{ item.label }}</span>
          <button
            type="button"
            @click.stop="removeItem(item.id)"
            class="modern-dropdown-tag-remove"
          >
            ×
          </button>
        </div>
      </div>
      <div class="modern-dropdown-icon" :class="{ rotated: isOpen }">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>

    <transition name="dropdown-fade">
      <div v-if="isOpen" class="modern-dropdown-menu">
        <div class="modern-dropdown-search">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher..."
            class="modern-dropdown-search-input"
            @click.stop
          />
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" class="modern-dropdown-search-icon">
            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
            <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>

        <div class="modern-dropdown-options">
          <div
            v-for="item in filteredItems"
            :key="item.id"
            @click="toggleItem(item)"
            class="modern-dropdown-option"
            :class="{ selected: isSelected(item.id) }"
          >
            <div class="modern-dropdown-option-checkbox">
              <input
                type="checkbox"
                :checked="isSelected(item.id)"
                :id="`option-${item.id}`"
                @change="toggleItem(item)"
              />
            </div>
            <div class="modern-dropdown-option-content">
              <div class="modern-dropdown-option-label">{{ item.label }}</div>
              <div class="modern-dropdown-option-email">{{ item.email }}</div>
            </div>
          </div>

          <div v-if="filteredItems.length === 0" class="modern-dropdown-empty">
            Aucun résultat trouvé
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Sélectionner...',
  },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');

const selectedItems = computed(() => {
  return props.options.filter(opt =>
    props.modelValue.includes(opt.id) || props.modelValue.includes(String(opt.id))
  );
});

const filteredItems = computed(() => {
  if (!searchQuery.value) {
    return props.options;
  }

  const query = searchQuery.value.toLowerCase();
  return props.options.filter(opt =>
    opt.label.toLowerCase().includes(query) ||
    opt.email.toLowerCase().includes(query)
  );
});

const isSelected = (id) => {
  return props.modelValue.includes(id) || props.modelValue.includes(String(id));
};

const toggleItem = (item) => {
  const newValue = [...props.modelValue];
  const index = newValue.findIndex(v => v === item.id || v === String(item.id));

  if (index > -1) {
    newValue.splice(index, 1);
  } else {
    newValue.push(item.id);
  }

  emit('update:modelValue', newValue);
};

const removeItem = (id) => {
  const newValue = props.modelValue.filter(v => v !== id && v !== String(id));
  emit('update:modelValue', newValue);
};

const toggleOpen = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    searchQuery.value = '';
  }
};

const handleClickOutside = (e) => {
  const wrapper = document.querySelector('.modern-dropdown-wrapper');
  if (wrapper && !wrapper.contains(e.target)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.modern-dropdown-wrapper {
  position: relative;
  width: 100%;
}

.modern-dropdown-field {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  background-color: #fff;
  cursor: pointer;
  transition: all 0.2s ease;
  min-height: 44px;
}

.modern-dropdown-field:hover {
  border-color: #0B5697;
  box-shadow: 0 2px 8px rgba(11, 86, 151, 0.08);
}

.modern-dropdown-field.open {
  border-color: #0B5697;
  box-shadow: 0 4px 12px rgba(11, 86, 151, 0.15);
  border-radius: 8px 8px 0 0;
}

.modern-dropdown-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  flex: 1;
  align-items: center;
}

.modern-dropdown-placeholder {
  color: #999;
  font-size: 14px;
}

.modern-dropdown-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  background: linear-gradient(135deg, #0B5697 0%, #084385 100%);
  color: white;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.modern-dropdown-tag-remove {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  font-size: 16px;
  line-height: 1;
  opacity: 0.8;
  transition: opacity 0.2s;
  padding: 0;
  margin: 0;
}

.modern-dropdown-tag-remove:hover {
  opacity: 1;
}

.modern-dropdown-icon {
  color: #0B5697;
  display: flex;
  align-items: center;
  transition: transform 0.2s ease;
}

.modern-dropdown-icon.rotated {
  transform: rotate(180deg);
}

.modern-dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 2px solid #0B5697;
  border-top: none;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 4px 12px rgba(11, 86, 151, 0.15);
  z-index: 1000;
  max-height: 320px;
  overflow-y: auto;
}

.modern-dropdown-search {
  position: relative;
  padding: 12px;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
}

.modern-dropdown-search-input {
  flex: 1;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  padding: 8px 12px 8px 32px;
  font-size: 14px;
  outline: none;
  transition: all 0.2s;
}

.modern-dropdown-search-input:focus {
  border-color: #0B5697;
  box-shadow: 0 0 0 3px rgba(11, 86, 151, 0.1);
}

.modern-dropdown-search-icon {
  position: absolute;
  left: 20px;
  color: #999;
}

.modern-dropdown-options {
  padding: 6px;
  max-height: 240px;
  overflow-y: auto;
}

.modern-dropdown-option {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 10px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.modern-dropdown-option:hover {
  background-color: #f5f9ff;
}

.modern-dropdown-option.selected {
  background-color: #e8f0f9;
}

.modern-dropdown-option-checkbox {
  display: flex;
  align-items: center;
  margin-top: 2px;
}

.modern-dropdown-option-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #0B5697;
}

.modern-dropdown-option-content {
  flex: 1;
  min-width: 0;
}

.modern-dropdown-option-label {
  font-size: 14px;
  font-weight: 500;
  color: #333;
  word-break: break-word;
}

.modern-dropdown-option-email {
  font-size: 12px;
  color: #999;
  margin-top: 2px;
}

.modern-dropdown-empty {
  padding: 20px 12px;
  text-align: center;
  color: #999;
  font-size: 14px;
}

.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: all 0.2s ease;
}

.dropdown-fade-enter-from {
  opacity: 0;
  transform: translateY(-4px);
}

.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
