<script setup>
import { reactive } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import EmploiTempsForm from './EmploiTempsForm.vue';
defineOptions({ layout: DashboardLayout });

const props = defineProps({
    title: String,
    emploiTemps: { type: Object, required: true },
    classes: Array, ecoles: Array, campuses: Array, institutions: Array,
    sections: Array, cycles: Array, niveaux: Array, anneesScolaires: Array,
    periodes: Array, matieres: Array, enseignants: Array,
});

// Objet form en lecture seule (pas d'Inertia useForm nécessaire).
const form = reactive({
    ...props.emploiTemps,
    creneaux: Array.isArray(props.emploiTemps.creneaux) ? props.emploiTemps.creneaux.map(c => ({ ...c })) : [],
});
</script>

<template>
    <Head title="Détails de l'emploi du temps" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ emploiTemps.libelle || 'Emploi du temps' }}</h4>
            <Link :href="route('academique.emplois_du_temps.edit', emploiTemps.id)" class="btn btn-primary">
                <i class="fa fa-edit"></i> Modifier
            </Link>
        </div>
        <EmploiTempsForm
            :form="form"
            mode="show"
            :classes="classes" :ecoles="ecoles" :campuses="campuses" :institutions="institutions"
            :sections="sections" :cycles="cycles" :niveaux="niveaux" :annees-scolaires="anneesScolaires"
            :periodes="periodes" :matieres="matieres" :enseignants="enseignants"
        />
        <div class="mt-3">
            <Link :href="route('academique.emplois_du_temps.index')" class="btn btn-danger">
                <i class="fa fa-arrow-left"></i> Retour
            </Link>
        </div>
    </div>
</template>
