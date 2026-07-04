<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, item: { type: Object, required: true } });
</script>
<template>
  <ReferentielCrud mode="edit" routeName="parametrage.langues" :title="title || `Modifier — Langues`" :item="item" />
</template>
