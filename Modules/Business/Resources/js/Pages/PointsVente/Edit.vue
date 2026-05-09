<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import PointVenteForm from './PointVenteForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const props = defineProps({
    pointvente: Object,
    payss: Array,
    zones: Array,
    marchands: Array,
    paysCurrent: [Number, String, null],
    is_marchand: Boolean,
    marchand_current: Object,
});
const form = useForm({
    marchand_id: props.pointvente.marchand_id || '',
    zone_id: props.pointvente.zone_id || '',
    nom: props.pointvente.nom || '',
    adresse: props.pointvente.adresse || '',
    telephone: props.pointvente.telephone || '',
    longitude: props.pointvente.longitude || null,
    latitude: props.pointvente.latitude || null,
    photo_id: null,
});
const existingFiles = {
    photo: props.pointvente.photo,
};
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const submitForm = () => {
    showUpdateLoader();
    form.post(route('pointvente.update', props.pointvente.id), {
        _method: 'put',
        forceFormData: true,
        onFinish: () => {
            hideLoader();
        },
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
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.business.pointsvente.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <PointVenteForm
                                    :form="form"
                                    :zones="zones"
                                    :marchands="marchands"
                                    :existingFiles="existingFiles"
                                    :is-marchand="is_marchand"
                                    :marchand-current="marchand_current"
                                    mode="edit"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('pointvente.index')" class="btn btn-danger">
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
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
