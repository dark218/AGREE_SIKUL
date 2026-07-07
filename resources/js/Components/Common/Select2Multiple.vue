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
/* §UI : vraie zone type textarea — grande dès le départ + grossit avec les
   sélections. Objectif : lire les libellés complets même quand il y en a
   plusieurs, sans ouvrir le dropdown. */
.select2-container--default .select2-selection--multiple {
    min-height: 90px;
    padding: 8px 10px;
    line-height: 1.5;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background-color: #fff;
    font-size: 14px;
}
.select2-container--default .select2-selection--multiple .select2-selection__rendered {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 0;
    line-height: 1.5;
    max-height: 320px;
    overflow-y: auto;
    min-height: 74px;
    align-content: flex-start;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    max-width: 100%;
    white-space: normal;
    word-break: break-word;
    line-height: 1.5;
    padding: 6px 12px 6px 10px;
    margin: 3px 0;
    background-color: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(30, 64, 175, 0.06);
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__display {
    padding-left: 6px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #1e40af;
    margin-right: 6px;
    font-size: 16px;
    font-weight: 600;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #dc2626;
    background: transparent;
}
.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
    margin-top: 6px;
    line-height: 1.5;
    font-size: 14px;
    min-width: 200px;
}
/* Dropdown des options : plus lisible aussi */
.select2-container--default .select2-results__option {
    padding: 10px 14px;
    font-size: 14px;
    line-height: 1.5;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #0b5697;
    color: #fff;
}
.select2-dropdown {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
.select2-search--dropdown .select2-search__field {
    padding: 8px 12px;
    font-size: 14px;
    border-radius: 6px;
    border: 1px solid #d1d5db;
}
.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #0b5697;
    box-shadow: 0 0 0 2px rgba(11, 86, 151, 0.15);
}
</style>
