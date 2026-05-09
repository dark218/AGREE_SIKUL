<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AnneesScolairesForm from './AnneesScolairesForm.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const props = defineProps({
    item: Object,
});
const form = {
    code: props.item?.code || '',
    libelle: props.item?.libelle || '',
    date_debut: props.item?.date_debut || '',
    date_fin: props.item?.date_fin || '',
    duree: props.item?.duree || null,
    pays_id: props.item?.pays_id || null,
    etat: props.item?.etat || props.item?.statut || 'actif',
    errors: {},
};
</script>
<template>
    <Head :title="item?.libelle || 'Détail'" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ item?.libelle || 'Détail' }}</h4>
            <div class="dashboard-btn-wrapper">
                <Link :href="route('parametrage.annees_scolaires.index')" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                </Link>
                <Link v-if="item?.id" :href="route('parametrage.annees_scolaires.edit', item?.id)" class="btn btn-primary">
                    <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                </Link>
                <button v-else class="btn btn-primary" disabled>
                    <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                </button>
            </div>
        </div>
        <AlertMessage />
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <AnneesScolairesForm :form="form" mode="show" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
