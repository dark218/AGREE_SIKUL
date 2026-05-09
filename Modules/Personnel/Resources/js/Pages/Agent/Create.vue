<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PersonnelForm from '../../Components/PersonnelForm.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    payss: Array,
    typePieces: Array,
    paysCurrent: [Number, String],
});
const formRef = ref(null);
const isSubmitting = ref(false);
const errors = ref({});
const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value;
};
function submitForm() {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    showStoreLoader();
    const formData = formRef.value.getFormData();
    router.post(route('agent.store'), formData, {
        forceFormData: true,
        preserveScroll: true,
        onError: (errs) => { errors.value = errs; isSubmitting.value = false; },
        onSuccess: () => { isSubmitting.value = false; },
        onFinish: () => { isSubmitting.value = false; hideLoader(); }
    });
}
</script>
<template>
    <Head :title="t('modules.personnel.agent.create')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.personnel.agent.create') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <div class="col-xl-12 col-lg-6 mb-20">
                                <AlertMessage />
                                <PersonnelForm ref="formRef" :payss="payss" :type-pieces="typePieces" :show-pays-field="!paysCurrent" :errors="errors" check-alias-route="agent.check-alias-smil" />
                                <div class="row mt-4">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('agent.index')" class="btn btn-danger me-2"><i class="fa fa-arrow-left"></i> {{ t('actions.back') }}</Link>
                                            <button type="button" class="btn btn-primary" :disabled="isSubmitting" @click="submitForm">
                                                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i> {{ t('actions.validate') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
