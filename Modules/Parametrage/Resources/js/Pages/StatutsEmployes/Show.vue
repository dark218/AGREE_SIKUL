<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, item: { type: Object, required: true } });
</script>
<template>
  <ReferentielCrud mode="show" routeName="parametrage.statuts_employes" :title="title || `Détails — Statuts d\u2019employé`" :item="item" />
</template>
