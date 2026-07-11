<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, items: Object, filters: Object });
</script>
<template>
  <ReferentielCrud mode="index" routeName="parametrage.categorie_apprenants" :title="title || 'Catégories d’apprenant'" :items="items" :filters="filters" />
</template>
