<template>
    <Head :title="t('common.edit')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.edit') }} — Paiement du {{ paiement?.date_paiement }}</h4>
        </div>
        <PaiementForm
            ref="formRef"
            :paiement="paiement"
            :frais="frais"
            :apprenants="apprenants"
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
import PaiementForm from './PaiementForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    paiement:   Object,
    frais:      { type: Array, default: () => [] },
    apprenants: { type: Array, default: () => [] },
});
const formRef = ref(null);
const isSubmitting = ref(false);
const form = ref({
    errors: {},
});
function submitForm(formData) {
    isSubmitting.value = true;
    router.put(route('finances.paiements.update', props.paiement.id), formData, {
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
