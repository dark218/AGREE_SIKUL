<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import AffectationEnseignantForm from './AffectationEnseignantForm.vue';
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
    annee_scolaire_id: '',
    enseignant_id: '',
    classe_id: '',
    ecole_id: '',
    institution_id: '',
    campus_id: '',
    matiere_1_id: '',
    matiere_2_id: '',
    matiere_3_id: '',
    matiere_4_id: '',
    matiere_5_id: '',
    matiere_6_id: '',
    matiere_7_id: '',
    matiere_8_id: '',
    matiere_9_id: '',
    matiere_10_id: '',
    matiere_11_id: '',
    matiere_12_id: '',
    matiere_13_id: '',
    matiere_14_id: '',
    matiere_15_id: '',
    matiere_16_id: '',
    matiere_17_id: '',
    matiere_18_id: '',
    matiere_19_id: '',
    matiere_20_id: '',
    matiere_21_id: '',
    etat: 'actif',
});

const submitForm = () => {
    showStoreLoader();
    form.post(route('academique.affectations_enseignants.store'), {
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
                                <span class="dash-payment-badge">
                                    <i class="fa fa-plus"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('modules.academique.affectations_enseignants.create') || 'Créer une affectation' }}</h5>
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
                                    mode="create"
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
