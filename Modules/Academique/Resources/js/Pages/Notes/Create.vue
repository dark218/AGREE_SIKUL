<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import NoteForm from './NoteForm.vue';
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
    apprenants: {
        type: Array,
        default: () => [],
    },
    evaluations: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
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
    periodes: {
        type: Array,
        default: () => [],
    },
    natureExamens: {
        type: Array,
        default: () => [],
    },
    typeExamens: {
        type: Array,
        default: () => [],
    },
    matieres: {
        type: Array,
        default: () => [],
    },
    groupes: {
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
    evaluation_id: '',
    annee_scolaire_id: '',
    section_id: '',
    cycle_id: '',
    classe_id: '',
    ecole_id: '',
    campus_id: '',
    periode_id: '',
    nature_examen_id: '',
    type_examen_id: '',
    date_examen: '',
    matiere_id: '',
    groupe_id: '',
    note_originale: '',
    note_sur: '',
    enseignant_id: '',
    statut: 'en_attente',
    remarques: '',
});

const submitForm = () => {
    // DEBUG: Log form data before submission
    console.log('🔍 [DEBUG] NOTES CREATE - Submitting form');
    console.log('📋 Form data:', form.data());
    console.log('❌ Form errors:', form.errors);
    console.log('⚠️ Validation state:', {
        hasErrors: Object.keys(form.errors).length > 0,
        errorCount: Object.keys(form.errors).length
    });

    // Check required fields
    if (!form.apprenant_id) console.warn('⚠️ MISSING: apprenant_id');
    if (!form.evaluation_id) console.warn('⚠️ MISSING: evaluation_id');
    if (!form.note_originale) console.warn('⚠️ MISSING: note_originale');
    if (!form.note_sur) console.warn('⚠️ MISSING: note_sur');

    showStoreLoader();

    form.post(route('academique.notes.store'), {
        onSuccess: () => {
            console.log('✅ [SUCCESS] Note créée avec succès!');
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: (errors) => {
            console.error('❌ [ERROR] Erreur lors de la création:', errors);
            console.error('📊 Form errors after submit:', form.errors);
            console.error('🔴 Detailed errors:', {
                errors: errors,
                formErrors: form.data(),
                timestamp: new Date().toISOString()
            });
            hideLoader();
            // Show first error in console
            const firstError = Object.entries(form.errors)[0];
            if (firstError) {
                console.error(`❌ Premier problème: ${firstError[0]} = ${firstError[1]}`);
            }
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
                                <h5 class="title mb-0">{{ t('actions.create') || 'Créer' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <NoteForm
                                    :form="form"
                                    :apprenants="apprenants"
                                    :evaluations="evaluations"
                                    :anneesScolaires="anneesScolaires"
                                    :sections="sections"
                                    :cycles="cycles"
                                    :classes="classes"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    :periodes="periodes"
                                    :natureExamens="natureExamens"
                                    :typeExamens="typeExamens"
                                    :matieres="matieres"
                                    :groupes="groupes"
                                    :enseignants="enseignants"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.notes.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i> {{ t('actions.validate') || 'Valider' }}
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
