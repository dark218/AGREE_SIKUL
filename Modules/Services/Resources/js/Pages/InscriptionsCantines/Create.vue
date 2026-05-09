<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import InscriptionCantineForm from './InscriptionCantineForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';

defineOptions({
    layout: DashboardLayout,
});

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

const props = defineProps({
    apprenants: Array,
    servicesCantines: Array,
    anneeScolaires: Array,
});

const formRef = ref(null);

const submitForm = () => {
    console.log('Create::submitForm() called');
    const formData = formRef.value?.getFormData();
    console.log('Form data from child component:', formData);

    if (!formData) {
        console.error('No form data returned from component');
        return;
    }

    showStoreLoader();
    console.log('Posting to:', route('inscriptions-cantine.store'));
    console.log('Full form data:', JSON.stringify(formData, null, 2));

    router.post(route('inscriptions-cantine.store'), formData, {
        onSuccess: (page) => {
            console.log('✅ Request successful');
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: (errors) => {
            console.error('❌ Request failed with errors:', errors);
            hideLoader();
        }
    });
};
</script>

<template>
    <Head :title="t('actions.create')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <AlertMessage />
                <InscriptionCantineForm
                    ref="formRef"
                    :apprenants="apprenants || []"
                    :services-cantines="servicesCantines || []"
                    :annee-scolaires="anneeScolaires || []"
                    :submit-button-label="t('actions.validate')"
                    @submit="submitForm"
                />
            </div>
        </div>
    </div>

    <!-- Full Page Loader -->
    <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
</template>
