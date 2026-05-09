<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
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
    feature: Object,
    modules: Array,
});
</script>
<template>
    <Head :title="t('modules.administration.features.show')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-eye"></i></span>
                                <h5 class="title mb-0">{{ t('modules.administration.features.show') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <form class="form-row">
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('modules.administration.permissions.fields.module') }}</label>
                                    <StylishSelect
                                        :model-value="feature.module_id"
                                        :options="modules"
                                        option-value="id"
                                        option-label="libelle"
                                        :disabled="true"
                                        :clearable="false"
                                    />
                                </div>
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('common.name') }}</label>
                                    <input :value="feature.libelle" type="text" class="form-control" disabled>
                                </div>
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('common.name') }} (EN)</label>
                                    <input :value="feature.libelle_en" type="text" class="form-control" disabled>
                                </div>
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('modules.administration.features.fields.icon') }}</label>
                                    <input :value="feature.icone" type="text" class="form-control" disabled>
                                </div>
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('common.name') }}</label>
                                    <input :value="feature.menu_url" type="text" class="form-control" disabled>
                                </div>
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('modules.administration.modules.fields.order') }}</label>
                                    <input :value="feature.ordre" type="number" class="form-control" disabled>
                                </div>
                                <div class="text-end col-12 mt-3">
                                    <Link :href="route('administration.features.index')" class="btn btn-danger">{{ t('common.back') }}</Link>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
