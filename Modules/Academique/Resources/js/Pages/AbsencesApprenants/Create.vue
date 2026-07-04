<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import AbsenceApprenantForm from './AbsenceApprenantForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
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
    apprenant_id: '',
    matiere_id: null,
    classe_id: null,
    enseignant_id: null,
    date_debut: '',
    date_fin: '',
    nombre_heures: 0,
    motif: '',
    statut: 'non_justifiee',
    justificatif_path: null,
    etat: 'actif',
});

// Clear any previous validation errors on page load
form.clearErrors();
const submitForm = () => {
    showStoreLoader();
    form.post(route('academique.absences_apprenants.store'), {
        forceFormData: true,
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: () => {
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
                                <h5 class="title mb-0">{{ t('entities.create_absences_apprenants') || 'Créer une Absence' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <AbsenceApprenantForm
                                    :form="form"
                                    :apprenants="apprenants"
                                    :matieres="matieres"
                                    :classes="classes"
                                    :enseignants="enseignants"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <button type="button" class="btn btn-danger" @click="$inertia.visit(route('academique.absences_apprenants.index'))">
                                                {{ t('actions.back') || 'Retour' }}
                                            </button>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                {{ t('actions.validate') || 'Enregistrer' }}
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
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
