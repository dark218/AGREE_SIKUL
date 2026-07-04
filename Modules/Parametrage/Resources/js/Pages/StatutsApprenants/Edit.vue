<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, item: { type: Object, required: true } });
</script>
<template>
  <ReferentielCrud mode="edit" routeName="parametrage.statuts_apprenants" :title="title || `Modifier — Statuts d\u2019apprenant`" :item="item" />
</template>
