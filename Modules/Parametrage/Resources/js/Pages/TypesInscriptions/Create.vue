<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String });
</script>
<template>
  <ReferentielCrud mode="create" routeName="parametrage.types_inscriptions" :title="title || `Créer — Types d\u2019inscription`" />
</template>
