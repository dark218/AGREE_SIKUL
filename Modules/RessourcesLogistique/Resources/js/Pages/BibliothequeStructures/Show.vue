<template>
    <Head title="Détails bibliothèque" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ structure?.libelle }}</h4>
            <Link :href="route('bibliotheque-structures.edit', structure.id)" class="btn btn-primary">
                <i class="fa fa-edit"></i> Modifier
            </Link>
        </div>
        <BibliothequeStructureForm
            :structure="structure"
            :campuses="campuses"
            :is-read-only="true"
        />
        <div class="mt-3">
            <Link :href="route('bibliotheque-structures.index')" class="btn btn-danger">
                <i class="fa fa-arrow-left"></i> Retour
            </Link>
        </div>
    </div>
</template>
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BibliothequeStructureForm from './BibliothequeStructureForm.vue';
defineOptions({ layout: DashboardLayout });

defineProps({
    structure: { type: Object, required: true },
    campuses:  { type: Array, default: () => [] },
});
</script>
