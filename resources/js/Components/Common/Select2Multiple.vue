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

<!--
  §UI : Styles globaux (non-scoped) pour améliorer la lisibilité des Select2
  multi-select. Objectifs :
    - Zone d'affichage type textarea : min-height + hauteur qui grandit avec
      le nombre de tags (au lieu d'écraser sur une seule ligne).
    - Wrap des tags longs sur plusieurs lignes.
    - Tags qui gardent leur libellé complet lisible (pas de troncature agressive).
  Ces styles doivent être GLOBAUX car select2 injecte ses éléments hors du
  scope Vue du composant.
-->
<style>
.select2-container--default .select2-selection--multiple {
    min-height: 44px;
    padding: 4px 6px 2px 6px;
    line-height: 1.4;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background-color: #fff;
}
.select2-container--default .select2-selection--multiple .select2-selection__rendered {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    padding: 0;
    line-height: 1.4;
    max-height: 220px;
    overflow-y: auto;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    max-width: 100%;
    white-space: normal;
    word-break: break-word;
    line-height: 1.4;
    padding: 4px 10px 4px 8px;
    margin: 2px 0;
    background-color: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    border-radius: 4px;
    font-size: 13px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__display {
    padding-left: 4px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #1e40af;
    margin-right: 4px;
}
.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
    margin-top: 4px;
    line-height: 1.4;
}
.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #0b5697;
    box-shadow: 0 0 0 2px rgba(11, 86, 151, 0.15);
}
</style>
