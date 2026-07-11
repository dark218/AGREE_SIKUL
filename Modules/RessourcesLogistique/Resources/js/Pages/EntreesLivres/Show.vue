<template>
    <Head title="Détails entrée" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">Entrée — {{ entree?.ouvrage?.titre || 'livre' }}</h4>
            <Link :href="route('entrees-livres.edit', entree.id)" class="btn btn-primary"><i class="fa fa-edit"></i> Modifier</Link>
        </div>
        <EntreeLivreForm :entree="entree" :structures="structures" :ouvrages="ouvrages" :is-read-only="true" />
        <div class="mt-3">
            <Link :href="route('entrees-livres.index')" class="btn btn-danger"><i class="fa fa-arrow-left"></i> Retour</Link>
        </div>
    </div>
</template>
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import EntreeLivreForm from './EntreeLivreForm.vue';
defineOptions({ layout: DashboardLayout });

defineProps({
    entree:     { type: Object, required: true },
    structures: { type: Array, default: () => [] },
    ouvrages:   { type: Array, default: () => [] },
});
</script>
