<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, item: { type: Object, required: true } });
</script>
<template>
  <ReferentielCrud mode="show" routeName="parametrage.groupes_sanguins" :title="title || `Détails — Groupes sanguins`" :item="item" />
</template>
