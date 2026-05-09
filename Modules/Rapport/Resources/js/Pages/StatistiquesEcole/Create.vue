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
    classes: Array,
    ecoles: Array,
    institutions: Array,
    campuses: Array,
});

const showStoreLoader = ref(false);

const form = useForm({
    classe_id: null,
    ecole_id: null,
    institution_id: null,
    campus_id: null,
    nombre_inscrits: null,
    nombre_filles: null,
    nombre_garcons: null,
    nombre_enseignants: null,
    nombre_enseignants_permanent: null,
    nombre_enseignants_vacataires: null,
    enseignant_referent: '',
    produits_ecole: '',
    services_offerts: '',
    etat: 'actif',
});

const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const submitForm = () => {
    showStoreLoader.value = true;
    form.post(route('statistiques-ecole.store'), {
        onFinish: () => {
            showStoreLoader.value = false;
        },
    });
};
</script>

<template>
    <div class="body-wrapper">
        <FullPageLoader :show="showStoreLoader" />

        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-exclamation"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('actions.create') }}</h5>
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
                                mode="create"
                            />

                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <button type="button" @click="submitForm" class="btn btn-primary">
                                            <i class="fa fa-save"></i> {{ t('actions.create') }}
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
