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
    item: Object,
    apprenants: Array,
    servicesCantines: Array,
    anneeScolaires: Array,
});

const formRef = ref(null);

const submitForm = () => {
    const formData = formRef.value?.getFormData();
    if (formData) {
        showStoreLoader();
        router.post(route('inscriptions-cantine.update', props.item.id), {
            ...formData,
            _method: 'PUT',
        }, {
            onSuccess: () => {
                setTimeout(() => {
                    hideLoader();
                }, 500);
            },
            onError: () => {
                hideLoader();
            }
        });
    }
};
</script>

<template>
    <Head :title="t('actions.edit')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <AlertMessage />
                <InscriptionCantineForm
                    ref="formRef"
                    :inscription-cantine="item"
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
