<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import EmploiTempsForm from './EmploiTempsForm.vue';
defineOptions({ layout: DashboardLayout });

const props = defineProps({
    title: String,
    emploiTemps: { type: Object, required: true },
    classes: Array, ecoles: Array, campuses: Array, institutions: Array,
    sections: Array, cycles: Array, niveaux: Array, anneesScolaires: Array,
    periodes: Array, matieres: Array, enseignants: Array,
});

const e = props.emploiTemps;
const form = useForm({
    classe_id: e.classe_id ?? '', niveau_id: e.niveau_id ?? null, section_id: e.section_id ?? null,
    cycle_id: e.cycle_id ?? null, ecole_id: e.ecole_id ?? null, campus_id: e.campus_id ?? null,
    annee_scolaire_id: e.annee_scolaire_id ?? null, periode_id: e.periode_id ?? '',
    libelle: e.libelle ?? '', date_debut: e.date_debut ?? '', date_fin: e.date_fin ?? '',
    duree: e.duree ?? null, etat: e.etat ?? 'actif',
    creneaux: Array.isArray(e.creneaux) ? e.creneaux.map(c => ({ ...c })) : [],
});

const submit = () => form.put(route('academique.emplois_du_temps.update', props.emploiTemps.id));
</script>

<template>
    <Head title="Modifier l'emploi du temps" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">Modifier — {{ emploiTemps.libelle || 'Emploi du temps' }}</h4>
        </div>
        <AlertMessage />
        <EmploiTempsForm
            :form="form"
            mode="edit"
            :classes="classes" :ecoles="ecoles" :campuses="campuses" :institutions="institutions"
            :sections="sections" :cycles="cycles" :niveaux="niveaux" :annees-scolaires="anneesScolaires"
            :periodes="periodes" :matieres="matieres" :enseignants="enseignants"
            @submit="submit"
        />
    </div>
</template>
