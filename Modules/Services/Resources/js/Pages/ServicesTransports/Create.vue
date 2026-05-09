<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ServiceTransportForm from './ServiceTransportForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    anneesScolaires: Array,
    ecoles: Array,
    campuses: Array,
});

const form = useForm({
    zone: '',
    ligne: '',
    point_depart: '',
    point_1: '',
    point_2: '',
    point_3: '',
    point_4: '',
    point_5: '',
    point_6: '',
    point_7: '',
    point_8: '',
    point_9: '',
    point_10: '',
    tarif_mensuel: null,
    tarif_trimestriel: null,
    tarif_semestriel: null,
    tarif_annuel: null,
    date_debut: null,
    date_fin: null,
    annee_scolaire_id: null,
    ecole_id: null,
    campus_id: null,
    etat: 'actif',
});

const submitForm = () => {
    showStoreLoader();
    form.post(route('services-transport.store'), {
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: () => {
            hideLoader();
        }
    });
};
</script>

<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-plus"></i></span>
                                <h5 class="title mb-0">{{ t('modules.services.services-transport.create') || 'Créer un Service de Transport' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <ServiceTransportForm
                                    :form="form"
                                    :annees-scolaires="anneesScolaires"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    mode="create"
                                />
                                <!-- Buttons -->
                                <div class="row mt-4">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('services-transport.index')" class="btn btn-secondary">
                                                <i class="fa fa-times"></i> {{ t('actions.cancel') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-check"></i> {{ t('actions.validate') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Full Page Loader -->
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
