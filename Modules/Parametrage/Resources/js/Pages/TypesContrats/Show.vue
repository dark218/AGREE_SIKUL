<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, item: { type: Object, required: true } });
</script>
<template>
  <ReferentielCrud mode="show" routeName="parametrage.types_contrats" :title="title || `Détails — Types de contrat`" :item="item" />
</template>
