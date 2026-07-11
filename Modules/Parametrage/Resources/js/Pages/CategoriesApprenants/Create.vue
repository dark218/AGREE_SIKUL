<script setup>
import ReferentielCrud from '@/Components/Common/ReferentielCrud.vue';
defineProps({ title: String });
</script>
<template>
  <ReferentielCrud mode="create" routeName="parametrage.categorie_apprenants" :title="title || 'Créer — Catégorie apprenant'" />
</template>
