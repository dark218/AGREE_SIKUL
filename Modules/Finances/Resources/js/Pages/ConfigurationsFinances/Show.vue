<template>
    <Head :title="configuration.libelle" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">
                <Link :href="route('finances.configurations-finances.index')" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i>
                </Link>
                {{ configuration.libelle }}
            </h4>
        </div>
        <AlertMessage />
        <ConfigurationFinanceForm
            :configuration="configuration"
            :types="types"
            mode="show"
        />
    </div>
</template>
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfigurationFinanceForm from './ConfigurationFinanceForm.vue';
defineOptions({
    layout: DashboardLayout,
});
defineProps({
    configuration: Object,
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
