<template>
    <Head :title="t('common.edit')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.edit') }}</h4>
        </div>
        <InscriptionTransportForm
            ref="formRef"
            :inscription-transport="item"
            :apprenants="apprenants"
            :services-transports="servicesTransports"
            :annees-scolaires="anneeScolaires"
            :errors="form.errors"
            :submit-button-label="t('common.update')"
            @submit="submitForm"
        />
        <FullPageLoader :show="isSubmitting" :message="t('common.saving')" />
    </div>
</template>
<script setup>
import { ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InscriptionTransportForm from './InscriptionTransportForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { showStoreLoader, hideLoader } = useLoader();
const page = usePage();
const props = defineProps({
    item: Object,
    apprenants: Array,
    servicesTransports: Array,
    anneeScolaires: Array,
});
const formRef = ref(null);
const isSubmitting = ref(false);
const form = ref({
    errors: {},
});
function submitForm(formData) {
    isSubmitting.value = true;
    router.post(route('inscriptions-transport.update', props.item.id), {
        ...formData,
        _method: 'PUT',
    }, {
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
