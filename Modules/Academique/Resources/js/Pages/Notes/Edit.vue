<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import NoteForm from './NoteForm.vue';
import { useForm } from '@inertiajs/vue3';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { showUpdateLoader, hideLoader } = useLoader();
const page = usePage();
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
const handleSubmit = () => {
    showUpdateLoader();
    setTimeout(() => {
        form.put(route('academique.notes.update', props.note?.id), {
            onFinish: () => hideLoader(),
        });
    }, 500);
};
onMounted(() => {
    console.log('✅ Edit page mounted');
});
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
            </div>
            <AlertMessage />
            <div class="row m-0">
                <div class="card-body">
                    <div class="dash-payment-item">
                        <h5 class="dash-payment-title">{{ t('actions.edit') || 'Modifier' }}</h5>
                        <div class="dash-payment-body">
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
                        </div>
                        <div class="dash-payment-footer">
                            <Link href="#" @click.prevent="$router.back()" class="btn btn-danger">
                                {{ t('actions.back') || 'Retour' }}
                            </Link>
                            <button @click="handleSubmit" class="btn btn-primary">
                                {{ t('actions.validate') || 'Valider' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <FullPageLoader :show="form.processing" />
</template>
