<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String, item: Object });
</script>
<template>
  <ReferentielCrud mode="show" routeName="parametrage.categorie_apprenants" :title="title || 'Détail — Catégorie apprenant'" :item="item" />
</template>
