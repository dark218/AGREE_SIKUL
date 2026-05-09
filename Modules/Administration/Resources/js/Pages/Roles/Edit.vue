<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import RoleForm from './RoleForm.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({
    layout: DashboardLayout
});
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const props = defineProps({
    title: {
        type: String,
        default: 'Profil'
    },
    role: {
        type: Object,
        required: true
    },
    modules: {
        type: Array,
        required: true
    },
    rolePermissions: {
        type: Array,
        default: () => []
    }
});
// Référence au formulaire
const roleFormRef = ref(null);
// Collapse state
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
// Formulaire Inertia
const form = useForm({
    name: props.role.name,
    permissions: props.rolePermissions
});
// Soumettre le formulaire
function submitForm(formData) {
    form.name = formData.name;
    form.permissions = formData.permissions;
    showUpdateLoader();
    form.put(route('administration.roles.update', props.role.id), {
        preserveScroll: true,
        onError: () => {
            // Les erreurs sont gérées automatiquement par Inertia
        },
        onFinish: () => {
            hideLoader();
        }
    });
}
</script>
<template>
    <Head :title="t('modules.administration.roles.edit')" />
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
                                    {{ t('modules.administration.roles.edit') }}
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
                                        <!-- Formulaire -->
                                        <RoleForm
                                            ref="roleFormRef"
                                            :role="role"
                                            :modules="modules"
                                            :role-permissions="rolePermissions"
                                            :errors="form.errors"
                                            @submit="submitForm"
                                        />
                                        <!-- Boutons d'action -->
                                        <div class="row mt-4">
                                            <div class="col">
                                                <div class="text-end">
                                                    <Link 
                                                        :href="route('administration.roles.index')" 
                                                        class="btn btn-danger me-2"
                                                    >
                                                        <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                                    </Link>
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-primary"
                                                        :disabled="form.processing"
                                                        @click="submitForm(roleFormRef.getFormData())"
                                                    >
                                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
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
