<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ExemplaireForm from './ExemplaireForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    item: Object,
    ouvrages: Array,
});

const form = useForm({
    code_exemplaire: page.props.item?.code_exemplaire || '',
    numero_serie: page.props.item?.numero_serie || '',
    ouvrage_id: page.props.item?.ouvrage_id || '',
    etat: page.props.item?.etat || '',
    localisation: page.props.item?.localisation || '',
    date_acquisition: page.props.item?.date_acquisition || '',
    date_derniere_maintenance: page.props.item?.date_derniere_maintenance || '',
    statut: page.props.item?.statut || '',
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
                                <h5 class="title mb-0">{{ t('actions.view') }} {{ item?.nom || t('common.exemplaire') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <ExemplaireForm :form="form" :ouvrages="ouvrages" mode="show" />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('exemplaires.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
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
