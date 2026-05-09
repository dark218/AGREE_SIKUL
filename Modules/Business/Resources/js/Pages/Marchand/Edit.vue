<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MarchandForm from './MarchandForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const props = defineProps({
    marchand: Object,
    payss: Array,
    typePieces: Array,
    paysCurrent: [Number, String, null],
});
const form = useForm({
    pays_id: props.marchand.proprietaire?.pays_id || props.paysCurrent || '',
    raison_sociale: props.marchand.raison_sociale || '',
    identifiant_fiscal: props.marchand.identifiant_fiscal || '',
    type: props.marchand.type || '',
    nom: props.marchand.proprietaire?.nom || '',
    prenoms: props.marchand.proprietaire?.prenoms || '',
    email: props.marchand.proprietaire?.email || '',
    tel: props.marchand.proprietaire?.login || '',
    type_piece: props.marchand.proprietaire?.type_piece || '',
    numero_piece: props.marchand.proprietaire?.numero_piece || '',
    date_delivrance: props.marchand.proprietaire?.date_delivrance || '',
    date_naissance: props.marchand.proprietaire?.date_naissance || '',
    lieu_naissance: props.marchand.proprietaire?.lieu_naissance || '',
    lieu_delivrance: props.marchand.proprietaire?.lieu_delivrance || '',
    photoprofile_id: null,
    piecerecto_id: null,
    pieceverso_id: null,
    dfe_id: null,
    rccm_id: null,
});
const existingFiles = {
    photoprofile: props.marchand.proprietaire?.photoprofile,
    piecerecto: props.marchand.proprietaire?.piecerecto,
    pieceverso: props.marchand.proprietaire?.pieceverso,
    dfe: props.marchand.dfe,
    rccm: props.marchand.rccm,
};
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const submitForm = () => {
    showUpdateLoader();
    form.post(route('marchand.update', props.marchand.id), {
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
                                <h5 class="title mb-0">{{ t('modules.business.marchands.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <MarchandForm
                                    :form="form"
                                    :payss="payss"
                                    :typePieces="typePieces"
                                    :paysCurrent="paysCurrent"
                                    :existingFiles="existingFiles"
                                    mode="edit"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('marchand.index')" class="btn btn-danger">
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
