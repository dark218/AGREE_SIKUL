<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CampusForm from './CampusForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    campus: Object,
    institutions: {
        type: Array,
        default: () => [],
    },
    responsables: {
        type: Array,
        default: () => [],
    },
    paysList: {
        type: Array,
        default: () => [],
    },
    communes: {
        type: Array,
        default: () => [],
    },
    departements: {
        type: Array,
        default: () => [],
    },
    quartiers: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    code: page.props.campus?.code || '',
    nom: page.props.campus?.nom || '',
    institution_id: page.props.campus?.institution_id || null,
    adresse: page.props.campus?.adresse || '',
    ville: page.props.campus?.ville || '',
    code_postal: page.props.campus?.code_postal || '',
    boite_postale: page.props.campus?.boite_postale || '',
    quartier: page.props.campus?.quartier || '',
    commune: page.props.campus?.commune || '',
    departement: page.props.campus?.departement || '',
    region: page.props.campus?.region || '',
    pays_id: page.props.campus?.pays_id || null,
    longitude: page.props.campus?.longitude || null,
    latitude: page.props.campus?.latitude || null,
    telephone: page.props.campus?.telephone || '',
    email: page.props.campus?.email || '',
    responsable_id: page.props.campus?.responsable_id || null,
    statut: page.props.campus?.statut || 'actif',
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
                                <h5 class="title mb-0">{{ t('common.campus') }} - {{ page.props.campus?.nom }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <CampusForm
                                :form="form"
                                :institutions="institutions"
                                :responsables="responsables"
                                :paysList="paysList"
                                :communes="communes"
                                :departements="departements"
                                :quartiers="quartiers"
                                :regions="regions"
                                mode="show"
                            />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.campuses.index')" class="btn btn-danger">
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
