<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ExamenEnLigneForm from './ExamenEnLigneForm.vue';
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
    title: String,
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
    statuts: {
        type: Array,
        default: () => ['brouillon', 'publie', 'en_cours', 'termine', 'corrige'],
    },
});

const form = useForm({
    titre: '',
    description: '',
    instructions: '',
    planification_examen_id: null,
    classe_id: null,
    matiere_id: null,
    enseignant_id: null,
    date_debut: '',
    date_fin: '',
    nombre_heures: null,
    nombre_questions: null,
    duree_minutes: null,
    note_maximum: 20,
    note_minimum_passage: 10,
    melange_questions: false,
    melange_reponses: false,
    nombre_tentatives: 1,
    afficher_resultat: true,
    afficher_correction: false,
    retour_arriere: true,
    mot_de_passe: '',
    etat: 'brouillon',
});

const submitForm = () => {
    showStoreLoader();
    form.post(route('academique.examens-en-ligne.store'), {
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
                                <h5 class="title mb-0">{{ t('exam.create_title') || 'Créer un examen en ligne' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />

                            <!-- Info -->
                            <div class="alert alert-info mb-3 small">
                                <i class="fa fa-info-circle me-1"></i>
                                <strong>Étape 1 :</strong> Remplissez les informations de l'examen et validez.
                                <strong>Étape 2 :</strong> Vous serez redirigé vers la page de modification pour ajouter les questions.
                            </div>

                            <form @submit.prevent="submitForm">
                                <ExamenEnLigneForm
                                    :form="form"
                                    :matieres="matieres"
                                    :classes="classes"
                                    :enseignants="enseignants"
                                    :statuts="statuts"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.examens-en-ligne.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i> {{ t('exam.create_and_add_questions') || 'Créer et ajouter des questions' }}
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
