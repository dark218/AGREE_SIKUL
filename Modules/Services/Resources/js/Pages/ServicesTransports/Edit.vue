<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useForm } from '@inertiajs/vue3';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ServiceTransportForm from './ServiceTransportForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { isLoading, loaderMessage, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    item: Object,
    anneesScolaires: Array,
    ecoles: Array,
    campuses: Array,
});

const form = useForm({
    annee_scolaire_id: props.item?.annee_scolaire_id || null,
    ecole_id: props.item?.ecole_id || null,
    campus_id: props.item?.campus_id || null,
    zone: props.item?.zone || '',
    ligne: props.item?.ligne || '',
    point_depart: props.item?.point_depart || '',
    point_1: props.item?.point_1 || '',
    point_2: props.item?.point_2 || '',
    point_3: props.item?.point_3 || '',
    point_4: props.item?.point_4 || '',
    point_5: props.item?.point_5 || '',
    point_6: props.item?.point_6 || '',
    point_7: props.item?.point_7 || '',
    point_8: props.item?.point_8 || '',
    point_9: props.item?.point_9 || '',
    point_10: props.item?.point_10 || '',
    tarif_mensuel: props.item?.tarif_mensuel || null,
    tarif_trimestriel: props.item?.tarif_trimestriel || null,
    tarif_semestriel: props.item?.tarif_semestriel || null,
    tarif_annuel: props.item?.tarif_annuel || null,
    date_debut: props.item?.date_debut || null,
    date_fin: props.item?.date_fin || null,
    etat: props.item?.etat || 'actif',
});

const submitForm = () => {
    showStoreLoader();
    form.post(route('services-transport.update', props.item.id), {
        _method: 'put',
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
                router.visit(route('services-transport.index'));
            }, 500);
        },
        onError: () => {
            hideLoader();
        },
    });
};

const goBack = () => {
    router.visit(route('services-transport.show', props.item.id));
};
</script>

<template>
    <Head :title="t('common.edit') + ' - ' + item.zone" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-edit"></i></span>
                                <h5 class="title mb-0">{{ t('common.edit') }} - {{ item.zone }} - {{ item.ligne }}</h5>
                            </div>
                        </div>
                        <div class="dash-payment-body">
                            <!-- Bouton "Valider" géré par le FormStepper (dernière étape). -->
                            <div>
                                <ServiceTransportForm
                                    :form="form"
                                    :mode="'edit'"
                                    :annees-scolaires="anneesScolaires"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    @submit="submitForm"
                                />
                                <div class="row mt-4">
                                    <div class="col">
                                        <div class="text-start">
                                            <button
                                                type="button"
                                                @click="goBack"
                                                class="btn btn-outline-secondary"
                                            >
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') || t('actions.cancel') }}
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
        <!-- Full Page Loader -->
        <FullPageLoader :show="isLoading" :message="loaderMessage" />
    </div>
</template>
