<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String });
</script>
<template>
  <ReferentielCrud mode="create" routeName="parametrage.groupes_sanguins" :title="title || `Créer — Groupes sanguins`" />
</template>
