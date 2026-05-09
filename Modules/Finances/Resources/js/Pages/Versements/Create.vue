<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import VersementForm from './VersementForm.vue';
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
    apprenants: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    niveaux: {
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
    campuses: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    annee_scolaire_id: null,
    apprenant_id: null,
    niveau_id: null,
    classe_id: null,
    ecole_id: null,
    campus_id: null,
    frais_dossier: null,
    frais_inscription: null,
    frais_scolarite: null,
    total_paye: null,
    restant_a_payer: null,
    nature_versement_1: null,
    montant_versement_1: null,
    nature_versement_2: null,
    montant_versement_2: null,
    nature_versement_3: null,
    montant_versement_3: null,
    nature_versement_4: null,
    montant_versement_4: null,
    nature_versement_5: null,
    montant_versement_5: null,
    nature_versement_6: null,
    montant_versement_6: null,
    nature_versement_7: null,
    montant_versement_7: null,
    nature_versement_8: null,
    montant_versement_8: null,
    nature_versement_9: null,
    montant_versement_9: null,
    nature_versement_10: null,
    montant_versement_10: null,
    nature_versement_11: null,
    montant_versement_11: null,
    nature_versement_12: null,
    montant_versement_12: null,
    etat: 'actif',
});

const submitForm = () => {
    showStoreLoader();
    form.post(route('finances.versements.store'), {
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
                                <span class="dash-payment-badge"><i class="fa fa-plus"></i></span>
                                <h5 class="title mb-0">{{ t('actions.create') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <VersementForm
                                    :form="form"
                                    :apprenants="props.apprenants"
                                    :annees-scolaires="props.anneesScolaires"
                                    :niveaux="props.niveaux"
                                    :classes="props.classes"
                                    :ecoles="props.ecoles"
                                    :campuses="props.campuses"
                                    mode="create"
                                />

                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('finances.versements.index')" class="btn btn-danger">
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
