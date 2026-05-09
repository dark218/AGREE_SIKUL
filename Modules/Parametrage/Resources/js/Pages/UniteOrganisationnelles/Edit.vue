<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import UniteOrganisationnellesForm from './UniteOrganisationnellesForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const form = useForm({
    code: page.props.uniteOrganisationnelle?.code || '',
    libelle: page.props.uniteOrganisationnelle?.libelle || '',
    type_unite: page.props.uniteOrganisationnelle?.type_unite || '',
    responsable_id: page.props.uniteOrganisationnelle?.responsable_id || null,
    budget_annuel: page.props.uniteOrganisationnelle?.budget_annuel || null,
    effectif_max: page.props.uniteOrganisationnelle?.effectif_max || null,
    niveau_hierarchique: page.props.uniteOrganisationnelle?.niveau_hierarchique || null,
    ecole_id: page.props.uniteOrganisationnelle?.ecole_id || null,
    unite_mere_id: page.props.uniteOrganisationnelle?.unite_mere_id || null,
    etat: page.props.uniteOrganisationnelle?.etat || 'actif',
    });
const submitForm = () => {
    showStoreLoader();
    form.put(route('parametrage.unite_organisationnelles.update', page.props.uniteOrganisationnelle?.id), {
        onFinish: () => {
            hideLoader();
        }
});
};
</script>
<template>
    <Head :title="t('actions.edit')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0"><i class="fa fa-pencil"></i> {{ t('actions.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <UniteOrganisationnellesForm
                                    :form="form"
                                    :unites="page.props.unites"
                                    :ecoles="page.props.ecoles"
                                    :responsables="page.props.responsables"
                                    mode="edit"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.unite_organisationnelles.index')" class="btn btn-danger">
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
