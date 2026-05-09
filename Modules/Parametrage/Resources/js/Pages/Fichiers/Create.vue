<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FichiersForm from './FichiersForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const form = useForm({
    code: '',
    libelle: '',
    libelle_en: '',
});
const submit = () => {
    form.post(route('parametrage.fichiers.store'));
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
                <FichiersForm :form="form" mode="create" />
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ t('actions.save') }}</button>
                    <Link :href="route('parametrage.fichiers.index')" class="btn btn-secondary ms-2">
                        {{ t('actions.cancel') }}
                    </Link>
                </div>
            </form>
            <AlertMessage v-if="form.errors" type="danger" :messages="form.errors" />
        </div>
    </div>
</template>
