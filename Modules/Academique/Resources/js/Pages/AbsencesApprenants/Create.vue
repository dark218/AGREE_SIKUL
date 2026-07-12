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
    apprenants: { type: Array, default: () => [] },
    classes: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
});
const form = useForm({
    apprenant_id: '', classe_id: null, matiere_id: null,
    date_debut: '', date_fin: '', nombre_heures: 0,
    motif: '', statut: 'en_attente', justificatif_path: null, etat: 'actif',
});
const submitForm = () => form.post(route('academique.absences_apprenants.store'), { forceFormData: true });
</script>
<template>
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper"><h4 class="title">{{ title || 'Nouvelle absence apprenant' }}</h4></div>
        <AlertMessage />
        <form @submit.prevent="submitForm">
            <AbsenceApprenantForm :form="form" :apprenants="apprenants" :classes="classes" :matieres="matieres" mode="create" />
            <div class="text-end mt-3">
                <button type="button" class="btn btn-danger" @click="$inertia.visit(route('academique.absences_apprenants.index'))">{{ t('actions.back') }}</button>
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>{{ t('actions.validate') }}
                </button>
            </div>
        </form>
    </div>
</template>
