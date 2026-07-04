<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PeriodesColairesForm from './PeriodesColairesForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    item: Object,
    annees_scolaires: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    annee_scolaire_id: page.props.item?.annee_scolaire_id || null,
    type_periode: page.props.item?.type_periode || '',
    code: page.props.item?.code || '',
    libelle: page.props.item?.libelle || '',
    numero_ordre: page.props.item?.numero_ordre || null,
    ecole_id: page.props.item?.ecole_id || null,
    date_debut: page.props.item?.date_debut || '',
    date_fin: page.props.item?.date_fin || '',
    duree: page.props.item?.duree ?? null,
    est_periode_evaluation: page.props.item?.est_periode_evaluation || false,
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
                            <PeriodesColairesForm
                                :form="form"
                                mode="show"
                                :annees_scolaires="page.props.annees_scolaires"
                                :ecoles="page.props.ecoles"
                            />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.periodes_colaires.index')" class="btn btn-danger">
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
