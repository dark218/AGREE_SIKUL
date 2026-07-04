<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String });
</script>
<template>
  <ReferentielCrud mode="create" routeName="parametrage.langues" :title="title || `Créer — Langues`" />
</template>
