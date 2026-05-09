<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import RenduDevoirForm from './RenduDevoirForm.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();
const page = usePage();
const props = defineProps({
    title: String,
    rendu: Object,
});
const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);
const showDeleteConfirm = ref(false);
const rendu = props.rendu || page.props.rendu;
function confirmDelete() {
    deleteMode.value = true;
    showDeleteConfirm.value = true;
}
function confirmDeactivate() {
    deactivateMode.value = true;
    deleteMode.value = false;
    showDeleteConfirm.value = true;
}
function confirmActivate() {
    activateMode.value = true;
    deleteMode.value = false;
    showDeleteConfirm.value = true;
}
function performAction() {
    if (deleteMode.value) {
        performDelete();
    } else {
        performToggleStatus();
    }
}
function performDelete() {
    showDeleteLoader();
    router.visit(route('academique.rendus_devoirs.destroy', rendu.id), {
        method: 'delete',
        onSuccess: () => {
            showDeleteConfirm.value = false;
            deleteMode.value = false;
        },
        onFinish: () => hideLoader(),
    });
}
function performToggleStatus() {
    if (deactivateMode.value || activateMode.value) {
        showDeleteLoader();
    }
    router.visit(route('academique.rendus_devoirs.statut', rendu.id), {
        method: 'put',
        onSuccess: () => {
            showDeleteConfirm.value = false;
            deactivateMode.value = false;
            activateMode.value = false;
        },
        onFinish: () => hideLoader(),
    });
}
function closeModal() {
    showDeleteConfirm.value = false;
    deleteMode.value = false;
    deactivateMode.value = false;
    activateMode.value = false;
}
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('actions.view') || 'Voir' }}</h5>
                            </div>
                        </div>
                        <div class="dash-payment-body">
                            <AlertMessage />
                            <RenduDevoirForm :form="rendu" mode="show" />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.rendus_devoirs.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                        </Link>
                                        <Link :href="route('academique.rendus_devoirs.edit', rendu.id)" class="btn btn-primary">
                                            <i class="fa fa-edit"></i> {{ t('actions.edit') || 'Éditer' }}
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
            :title="deleteMode ? t('common.confirm_delete') : (deactivateMode ? t('common.confirm_deactivate') : t('common.confirm_activate'))"
            :message="deleteMode ? t('messages.confirm.delete.message') : (deactivateMode ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message'))"
            @confirm="performAction"
            @update:show="closeModal"
        />
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
