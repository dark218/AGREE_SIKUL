<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, items: Object, filters: Object });
</script>
<template>
  <ReferentielCrud mode="index" routeName="parametrage.statuts_employes" :title="title || `Statuts d\u2019employé`" :items="items" :filters="filters" />
</template>
