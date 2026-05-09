<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import EmployeForm from './EmployeForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const props = defineProps({
    employe: Object,
    typePieces: Array,
    typeEmployes: Array,
    marchands: Array,
    pointsVente: Array,
    is_marchand: Boolean,
    marchand_current: Object,
});
const { can } = usePermissions();
const form = useForm({
    marchand_id: props.employe.marchand_id || '',
    points_vente_id: props.employe.points_vente_id || '',
    type_employe: props.employe.type_employe || '',
    date_embauche: props.employe.date_embauche || '',
    nom: props.employe.user?.nom || '',
    prenoms: props.employe.user?.prenoms || '',
    email: props.employe.user?.email || '',
    tel: props.employe.user?.login || '',
    type_piece: props.employe.user?.type_piece || '',
    numero_piece: props.employe.user?.numero_piece || '',
    date_delivrance: props.employe.user?.date_delivrance || '',
    date_naissance: props.employe.user?.date_naissance || '',
    lieu_naissance: props.employe.user?.lieu_naissance || '',
    lieu_delivrance: props.employe.user?.lieu_delivrance || '',
    photoprofile_id: null,
    piecerecto_id: null,
    pieceverso_id: null,
});
const existingFiles = {
    photoprofile: props.employe.user?.photoprofile,
    piecerecto: props.employe.user?.piecerecto,
    pieceverso: props.employe.user?.pieceverso,
};
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const submitForm = () => {
    showUpdateLoader();
    form.post(route('employe.update', props.employe.id), {
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
                                <h5 class="title mb-0">{{ t('modules.business.employes.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <EmployeForm
                                    :form="form"
                                    :typePieces="typePieces"
                                    :typeEmployes="typeEmployes"
                                    :marchands="marchands"
                                    :pointsVente="pointsVente"
                                    :existingFiles="existingFiles"
                                    :is-marchand="is_marchand"
                                    :marchand-current="marchand_current"
                                    mode="edit"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('employe.index')" class="btn btn-danger">
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
