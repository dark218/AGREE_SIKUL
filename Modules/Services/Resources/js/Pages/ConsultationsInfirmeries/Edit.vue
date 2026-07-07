<template>
    <Head :title="t('common.edit')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.edit') }} — Consultation du {{ consultation?.date_consultation }}</h4>
        </div>
        <ConsultationInfirmerieForm
            ref="formRef"
            :consultation="consultation"
            :apprenants="apprenants"
            :infirmiers="infirmiers"
            :errors="form.errors"
            :submit-button-label="t('common.update')"
            @submit="submitForm"
        />
        <FullPageLoader :show="isSubmitting" :message="t('common.saving')" />
    </div>
</template>
<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ConsultationInfirmerieForm from './ConsultationInfirmerieForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    consultation: Object,
    apprenants:   { type: Array, default: () => [] },
    infirmiers:   { type: Array, default: () => [] },
});
const formRef = ref(null);
const isSubmitting = ref(false);
const form = ref({
    errors: {},
});
function submitForm(formData) {
    isSubmitting.value = true;
    router.put(route('consultations-infirmerie.update', props.consultation.id), formData, {
        onSuccess: () => {
            isSubmitting.value = false;
        },
        onError: (errors) => {
            form.value.errors = errors;
            isSubmitting.value = false;
        },
    });
}
</script>
