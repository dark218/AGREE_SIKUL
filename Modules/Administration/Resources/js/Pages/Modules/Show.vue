<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
const { t } = useI18n();
// Collapse state
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
defineOptions({
    layout: DashboardLayout
});
const props = defineProps({
    title: String,
    module: Object,
});
</script>
<template>
    <Head :title="t('modules.administration.modules.show')" />
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
                                <h5 class="title mb-0">{{ t('modules.administration.modules.show') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <form class="form-row">
                                <!-- Libellé -->
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('common.name') }}</label>
                                    <input
                                        :value="module.libelle"
                                        type="text"
                                        class="form-control"
                                        disabled
                                    >
                                </div>
                                <!-- Libellé anglais -->
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('common.name') }} (EN)</label>
                                    <input
                                        :value="module.libelle_en"
                                        type="text"
                                        class="form-control"
                                        disabled
                                    >
                                </div>
                                <!-- Nom icône -->
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('modules.administration.modules.fields.icon') }}</label>
                                    <input
                                        :value="module.icone"
                                        type="text"
                                        class="form-control"
                                        disabled
                                    >
                                </div>
                                <!-- Menu -->
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('common.name') }}</label>
                                    <input
                                        :value="module.menu_url"
                                        type="text"
                                        class="form-control"
                                        disabled
                                    >
                                </div>
                                <!-- Ordre -->
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('modules.administration.modules.fields.order') }}</label>
                                    <input
                                        :value="module.ordre"
                                        type="number"
                                        class="form-control"
                                        disabled
                                    >
                                </div>
                                <!-- Boutons -->
                                <div class="text-end col-12 mt-3">
                                    <Link :href="route('administration.modules.index')" class="btn btn-danger">{{ t('common.back') }}</Link>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
