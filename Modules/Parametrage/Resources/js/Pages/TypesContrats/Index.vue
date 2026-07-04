<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, items: Object, filters: Object });
</script>
<template>
  <ReferentielCrud mode="index" routeName="parametrage.types_contrats" :title="title || `Types de contrat`" :items="items" :filters="filters" />
</template>
