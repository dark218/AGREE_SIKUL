<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ClasseForm from './ClasseForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    ecoles: {
        type: Array,
        default: () => [],
    },
    niveaux: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    enseignants: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    nom: '',
    code_salle: '',
    libelle_affichage: '',
    ecole_id: null,
    niveau_id: null,
    campus_id: null,
    section_id: null,
    cycle_id: null,
    enseignant_titulaire_id: null,
    annee_scolaire_id: null,
    capacite_max: null,
    salle: '',
    statut: 'actif',
});
const submitForm = () => {
    console.log('🔵 [CLASSE CREATE] submitForm - FORM DATA:', form.data());
    showStoreLoader();
    form.post(route('parametrage.classes.store'), {
        onSuccess: () => {
            console.log('✅ [CLASSE CREATE] submitForm - SUCCESS');
            // Attendre que la redirection et la nouvelle page se chargent complètement
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: (errors) => {
            console.error('❌ [CLASSE CREATE] submitForm - ERROR:', errors);
            console.error('   Form errors:', form.errors);
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
                                <h5 class="title mb-0">{{ t('common.add_new') }} - {{ t('common.classe') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <ClasseForm
                                    :form="form"
                                    :ecoles="ecoles"
                                    :niveaux="niveaux"
                                    :sections="sections"
                                    :cycles="cycles"
                                    :enseignants="enseignants"
                                    :anneesScolaires="anneesScolaires"
                                    :campuses="campuses"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.classes.index')" class="btn btn-danger">
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
