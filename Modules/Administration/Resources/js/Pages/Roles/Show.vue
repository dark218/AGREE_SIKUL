<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import RoleForm from './RoleForm.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
defineOptions({
    layout: DashboardLayout
});
const { t } = useI18n();
// Collapse state
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
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
</script>
<template>
    <Head :title="t('modules.administration.roles.show')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <!-- Header -->
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-eye"></i>
                                </span>
                                <h5 class="title mb-0">
                                    {{ t('modules.administration.roles.show') }}
                                </h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <div class="col-xl-12 col-lg-6 p-3">
                                <div class="">
                                    <!-- Alert Messages -->
                                    <AlertMessage />
                                    <div class=" ">
                                        <!-- Info box -->
                                       
                                        <!-- Formulaire en lecture seule -->
                                        <RoleForm
                                            :role="role"
                                            :modules="modules"
                                            :role-permissions="rolePermissions"
                                            :is-read-only="true"
                                        />
                                        <!-- Boutons d'action -->
                                        <div class="row mt-4 p-3">
                                            <div class="col">
                                                <div class="text-end">
                                                    <Link 
                                                        :href="route('administration.roles.index')" 
                                                        class="btn btn-danger"
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
    </div>
</template>
