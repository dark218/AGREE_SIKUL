<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, items: Object, filters: Object });
</script>
<template>
  <ReferentielCrud mode="index" routeName="parametrage.types_inscriptions" :title="title || `Types d\u2019inscription`" :items="items" :filters="filters" />
</template>
