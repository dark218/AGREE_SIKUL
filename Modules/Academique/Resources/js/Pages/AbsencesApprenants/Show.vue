<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import AbsenceApprenantForm from './AbsenceApprenantForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    absence: Object,
    apprenants: Array,
    matieres: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    enseignants: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    apprenant_id: props.absence?.apprenant_id || '',
    matiere_id: props.absence?.matiere_id || null,
    classe_id: props.absence?.classe_id || null,
    enseignant_id: props.absence?.enseignant_id || null,
    date_debut: props.absence?.date_debut || '',
    date_fin: props.absence?.date_fin || '',
    nombre_heures: props.absence?.nombre_heures || 0,
    motif: props.absence?.motif || '',
    statut: props.absence?.statut || 'non_justifiee',
    justificatif_path: props.absence?.justificatif_path || null,
    etat: props.absence?.etat || 'actif',
});
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('entities.show_absences_apprenants') || 'Détails Absence' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <AbsenceApprenantForm
                                :form="form"
                                :apprenants="apprenants"
                                :matieres="matieres"
                                :classes="classes"
                                :enseignants="enseignants"
                                mode="show"
                            />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.absences_apprenants.index')" class="btn btn-danger">
                                            {{ t('actions.back') }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
