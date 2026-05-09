<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ReservationForm from './ReservationForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, showStoreLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    exemplaires: Array,
    apprenants: Array,
});

const form = useForm({
    exemplaire_id: '',
    apprenant_id: '',
    date_reservation: '',
    date_expiration: '',
    date_notification: '',
    priorite: 'normale',
    statut: 'en_attente',
});

const submitForm = () => {
    console.log('🔵 FORM SUBMIT - Form Data:', form);
    console.log('🔵 FORM SUBMIT - Data Object:', {
        exemplaire_id: form.exemplaire_id,
        apprenant_id: form.apprenant_id,
        date_reservation: form.date_reservation,
        date_expiration: form.date_expiration,
        date_notification: form.date_notification,
        priorite: form.priorite,
        statut: form.statut,
    });

    showStoreLoader();
    form.post(route('reservations.store'), {
        onSuccess: () => {
            console.log('✅ FORM SUBMITTED SUCCESSFULLY');
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: (errors) => {
            console.error('❌ FORM SUBMISSION ERROR:', errors);
            console.error('Form errors:', form.errors);
            hideLoader();
        }
    });
};
</script>

<template>
    <Head :title="t('common.add') || 'Ajouter'" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('actions.add') }} {{ t('common.reservation') || 'Réservation' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <ReservationForm
                                    :form="form"
                                    :exemplaires="exemplaires"
                                    :apprenants="apprenants"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('reservations.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i> {{ t('actions.validate') }}
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
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
        />
    </div>
</template>
