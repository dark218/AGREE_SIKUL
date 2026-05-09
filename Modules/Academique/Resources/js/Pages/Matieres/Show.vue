<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import MatiereForm from './MatiereForm.vue';
import { useForm } from '@inertiajs/vue3';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { showDeleteLoader, hideLoader } = useLoader();
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
const showDeleteModal = ref(false);
const confirmDelete = () => {
    showDeleteModal.value = true;
};
const deleteItem = () => {
    showDeleteLoader();
    router.visit(route('academique.matieres.destroy', props.matiere?.id), {
        method: 'delete',
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
        },
        onFinish: () => hideLoader(),
    });
};
const closeModal = () => {
    showDeleteModal.value = false;
};
onMounted(() => {
    console.log('✅ Show page mounted with matiere:', props.matiere?.id);
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
                        <h5 class="dash-payment-title">
                            <i class="fa fa-eye"></i>
                            {{ t('actions.view') || 'Voir' }}
                        </h5>
                        <div class="dash-payment-body">
                            <MatiereForm
                                :form="form"
                                :niveaux="niveaux"
                                :sections="sections"
                                :cycles="cycles"
                                :anneesScolaires="anneesScolaires"
                                :ecoles="ecoles"
                                mode="show"
                            />
                        </div>
                        <div class="dash-payment-footer">
                            <Link href="#" @click.prevent="$router.back()" class="btn btn-secondary">
                                {{ t('actions.back') || 'Retour' }}
                            </Link>
                            <Link :href="route('academique.matieres.edit', props.matiere?.id)" class="btn btn-primary">
                                <i class="fa fa-edit"></i> {{ t('actions.edit') || 'Modifier' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="t('messages.confirm.delete.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            @close="closeModal"
            @confirm="deleteItem"
            :confirm-text="t('actions.delete')"
            confirm-class="btn-danger"
        />
        <FullPageLoader :show="form.processing" />
    </div>
</template>
