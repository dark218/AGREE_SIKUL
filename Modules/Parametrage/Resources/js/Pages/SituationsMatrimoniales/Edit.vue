<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, item: { type: Object, required: true } });
</script>
<template>
  <ReferentielCrud mode="edit" routeName="parametrage.situations_matrimoniales" :title="title || `Modifier — Situations matrimoniales`" :item="item" />
</template>
