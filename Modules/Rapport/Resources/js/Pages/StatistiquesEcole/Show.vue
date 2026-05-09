<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import StatistiquesEcoleForm from './StatistiquesEcoleForm.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    statistiquesEcole: Object,
    classes: Array,
    ecoles: Array,
    institutions: Array,
    campuses: Array,
});

const form = computed(() => ({
    classe_id: props.statistiquesEcole?.classe_id || null,
    ecole_id: props.statistiquesEcole?.ecole_id || null,
    institution_id: props.statistiquesEcole?.institution_id || null,
    campus_id: props.statistiquesEcole?.campus_id || null,
    nombre_inscrits: props.statistiquesEcole?.nombre_inscrits || null,
    nombre_filles: props.statistiquesEcole?.nombre_filles || null,
    nombre_garcons: props.statistiquesEcole?.nombre_garcons || null,
    nombre_enseignants: props.statistiquesEcole?.nombre_enseignants || null,
    nombre_enseignants_permanent: props.statistiquesEcole?.nombre_enseignants_permanent || null,
    nombre_enseignants_vacataires: props.statistiquesEcole?.nombre_enseignants_vacataires || null,
    enseignant_referent: props.statistiquesEcole?.enseignant_referent || '',
    produits_ecole: props.statistiquesEcole?.produits_ecole || '',
    services_offerts: props.statistiquesEcole?.services_offerts || '',
    etat: props.statistiquesEcole?.etat || 'actif',
}));
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
                                <h5 class="title mb-0">{{ t('actions.view') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <StatistiquesEcoleForm :form="form" :classes="classes" :ecoles="ecoles" :institutions="institutions" :campuses="campuses" mode="show" />
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('statistiques-ecole.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <Link :href="route('statistiques-ecole.edit', statistiquesEcole?.id)" class="btn btn-primary">
                                            <i class="fa fa-edit"></i> {{ t('actions.edit') }}
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
