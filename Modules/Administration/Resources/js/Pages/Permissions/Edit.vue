<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({
    layout: DashboardLayout
});
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    permission: Object,
    features: Array,
});
const form = useForm({
    feature_id: props.permission.feature_id || '',
    name: props.permission.name || '',
    libelle: props.permission.libelle || '',
});
// Collapse state
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
function submitForm() {
    showUpdateLoader();
    form.put(route('administration.permissions.update', props.permission.id), {
        onFinish: () => {
            hideLoader();
        }
    });
}
</script>
<template>
    <Head :title="t('modules.administration.permissions.edit')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.administration.permissions.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm" class="form-row">
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('modules.administration.features.title') }}</label>
                                    <StylishSelect
                                        v-model="form.feature_id"
                                        :options="features"
                                        option-value="id"
                                        option-label="libelle"
                                        :placeholder="t('modules.administration.features.title')"
                                    />
                                    <div v-if="form.errors.feature_id" class="text-danger small">{{ form.errors.feature_id }}</div>
                                </div>
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t("common.name") }}</label>
                                    <input v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.name }" required>
                                    <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                                </div>
                                <div class="text-end col-12 mt-3">
                                    <Link :href="route('administration.permissions.index')" class="btn btn-danger me-2">{{ t("common.cancel") }}</Link>
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                        Valider
                                    </button>
                                </div>
                            </form>
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
