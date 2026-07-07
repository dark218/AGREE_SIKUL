<template>
    <Head :title="t('common.add_new')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.add_new') }}</h4>
        </div>
        <EcheancierForm
            ref="formRef"
            :frais="frais"
            :errors="form.errors"
            :submit-button-label="t('common.create')"
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
import EcheancierForm from './EcheancierForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    frais: { type: Array, default: () => [] },
});
const formRef = ref(null);
const isSubmitting = ref(false);
const form = ref({
    errors: {},
});
function submitForm(formData) {
    isSubmitting.value = true;
    router.post(route('finances.echeanciers.store'), formData, {
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
