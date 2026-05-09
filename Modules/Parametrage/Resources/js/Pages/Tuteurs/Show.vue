<script setup>
import { ref } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import TuteurForm from './TuteurForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const form = useForm({
    nom: page.props.tuteur?.nom || '',
    prenoms: page.props.tuteur?.prenoms || '',
    email: page.props.tuteur?.email || '',
    telephone: page.props.tuteur?.telephone || '',
    profession: page.props.tuteur?.profession || '',
    adresse: page.props.tuteur?.adresse || '',
    statut: page.props.tuteur?.statut || 'actif',
});
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="isCollapsed = !isCollapsed">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-eye"></i></span>
                                <h5 class="title mb-0">{{ t('common.tuteur') }} - {{ page.props.tuteur?.nom }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="isCollapsed = !isCollapsed">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <TuteurForm :form="form" mode="show" />
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.tuteurs.index')" class="btn btn-danger"><i class="fa fa-arrow-left"></i> {{ t('actions.back') }}</Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
