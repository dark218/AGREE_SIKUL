<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
defineOptions({
    layout: DashboardLayout
});
const props = defineProps({
    title: String,
});
const form = useForm({
    libelle: '',
    libelle_en: '',
    icone: '',
    menu_url: '',
    ordre: '',
});
// Collapse state
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
function submitForm() {
    showStoreLoader();
    form.post(route('administration.modules.store'), {
        onSuccess: () => {
            setTimeout(() => hideLoader(), 500);
        },
        onError: () => {
            hideLoader();
        },
        onFinish: () => {
            hideLoader();
        }
    });
}
</script>
<template>
    <Head :title="`${t('common.add')} - ${title}`" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <!-- Header -->
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.administration.modules.create') || 'Créer un module' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm" class="form-row">
                                <!-- Libellé -->
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('common.name') }}</label>
                                    <input
                                        v-model="form.libelle"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.libelle }"
                                        required
                                    >
                                    <div v-if="form.errors.libelle" class="invalid-feedback">{{ form.errors.libelle }}</div>
                                </div>
                                <!-- Libellé anglais -->
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('common.name') }} (EN)</label>
                                    <input
                                        v-model="form.libelle_en"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.libelle_en }"
                                    >
                                    <div v-if="form.errors.libelle_en" class="invalid-feedback">{{ form.errors.libelle_en }}</div>
                                </div>
                                <!-- Nom icône -->
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('modules.administration.modules.fields.icon') }}</label>
                                    <input
                                        v-model="form.icone"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.icone }"
                                        required
                                    >
                                    <div v-if="form.errors.icone" class="invalid-feedback">{{ form.errors.icone }}</div>
                                </div>
                                <!-- Menu -->
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('common.name') }}</label>
                                    <input
                                        v-model="form.menu_url"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.menu_url }"
                                        required
                                    >
                                    <div v-if="form.errors.menu_url" class="invalid-feedback">{{ form.errors.menu_url }}</div>
                                </div>
                                <!-- Ordre -->
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('modules.administration.modules.fields.order') }}</label>
                                    <input
                                        v-model="form.ordre"
                                        type="number"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.ordre }"
                                    >
                                    <div v-if="form.errors.ordre" class="invalid-feedback">{{ form.errors.ordre }}</div>
                                </div>
                                <!-- Boutons -->
                                <div class="text-end col-12 mt-3">
                                    <Link :href="route('administration.modules.index')" class="btn btn-danger me-2">{{ t('common.cancel') }}</Link>
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                        {{ t('common.save') }}
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
