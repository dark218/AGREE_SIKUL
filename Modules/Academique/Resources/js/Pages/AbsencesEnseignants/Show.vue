<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AbsenceEnseignantForm from './AbsenceEnseignantForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    absenceEnseignant: Object,
    enseignants: Array,
    matieres: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    enseignant_id: page.props.absenceEnseignant?.enseignant_id || '',
    matiere_id: page.props.absenceEnseignant?.matiere_id || null,
    classe_id: page.props.absenceEnseignant?.classe_id || null,
    date_debut: page.props.absenceEnseignant?.date_debut || '',
    date_fin: page.props.absenceEnseignant?.date_fin || '',
    nombre_heures: page.props.absenceEnseignant?.nombre_heures || 0,
    motif: page.props.absenceEnseignant?.motif || '',
    statut: page.props.absenceEnseignant?.statut || 'en_attente',
    justificatif_path: page.props.absenceEnseignant?.justificatif_path || null,
    etat: page.props.absenceEnseignant?.etat || 'actif',
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
                                <span class="dash-payment-badge">
                                    <i class="fa fa-eye"></i>
                                </span>
                                <h5 class="title mb-0">{{ props.title || t('common.view_absence_enseignant') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AbsenceEnseignantForm :form="form" :enseignants="enseignants" :matieres="matieres" :classes="classes" mode="show" />
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.absences_enseignants.index')" class="btn btn-danger">
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
