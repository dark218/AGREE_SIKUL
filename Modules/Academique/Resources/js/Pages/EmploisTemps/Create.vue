<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import EmploiTempsForm from './EmploiTempsForm.vue';
defineOptions({ layout: DashboardLayout });

const props = defineProps({
    title: String,
    classes: Array, ecoles: Array, campuses: Array, institutions: Array,
    sections: Array, cycles: Array, niveaux: Array, anneesScolaires: Array,
    periodes: Array, matieres: Array, enseignants: Array,
});

const form = useForm({
    classe_id: '', niveau_id: null, section_id: null, cycle_id: null,
    ecole_id: null, campus_id: null, annee_scolaire_id: null,
    periode_id: '', libelle: '', date_debut: '', date_fin: '', duree: null,
    etat: 'actif', creneaux: [],
});

const submit = () => form.post(route('academique.emplois_du_temps.store'));
</script>

<template>
    <Head title="Créer un emploi du temps" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">Créer un emploi du temps</h4>
        </div>
        <AlertMessage />
        <EmploiTempsForm
            :form="form"
            mode="create"
            :classes="classes" :ecoles="ecoles" :campuses="campuses" :institutions="institutions"
            :sections="sections" :cycles="cycles" :niveaux="niveaux" :annees-scolaires="anneesScolaires"
            :periodes="periodes" :matieres="matieres" :enseignants="enseignants"
            @submit="submit"
        />
    </div>
</template>
