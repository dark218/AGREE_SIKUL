<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String });
</script>
<template>
  <ReferentielCrud mode="create" routeName="parametrage.situations_matrimoniales" :title="title || `Créer — Situations matrimoniales`" />
</template>
