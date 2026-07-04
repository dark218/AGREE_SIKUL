<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String });
</script>
<template>
  <ReferentielCrud mode="create" routeName="parametrage.statuts_apprenants" :title="title || `Créer — Statuts d\u2019apprenant`" />
</template>
