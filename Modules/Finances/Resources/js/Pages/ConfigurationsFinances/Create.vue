<template>
    <Head :title="t('actions.create') + ' - ' + t('common.configurations-finances')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">
                <Link :href="route('finances.configurations-finances.index')" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i>
                </Link>
                {{ t('actions.create') }} {{ t('common.configurations-finances') }}
            </h4>
        </div>
        <AlertMessage />
        <ConfigurationFinanceForm
            :types="types"
            mode="create"
        />
    </div>
</template>
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfigurationFinanceForm from './ConfigurationFinanceForm.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
defineProps({
    types: Array,
});
</script>
<style scoped>
.dashboard-header-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.title {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}
.title a {
    margin-right: 15px;
}
</style>
