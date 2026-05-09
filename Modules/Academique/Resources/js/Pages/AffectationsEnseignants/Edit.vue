<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import AffectationEnseignantForm from './AffectationEnseignantForm.vue';
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
    affectation: {
        type: Object,
        required: true,
    },
    enseignants: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    institutions: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    matieres: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    annee_scolaire_id: props.affectation?.annee_scolaire_id || '',
    enseignant_id: props.affectation?.enseignant_id || '',
    classe_id: props.affectation?.classe_id || '',
    ecole_id: props.affectation?.ecole_id || '',
    institution_id: props.affectation?.institution_id || '',
    campus_id: props.affectation?.campus_id || '',
    matiere_1_id: props.affectation?.matiere_1_id || '',
    matiere_2_id: props.affectation?.matiere_2_id || '',
    matiere_3_id: props.affectation?.matiere_3_id || '',
    matiere_4_id: props.affectation?.matiere_4_id || '',
    matiere_5_id: props.affectation?.matiere_5_id || '',
    matiere_6_id: props.affectation?.matiere_6_id || '',
    matiere_7_id: props.affectation?.matiere_7_id || '',
    matiere_8_id: props.affectation?.matiere_8_id || '',
    matiere_9_id: props.affectation?.matiere_9_id || '',
    matiere_10_id: props.affectation?.matiere_10_id || '',
    matiere_11_id: props.affectation?.matiere_11_id || '',
    matiere_12_id: props.affectation?.matiere_12_id || '',
    matiere_13_id: props.affectation?.matiere_13_id || '',
    matiere_14_id: props.affectation?.matiere_14_id || '',
    matiere_15_id: props.affectation?.matiere_15_id || '',
    matiere_16_id: props.affectation?.matiere_16_id || '',
    matiere_17_id: props.affectation?.matiere_17_id || '',
    matiere_18_id: props.affectation?.matiere_18_id || '',
    matiere_19_id: props.affectation?.matiere_19_id || '',
    matiere_20_id: props.affectation?.matiere_20_id || '',
    matiere_21_id: props.affectation?.matiere_21_id || '',
    etat: props.affectation?.etat || 'actif',
});

// Initialize form with affectation data when component mounts
onMounted(() => {
    if (props.affectation) {
        form.annee_scolaire_id = props.affectation.annee_scolaire_id || '';
        form.enseignant_id = props.affectation.enseignant_id || '';
        form.classe_id = props.affectation.classe_id || '';
        form.ecole_id = props.affectation.ecole_id || '';
        form.institution_id = props.affectation.institution_id || '';
        form.campus_id = props.affectation.campus_id || '';
        for (let i = 1; i <= 21; i++) {
            const field = `matiere_${i}_id`;
            form[field] = props.affectation[field] || '';
        }
        form.etat = props.affectation.etat || 'actif';
    }
});

// Watch for changes to props.affectation and update form (for navigation between records)
watch(() => props.affectation, (newAffectation) => {
    if (newAffectation) {
        form.annee_scolaire_id = newAffectation.annee_scolaire_id || '';
        form.enseignant_id = newAffectation.enseignant_id || '';
        form.classe_id = newAffectation.classe_id || '';
        form.ecole_id = newAffectation.ecole_id || '';
        form.institution_id = newAffectation.institution_id || '';
        form.campus_id = newAffectation.campus_id || '';
        for (let i = 1; i <= 21; i++) {
            const field = `matiere_${i}_id`;
            form[field] = newAffectation[field] || '';
        }
        form.etat = newAffectation.etat || 'actif';
    }
}, { deep: true });

const submitForm = () => {
    showUpdateLoader();
    form.put(route('academique.affectations_enseignants.update', props.affectation.id), {
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
                                <h5 class="title mb-0">{{ t('modules.academique.affectations_enseignants.edit') || 'Éditer une affectation' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <AffectationEnseignantForm
                                    :form="form"
                                    :enseignants="enseignants"
                                    :annees-scolaires="anneesScolaires"
                                    :classes="classes"
                                    :ecoles="ecoles"
                                    :institutions="institutions"
                                    :campuses="campuses"
                                    :matieres="matieres"
                                    mode="edit"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.affectations_enseignants.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i> {{ t('actions.validate') }}
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
