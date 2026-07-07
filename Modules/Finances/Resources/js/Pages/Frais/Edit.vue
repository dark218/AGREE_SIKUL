<template>
    <Head :title="t('common.edit')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.edit') }} - {{ item.nom || item.name || item.titre }}</h4>
        </div>
        <FraisForm
            ref="formRef"
            :frais="item"
            :types-frais="typesFrais"
            :apprenants="apprenants"
            :annees-scolaires="anneesScolaires"
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
import FraisForm from './FraisForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    item:            Object,
    typesFrais:      { type: Array, default: () => [] },
    apprenants:      { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
});
const formRef = ref(null);
const isSubmitting = ref(false);
const form = ref({
    errors: {},
});
function submitForm(formData) {
    isSubmitting.value = true;
    router.put(route('finances.frais.update', props.item.id), formData, {
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
