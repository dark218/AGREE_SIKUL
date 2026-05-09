<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import BanqueForm from './BanqueForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const form = useForm({
    code: page.props.banque?.code || '',
    libelle: page.props.banque?.libelle || '',
    pays_id: page.props.banque?.pays_id || null,
    etat: page.props.banque?.etat || 'actif',
    });
const submit = () => {
    form.put(route('parametrage.banques.update', page.props.banque?.id), {
        onError: (errors) => {
            console.error('Form validation errors:', errors);
        },
        onSuccess: () => {
        },
        onFinish: () => {
        }
    });
};
</script>
<template>
    <Head :title="t('actions.edit')" />
    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title"><i class="fa fa-pencil"></i> {{ t('actions.edit') }}</h4>
            </div>
            <form @submit.prevent="submit" class="custom-form">
                <BanqueForm :form="form" mode="edit" />
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ t('actions.save') }}</button>
                    <Link :href="route('parametrage.banques.index')" class="btn btn-secondary ms-2">
                        {{ t('actions.cancel') }}
                    </Link>
                </div>
            </form>
            <AlertMessage v-if="form.errors" type="danger" :messages="form.errors" />
        </div>
    </div>
</template>
