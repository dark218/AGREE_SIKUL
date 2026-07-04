<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, items: Object, filters: Object });
</script>
<template>
  <ReferentielCrud mode="index" routeName="parametrage.groupes_sanguins" :title="title || `Groupes sanguins`" :items="items" :filters="filters" />
</template>
