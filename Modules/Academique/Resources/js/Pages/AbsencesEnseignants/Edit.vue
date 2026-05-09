<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import AbsenceEnseignantForm from './AbsenceEnseignantForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
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
    enseignant_id: props.absenceEnseignant?.enseignant_id || '',
    matiere_id: props.absenceEnseignant?.matiere_id || null,
    classe_id: props.absenceEnseignant?.classe_id || null,
    date_debut: props.absenceEnseignant?.date_debut || '',
    date_fin: props.absenceEnseignant?.date_fin || '',
    nombre_heures: props.absenceEnseignant?.nombre_heures || 0,
    motif: props.absenceEnseignant?.motif || '',
    statut: props.absenceEnseignant?.statut || 'en_attente',
    justificatif_path: props.absenceEnseignant?.justificatif_path || null,
    etat: props.absenceEnseignant?.etat || 'actif',
});

// Clear any previous validation errors on page load
form.clearErrors();

const submitForm = () => {
    showUpdateLoader();
    form.put(route('academique.absences_enseignants.update', props.absenceEnseignant.id), {
        forceFormData: true,
        onError: (errors) => {
            console.error('Form validation errors:', errors);
        },
        onSuccess: () => {
        },
        onFinish: () => {
            hideLoader();
        }
    });
};
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
                                <h5 class="title mb-0">{{ props.title || t('common.edit_absence_enseignant') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <AbsenceEnseignantForm :form="form" :enseignants="enseignants" :matieres="matieres" :classes="classes" mode="edit" />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <button type="button" class="btn btn-danger" @click="$inertia.visit(route('academique.absences_enseignants.index'))">
                                                {{ t('actions.back') }}
                                            </button>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                {{ t('actions.validate') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
