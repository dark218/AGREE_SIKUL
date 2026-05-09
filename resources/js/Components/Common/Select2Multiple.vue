<template>
  <select ref="select" multiple="multiple" style="width: 100%">
    <slot></slot>
  </select>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import $ from 'jquery';
import 'select2';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Sélectionner...',
  },
});

const emit = defineEmits(['update:modelValue']);
const select = ref(null);
let isUpdating = false;

onMounted(() => {
  $(select.value).select2({
    placeholder: props.placeholder,
    allowClear: true,
    width: '100%',
  });

  // Initialiser la valeur
  if (props.modelValue && props.modelValue.length > 0) {
    $(select.value).val(props.modelValue.map(v => String(v))).trigger('change');
  }

  $(select.value).on('change', () => {
    if (isUpdating) return;
    const values = $(select.value).val();
    isUpdating = true;
    emit('update:modelValue', values ? values.map(v => parseInt(v) || v) : []);
    isUpdating = false;
  });
});

watch(() => props.modelValue, (newValue) => {
  if (!select.value || isUpdating) return;
  const currentVal = $(select.value).val() || [];
  const newVal = newValue ? newValue.map(v => String(v)) : [];

  // Seulement mettre à jour si vraiment différent
  if (JSON.stringify(currentVal) !== JSON.stringify(newVal)) {
    $(select.value).val(newVal).trigger('change');
  }
}, { deep: true });

onBeforeUnmount(() => {
  if (select.value) {
    $(select.value).select2('destroy');
  }
});
</script>
