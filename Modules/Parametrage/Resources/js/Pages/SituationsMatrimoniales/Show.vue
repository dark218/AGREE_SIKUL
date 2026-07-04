<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, item: { type: Object, required: true } });
</script>
<template>
  <ReferentielCrud mode="show" routeName="parametrage.situations_matrimoniales" :title="title || `Détails — Situations matrimoniales`" :item="item" />
</template>
