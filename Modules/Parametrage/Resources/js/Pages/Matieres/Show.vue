<script setup>
import { ref } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import MatiereForm from './MatiereForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const props = defineProps({
    ecoles: { type: Array, default: () => [] }
});
const form = useForm({
    code: page.props.matiere?.code || '',
    libelle: page.props.matiere?.libelle || '',
    description: page.props.matiere?.description || '',
    coefficient: page.props.matiere?.coefficient || 1,
    ecole_id: page.props.matiere?.ecole_id || null,
    statut: page.props.matiere?.statut || 'actif'
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
                                <h5 class="title mb-0">{{ t('common.matiere') }} - {{ page.props.matiere?.libelle }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="isCollapsed = !isCollapsed">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <MatiereForm :form="form" :ecoles="ecoles" mode="show" />
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.matieres.index')" class="btn btn-danger"><i class="fa fa-arrow-left"></i> {{ t('actions.back') }}</Link>
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
