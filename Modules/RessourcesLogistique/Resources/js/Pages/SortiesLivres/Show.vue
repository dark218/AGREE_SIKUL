<template>
    <Head title="Détails sortie" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">Sortie — {{ sortie?.ouvrage?.titre || 'livre' }}</h4>
            <Link :href="route('sorties-livres.edit', sortie.id)" class="btn btn-primary"><i class="fa fa-edit"></i> Modifier</Link>
        </div>
        <SortieLivreForm :sortie="sortie" :structures="structures" :ouvrages="ouvrages" :is-read-only="true" />
        <div class="mt-3">
            <Link :href="route('sorties-livres.index')" class="btn btn-danger"><i class="fa fa-arrow-left"></i> Retour</Link>
        </div>
    </div>
</template>
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import SortieLivreForm from './SortieLivreForm.vue';
defineOptions({ layout: DashboardLayout });

defineProps({
    sortie:     { type: Object, required: true },
    structures: { type: Array, default: () => [] },
    ouvrages:   { type: Array, default: () => [] },
});
</script>
