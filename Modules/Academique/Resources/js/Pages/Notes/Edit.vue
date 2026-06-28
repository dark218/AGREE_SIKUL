<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import NoteForm from './NoteForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };
const props = defineProps({
    note: {
        type: Object,
        required: true,
    },
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

console.log('🔵 [Edit page] Props note received:', props.note);

const form = useForm({
    apprenant_id: props.note?.apprenant_id || '',
    evaluation_id: props.note?.evaluation_id || '',
    annee_scolaire_id: props.note?.annee_scolaire_id || '',
    section_id: props.note?.section_id || '',
    cycle_id: props.note?.cycle_id || '',
    classe_id: props.note?.classe_id || '',
    ecole_id: props.note?.ecole_id || '',
    campus_id: props.note?.campus_id || '',
    periode_id: props.note?.periode_id || '',
    nature_examen_id: props.note?.nature_examen_id || '',
    type_examen_id: props.note?.type_examen_id || '',
    date_examen: props.note?.date_examen || '',
    matiere_id: props.note?.matiere_id || '',
    groupe_id: props.note?.groupe_id || '',
    note_originale: props.note?.note_originale || '',
    note_sur: props.note?.note_sur || '',
    enseignant_id: props.note?.enseignant_id || '',
    statut: props.note?.statut || 'en_attente',
    remarques: props.note?.remarques || '',
});
const submitForm = () => {
    showUpdateLoader();
    form.put(route('academique.notes.update', props.note?.id), {
        onError: () => hideLoader(),
        onFinish: () => hideLoader(),
    });
};
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-edit"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('actions.edit') || 'Modifier' }}</h5>
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
                                    mode="edit"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.notes.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button type="submit" class="btn btn-primary ms-2" :disabled="form.processing">
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
    </div>
    <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
</template>
