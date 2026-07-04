<script setup>
import { ref } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import JoursFeriesForm from './JoursFeriesForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    pays: {
        type: Array,
        default: () => [],
    },
});
// Format date to YYYY-MM-DD for HTML date input
const formatDateForInput = (dateValue) => {
    if (!dateValue) return '';
    if (typeof dateValue === 'string' && dateValue.includes('-')) return dateValue.split('T')[0];
    if (dateValue instanceof Date) return dateValue.toISOString().split('T')[0];
    return '';
};
const form = useForm({
    code: page.props.item?.code || '',
    libelle: page.props.item?.libelle || '',
    jour: page.props.item?.jour || '',
    mois: page.props.item?.mois || '',
    annee: page.props.item?.annee || '',
    date: formatDateForInput(page.props.item?.date),
    pays_id: page.props.item?.pays_id || null,
    est_recurrent: page.props.item?.est_recurrent ?? false,
    etat: page.props.item?.etat || 'actif',
    });
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-eye"></i>
                                </span>
                                <h5 class="title mb-0"><i class="fa fa-eye"></i> {{ t('actions.view') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <JoursFeriesForm :form="form" :pays="pays" mode="show" />
                            <!-- Bouton Retour et Edit -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.jours_feries.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <Link v-if="page.props.item?.id" :href="route('parametrage.jours_feries.edit', page.props.item?.id)" class="btn btn-primary ms-2">
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </Link>
                                        <button v-else class="btn btn-primary ms-2" disabled>
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </button>
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
