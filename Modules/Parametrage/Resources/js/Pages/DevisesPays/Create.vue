<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import DevisesPaysForm from './DevisesPaysForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const props = defineProps({
    pays: { type: Array, default: () => [] },
    devises: { type: Array, default: () => [] },
});
const form = useForm({
    code: '',
    pays_id: null,
    devise_id: null,
    taux_change: 1,
    etat: 'actif',
});
const submit = () => {
    form.post(route('parametrage.devises_pays.store'));
};
</script>
<template>
    <Head :title="t('actions.create')" />
    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('actions.create') }}</h4>
            </div>
            <form @submit.prevent="submit" class="custom-form">
                <DevisesPaysForm :form="form" :pays="props.pays" :devises="props.devises" mode="create" />
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ t('actions.save') }}</button>
                    <Link :href="route('parametrage.devises_pays.index')" class="btn btn-secondary ms-2">
                        {{ t('actions.cancel') }}
                    </Link>
                </div>
            </form>
            <AlertMessage v-if="form.errors" type="danger" :messages="form.errors" />
        </div>
    </div>
</template>
