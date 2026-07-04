<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, item: { type: Object, required: true } });
</script>
<template>
  <ReferentielCrud mode="show" routeName="parametrage.types_inscriptions" :title="title || `Détails — Types d\u2019inscription`" :item="item" />
</template>
