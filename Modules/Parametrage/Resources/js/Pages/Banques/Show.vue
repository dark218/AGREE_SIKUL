<script setup>
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
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
</script>
<template>
    <Head :title="t('actions.view')" />
    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title"><i class="fa fa-eye"></i> {{ t('actions.view') }}</h4>
            </div>
            <BanqueForm :form="form" mode="show" />
            <div class="mt-4">
                <Link :href="route('parametrage.banques.index')" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                </Link>
            </div>
        </div>
    </div>
</template>
