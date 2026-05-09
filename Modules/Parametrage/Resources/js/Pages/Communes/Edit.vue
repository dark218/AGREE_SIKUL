<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import CommunesForm from './CommunesForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const hasValidationErrors = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    departements: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Array,
        default: () => [],
    },
    pays: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    code: page.props.commune?.code || '',
    libelle: page.props.commune?.libelle || '',
    departement_id: page.props.commune?.departement_id || null,
    region_id: page.props.commune?.region_id || null,
    pays_id: page.props.commune?.pays_id || null,
    etat: page.props.commune?.etat || 'actif',
});
// Check if form has any errors
const formHasErrors = computed(() => {
    return Object.keys(form.errors || {}).length > 0;
});
const submitForm = () => {
    showUpdateLoader();
    hasValidationErrors.value = false;
    form.put(route('parametrage.communes.update', page.props.commune?.id), {
        onSuccess: () => {
            hideLoader();
        },
        onError: (errors) => {
            hideLoader();
            hasValidationErrors.value = true;
            console.error('Validation errors:', errors);
        },
        onFinish: () => {
            hideLoader();
        }
    });
};
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <!-- Alert for validation errors -->
                <div v-if="hasValidationErrors" class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>{{ t('common.error') || 'Erreur' }}:</strong>
                    <div class="mt-2">
                        <div v-for="(error, field) in form.errors" :key="field" class="small">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span>{{ error }}</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" @click="hasValidationErrors = false"></button>
                </div>
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.parametrage.communes.edit') || t('actions.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <form @submit.prevent="submitForm">
                                <CommunesForm :form="form" :departements="props.departements" :regions="props.regions" :pays="props.pays" mode="edit" />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.communes.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                {{ t('actions.save') }}
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
