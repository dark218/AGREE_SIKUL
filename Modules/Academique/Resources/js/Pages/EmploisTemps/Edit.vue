<script setup>
import { ref } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import EmploiTempsForm from './EmploiTempsForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    emploiTemps: Object,
    classes: { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    campuses: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
    enseignants: { type: Array, default: () => [] },
});
const emploiData = props.emploiTemps || page.props.emploiTemps;
console.log('[EmploiTempsEdit] Form initialization with:', emploiData);

// Format datetime pour datetime-local input (enlever .000000Z)
const formatDatetimeForInput = (dateString) => {
    if (!dateString) return '';
    return dateString.replace(/\.\d+Z$/, '');
};

// Format date pour date input (YYYY-MM-DD)
const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    return dateString.split('T')[0]; // Extrait YYYY-MM-DD de ISO datetime
};

const form = useForm({
    classe_id: emploiData?.classe_id || null,
    annee_scolaire_id: emploiData?.annee_scolaire_id || null,
    section_id: emploiData?.section_id || null,
    cycle_id: emploiData?.cycle_id || null,
    ecole_id: emploiData?.ecole_id || null,
    campus_id: emploiData?.campus_id || null,
    week_name: emploiData?.week_name || '',
    week_start_date: formatDateForInput(emploiData?.week_start_date) || '',
    week_end_date: formatDateForInput(emploiData?.week_end_date) || '',
    jour: emploiData?.jour || null,
    matiere_id: emploiData?.matiere_id || null,
    enseignant_id: emploiData?.enseignant_id || null,
    duree: emploiData?.duree || null,
    date_debut: formatDatetimeForInput(emploiData?.date_debut) || '',
    date_fin: formatDatetimeForInput(emploiData?.date_fin) || '',
    est_valide: emploiData?.est_valide || false,
    statut: emploiData?.statut || 'brouillon',
});
const submitForm = () => {
    showUpdateLoader();
    const id = emploiData?.id || props.emploiTemps?.id;

    // Clean dates: extract only YYYY-MM-DDTHH:MM (first 16 chars) to match validation format
    const cleanedData = {
        ...form.data(),
        date_debut: form.date_debut ? form.date_debut.substring(0, 16) : '',
        date_fin: form.date_fin ? form.date_fin.substring(0, 16) : '',
    };

    console.log('[EmploiTempsEdit] Submitting update for ID:', id);
    console.log('[EmploiTempsEdit] Cleaned dates - début:', cleanedData.date_debut, 'fin:', cleanedData.date_fin);

    form.put(route('academique.emplois_du_temps.update', id), {
        data: cleanedData,
        onError: (errors) => {
            console.error('Form validation errors:', errors);
            hideLoader();
        },
        onSuccess: () => {
            console.log('[EmploiTempsEdit] Update successful');
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
                                <h5 class="title mb-0">{{ t('actions.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <!-- Bouton "Valider" géré par le FormStepper (dernière étape). -->
                            <div>
                                <EmploiTempsForm
                                    :form="form"
                                    :classes="classes"
                                    :anneesScolaires="anneesScolaires"
                                    :sections="sections"
                                    :cycles="cycles"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    :matieres="matieres"
                                    :enseignants="enseignants"
                                    mode="edit"
                                    @submit="submitForm"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-start">
                                            <Link :href="route('academique.emplois_du_temps.index')" class="btn btn-outline-secondary">
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
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
