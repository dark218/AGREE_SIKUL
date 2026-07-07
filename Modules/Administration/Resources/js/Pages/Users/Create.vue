<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import UserForm from './UserForm.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({
    layout: DashboardLayout
});
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    roles: Array,
    pays: Array,
    statuts: Array,
    kycStatuts: Array,
    typePieces: Array,
    showPaysField: Boolean,
});
// Référence au formulaire
const userFormRef = ref(null);
const isSubmitting = ref(false);
const errors = ref({});
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
// Le stepper émet 'submit' avec le FormData déjà construit.
function submitForm(formData) {
    if (isSubmitting.value) return;
    isSubmitting.value = true;
    showStoreLoader();
    // Fallback : si l'appel vient d'ailleurs (ex. ref), on reconstruit.
    const payload = formData instanceof FormData
        ? formData
        : userFormRef.value?.getFormData();
    router.post(route('administration.users.store'), payload, {
        forceFormData: true,
        preserveScroll: true,
        onError: (errs) => {
            errors.value = errs;
            isSubmitting.value = false;
        },
        onSuccess: () => {
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
            hideLoader();
        }
    });
}
</script>
<template>
    <Head :title="t('modules.administration.users.create')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <!-- Header -->
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">
                                    {{ t('modules.administration.users.create') }}
                                </h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <div class="col-xl-12 col-lg-6 mb-20">
                                <div class="">
                                    <!-- Alert Messages -->
                                    <AlertMessage />
                                    <div class="">
                                        <!-- Bouton "Valider" géré par le FormStepper (dernière étape). -->
                                        <UserForm
                                            ref="userFormRef"
                                            :roles="roles"
                                            :payss="pays"
                                            :statuts="statuts"
                                            :kyc-statuts="kycStatuts"
                                            :type-pieces="typePieces"
                                            :show-pays-field="showPaysField"
                                            :errors="errors"
                                            @submit="submitForm"
                                        />
                                        <div class="row mt-4">
                                            <div class="col">
                                                <div class="text-start">
                                                    <Link
                                                        :href="route('administration.users.index')"
                                                        class="btn btn-outline-secondary"
                                                    >
                                                        <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
