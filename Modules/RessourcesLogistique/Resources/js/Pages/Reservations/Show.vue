<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ReservationForm from './ReservationForm.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

// Format ISO date to YYYY-MM-DD for date input
const formatDateForInput = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const props = defineProps({
    reservation: Object,
    exemplaires: Array,
    apprenants: Array,
});
const form = useForm({
    exemplaire_id: props.reservation?.exemplaire_id || '',
    apprenant_id: props.reservation?.apprenant_id || '',
    date_reservation: formatDateForInput(props.reservation?.date_reservation),
    date_expiration: formatDateForInput(props.reservation?.date_expiration),
    date_notification: formatDateForInput(props.reservation?.date_notification),
    priorite: props.reservation?.priorite || 'normale',
    statut: props.reservation?.statut || 'en_attente',
});
const showDeleteConfirm = ref(false);
const isDeleting = ref(false);
function confirmDelete() {
    showDeleteConfirm.value = true;
}
function performDelete() {
    isDeleting.value = true;
    form.delete(route('reservations.destroy', props.reservation.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
}
</script>

<template>
    <Head :title="reservation?.nom || 'Réservation'" />
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
                                <h5 class="title mb-0">{{ t('common.reservation') }} - {{ reservation?.exemplaire?.code_exemplaire }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <ReservationForm
                                :form="form"
                                :exemplaires="exemplaires"
                                :apprenants="apprenants"
                                mode="show"
                            />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('reservations.index')" class="btn btn-danger">
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
        <ConfirmModal
            :show="showDeleteConfirm"
            :title="t('common.confirm_delete')"
            :message="t('common.confirm_delete_message')"
            @confirm="performDelete"
            @cancel="showDeleteConfirm = false"
        />
        <FullPageLoader :show="isDeleting" :message="t('common.deleting')" />
    </div>
</template>
