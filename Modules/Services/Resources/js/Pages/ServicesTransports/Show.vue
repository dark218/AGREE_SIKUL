<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ServiceTransportForm from './ServiceTransportForm.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const props = defineProps({
    item: Object,
    anneesScolaires: Array,
    ecoles: Array,
    campuses: Array,
});
</script>

<template>
    <Head :title="item.zone + ' - ' + item.ligne" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ item.zone }} - {{ item.ligne }}</h4>
        </div>
        <div class="card">
            <div class="card-body">
                <ServiceTransportForm
                    :form="item"
                    :mode="'show'"
                    :anneesScolaires="anneesScolaires"
                    :ecoles="ecoles"
                    :campuses="campuses"
                />
                <!-- Action Buttons -->
                <div class="row mt-4">
                    <div class="col">
                        <div class="text-end">
                            <Link :href="route('services-transport.index')" class="btn btn-secondary">
                                <i class="fa fa-times"></i> {{ t('actions.cancel') }}
                            </Link>
                            <Link :href="route('services-transport.edit', item.id)" class="btn btn-primary">
                                <i class="fa fa-edit"></i> {{ t('actions.edit') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
