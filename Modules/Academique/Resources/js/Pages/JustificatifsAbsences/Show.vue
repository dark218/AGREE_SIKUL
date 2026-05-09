<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import JustificatifAbsenceForm from './JustificatifAbsenceForm.vue';
import { useForm } from '@inertiajs/vue3';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { showDeleteLoader, hideLoader } = useLoader();
const page = usePage();
const props = defineProps({
    justificatif: {
        type: Object,
        required: true,
    },
    absences: {
        type: Array,
        default: () => [],
    },
    fichiers: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    absence_id: props.justificatif?.absence_id || '',
    fichier_id: props.justificatif?.fichier_id || '',
    commentaire: props.justificatif?.commentaire || '',
});
const showDeleteModal = ref(false);
const confirmDelete = () => {
    showDeleteModal.value = true;
};
const deleteItem = () => {
    showDeleteLoader();
    router.visit(route('academique.justificatifs_absences.destroy', props.justificatif?.id), {
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
    console.log('✅ Show page mounted with justificatif:', props.justificatif?.id);
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
                            <JustificatifAbsenceForm :form="form" :absences="absences" :fichiers="fichiers" mode="show" />
                        </div>
                        <div class="dash-payment-footer">
                            <Link href="#" @click.prevent="$router.back()" class="btn btn-secondary">
                                {{ t('actions.back') || 'Retour' }}
                            </Link>
                            <Link :href="route('academique.justificatifs_absences.edit', props.justificatif?.id)" class="btn btn-primary">
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
