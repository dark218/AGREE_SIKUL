<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import MatiereForm from './MatiereForm.vue';
import { useForm } from '@inertiajs/vue3';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { showUpdateLoader, hideLoader } = useLoader();
const page = usePage();
const props = defineProps({
    matiere: {
        type: Object,
        required: true,
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
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    code: props.matiere?.code || '',
    libelle: props.matiere?.libelle || '',
    description: props.matiere?.description || '',
    coefficient: props.matiere?.coefficient || '',
    note_max: props.matiere?.note_max || '',
    niveau_id: props.matiere?.niveau_id || '',
    section_id: props.matiere?.section_id || '',
    cycle_id: props.matiere?.cycle_id || '',
    annee_scolaire_id: props.matiere?.annee_scolaire_id || '',
    ecole_id: props.matiere?.ecole_id || '',
    statut: props.matiere?.statut || 'actif',
});
const handleSubmit = () => {
    showUpdateLoader();
    setTimeout(() => {
        form.put(route('academique.matieres.update', props.matiere?.id), {
            onFinish: () => hideLoader(),
        });
    }, 500);
};
onMounted(() => {
    console.log('✅ Edit page mounted with matiere:', props.matiere?.id);
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
                            <MatiereForm
                                :form="form"
                                :niveaux="niveaux"
                                :sections="sections"
                                :cycles="cycles"
                                :anneesScolaires="anneesScolaires"
                                :ecoles="ecoles"
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
