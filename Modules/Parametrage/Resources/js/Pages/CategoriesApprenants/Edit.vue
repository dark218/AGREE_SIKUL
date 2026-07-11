<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, item: Object });
</script>
<template>
  <ReferentielCrud mode="edit" routeName="parametrage.categorie_apprenants" :title="title || 'Modifier — Catégorie apprenant'" :item="item" />
</template>
