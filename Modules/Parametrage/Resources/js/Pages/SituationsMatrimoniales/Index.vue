<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, items: Object, filters: Object });
</script>
<template>
  <ReferentielCrud mode="index" routeName="parametrage.situations_matrimoniales" :title="title || `Situations matrimoniales`" :items="items" :filters="filters" />
</template>
