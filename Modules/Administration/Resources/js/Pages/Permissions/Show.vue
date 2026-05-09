<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
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
    title: String,
    permission: Object,
    features: Array,
});
</script>
<template>
    <Head :title="t('modules.administration.permissions.show')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-eye"></i></span>
                                <h5 class="title mb-0">{{ t('modules.administration.permissions.show') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <form class="form-row">
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t('modules.administration.features.title') }}</label>
                                    <StylishSelect
                                        :model-value="permission.feature_id"
                                        :options="features"
                                        option-value="id"
                                        option-label="libelle"
                                        :disabled="true"
                                        :clearable="false"
                                    />
                                </div>
                                <div class="col-sm-12">
                                    <label class="form-label">{{ t("common.name") }}</label>
                                    <input :value="permission.name" type="text" class="form-control" disabled>
                                </div>
                                <div class="col-sm-12">
                                    <label class="form-label">Guard</label>
                                    <input :value="permission.guard_name" type="text" class="form-control" disabled>
                                </div>
                                <div class="text-end col-12 mt-3">
                                    <Link :href="route('administration.permissions.index')" class="btn btn-danger">{{ t("common.cancel") }}</Link>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
