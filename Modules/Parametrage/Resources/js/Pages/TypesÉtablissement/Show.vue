<script setup>
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
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
</script>
<template>
    <Head :title="t('actions.view')" />
    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title"><i class="fa fa-eye"></i> {{ t('actions.view') }}</h4>
            </div>
            <div class="custom-form">
                <TypesÉtablissementForm :form="form" mode="show" />
                <div class="mt-4">
                    <Link v-if="page.props.item?.id" :href="route('parametrage.types_etablissements.edit', page.props.item?.id)" class="btn btn-primary">
                        <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                    </Link>
                    <button v-else class="btn btn-primary" disabled>
                        <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                    </button>
                    <Link :href="route('parametrage.types_etablissements.index')" class="btn btn-secondary ms-2">
                        <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
