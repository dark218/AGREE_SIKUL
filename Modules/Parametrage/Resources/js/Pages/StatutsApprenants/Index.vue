<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, items: Object, filters: Object });
</script>
<template>
  <ReferentielCrud mode="index" routeName="parametrage.statuts_apprenants" :title="title || `Statuts d\u2019apprenant`" :items="items" :filters="filters" />
</template>
