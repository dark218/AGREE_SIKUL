<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import AbsenceApprenantBatchForm from './AbsenceApprenantBatchForm.vue';
defineOptions({ layout: DashboardLayout });
const props = defineProps({
    title: String,
    apprenants: { type: Array, default: () => [] },
    classes: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    campuses: { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
    enseignants: { type: Array, default: () => [] },
});
const form = useForm({
    annee_scolaire_id: null, classe_id: null, ecole_id: null, campus_id: null,
    matiere_id: null, enseignant_id: null,
    date_debut: '', date_fin: '', nombre_heures: 0, statut: 'en_attente',
    apprenants: [],
    justificatifs: {},
});
const submit = () => form.post(route('academique.absences_apprenants.store'), { forceFormData: true });
</script>
<template>
    <Head title="Nouvelle absence apprenant" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper"><h4 class="title">{{ title || 'Nouvelle absence apprenant' }}</h4></div>
        <AlertMessage />
        <AbsenceApprenantBatchForm
            :form="form"
            :apprenants="apprenants" :classes="classes" :ecoles="ecoles" :campuses="campuses"
            :annees-scolaires="anneesScolaires" :matieres="matieres" :enseignants="enseignants"
            @submit="submit"
        />
    </div>
</template>
