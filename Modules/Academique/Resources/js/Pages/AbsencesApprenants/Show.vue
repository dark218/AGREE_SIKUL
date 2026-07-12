<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import AbsenceApprenantForm from './AbsenceApprenantForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const props = defineProps({
    title: String,
    absenceApprenant: { type: Object, required: true },
    apprenants: { type: Array, default: () => [] },
    classes: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
});
const a = props.absenceApprenant;
const form = useForm({
    apprenant_id: a.apprenant_id ?? '', classe_id: a.classe_id ?? null, matiere_id: a.matiere_id ?? null,
    date_debut: a.date_debut ?? '', date_fin: a.date_fin ?? '', nombre_heures: a.nombre_heures ?? 0,
    motif: a.motif ?? '', statut: a.statut ?? 'en_attente',
    justificatif_path: a.justificatif_path ?? null, etat: a.etat ?? 'actif',
});
</script>
<template>
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper"><h4 class="title">{{ title || 'Détail absence apprenant' }}</h4></div>
        <AlertMessage />
        <AbsenceApprenantForm :form="form" :apprenants="apprenants" :classes="classes" :matieres="matieres" mode="show" />
        <div class="text-end mt-3">
            <button type="button" class="btn btn-secondary" @click="$inertia.visit(route('academique.absences_apprenants.index'))">{{ t('actions.back') }}</button>
            <button type="button" class="btn btn-primary" @click="$inertia.visit(route('academique.absences_apprenants.edit', a.id))">{{ t('actions.edit') }}</button>
        </div>
    </div>
</template>
