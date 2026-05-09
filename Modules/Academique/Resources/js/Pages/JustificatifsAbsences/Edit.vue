<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import JustificatifAbsenceForm from './JustificatifAbsenceForm.vue';
import { useForm } from '@inertiajs/vue3';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { showUpdateLoader, hideLoader } = useLoader();
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
const handleSubmit = () => {
    showUpdateLoader();
    setTimeout(() => {
        form.put(route('academique.justificatifs_absences.update', props.justificatif?.id), {
            onFinish: () => hideLoader(),
        });
    }, 500);
};
onMounted(() => {
    console.log('✅ Edit page mounted with justificatif:', props.justificatif?.id);
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
                            <JustificatifAbsenceForm :form="form" :absences="absences" :fichiers="fichiers" mode="edit" />
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
