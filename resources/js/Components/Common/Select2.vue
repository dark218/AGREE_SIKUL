<template>
  <select ref="select" :value="modelValue" @change="onChange" style="width: 100%">
    <slot></slot>
  </select>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import $ from 'jquery';
import 'select2';

const props = defineProps({
  modelValue: [String, Number]
});

const emit = defineEmits(['update:modelValue']);
const select = ref(null);

onMounted(() => {
  $(select.value).select2();
  $(select.value).on('change', () => {
    emit('update:modelValue', $(select.value).val());
  });
});

watch(() => props.modelValue, (newValue) => {
  $(select.value).val(newValue).trigger('change');
});
</script>
