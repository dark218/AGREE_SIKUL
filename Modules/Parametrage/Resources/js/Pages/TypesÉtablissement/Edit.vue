<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import TypesÉtablissementForm from './TypesÉtablissementForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const form = useForm({
    code: page.props.item?.code || '',
    libelle: page.props.item?.libelle || '',
    pays_id: page.props.item?.pays_id || null,
    etat: page.props.item?.etat || 'actif',
    });
const submit = () => {
    form.put(route('parametrage.types_etablissements.update', page.props.item?.id), {
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
                <TypesÉtablissementForm :form="form" mode="edit" />
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ t('actions.save') }}</button>
                    <Link :href="route('parametrage.types_etablissements.index')" class="btn btn-secondary ms-2">
                        {{ t('actions.cancel') }}
                    </Link>
                </div>
            </form>
            <AlertMessage v-if="form.errors" type="danger" :messages="form.errors" />
        </div>
    </div>
</template>
