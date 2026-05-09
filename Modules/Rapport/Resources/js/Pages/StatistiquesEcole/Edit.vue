<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import StatistiquesEcoleForm from './StatistiquesEcoleForm.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();

const props = defineProps({
    statistiquesEcole: Object,
    classes: Array,
    ecoles: Array,
    institutions: Array,
    campuses: Array,
});

const showUpdateLoader = ref(false);
const isCollapsed = ref(false);

const form = useForm({
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
});

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const submitForm = () => {
    showUpdateLoader.value = true;
    form.put(route('statistiques-ecole.update', props.statistiquesEcole.id), {
        onFinish: () => {
            showUpdateLoader.value = false;
        },
    });
};
</script>

<template>
    <div class="body-wrapper">
        <FullPageLoader :show="showUpdateLoader" />

        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-edit"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('actions.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage v-if="form.errors && Object.keys(form.errors).length > 0" type="danger" :messages="form.errors" />

                            <StatistiquesEcoleForm
                                :form="form"
                                :classes="classes"
                                :ecoles="ecoles"
                                :institutions="institutions"
                                :campuses="campuses"
                                mode="edit"
                            />

                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <button type="button" @click="submitForm" class="btn btn-primary">
                                            <i class="fa fa-save"></i> {{ t('actions.update') }}
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
