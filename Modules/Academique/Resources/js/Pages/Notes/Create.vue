<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import NoteBatchForm from './NoteBatchForm.vue';
defineOptions({ layout: DashboardLayout });

const props = defineProps({
    title: String,
    classes: Array, ecoles: Array, campuses: Array, institutions: Array,
    sections: Array, cycles: Array, anneesScolaires: Array, periodes: Array,
    natureExamens: Array, typeExamens: Array, matieres: Array, groupes: Array,
    enseignants: Array,
});

const form = useForm({
    classe_id: '', annee_scolaire_id: null, section_id: null, cycle_id: null,
    ecole_id: null, campus_id: null, periode_id: '', nature_examen_id: '',
    type_examen_id: '', date_examen: '', matiere_id: '', groupe_id: '',
    enseignant_id: '', note_sur: 20, titre: '',
    lignes: [],
});

const submit = () => form.post(route('academique.notes.store'));
</script>

<template>
    <Head title="Saisie des notes" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">Saisie des notes</h4>
        </div>
        <AlertMessage />
        <NoteBatchForm
            :form="form"
            :classes="classes" :ecoles="ecoles" :campuses="campuses" :institutions="institutions"
            :sections="sections" :cycles="cycles" :annees-scolaires="anneesScolaires" :periodes="periodes"
            :nature-examens="natureExamens" :type-examens="typeExamens" :matieres="matieres"
            :groupes="groupes" :enseignants="enseignants"
            @submit="submit"
        />
    </div>
</template>
