<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import CategoriesEnseignantForm from './CategoriesEnseignantForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const form = useForm({
    id: null,
    code: '',
    libelle: '',
    ecole_id: null,
    etat: 'actif',
    niveau_qualification: '',
    charge_horaire_min: null,
    charge_horaire_max: null,
    taux_horaire_base: null,
    peut_etre_titulaire: false,
    anciennete_requise: null,
});
// Populate form when component mounts and data is available
onMounted(() => {
    const item = page.props.categorieEnseignant;
    if (item) {
        Object.assign(form, {
            id: item.id,
            code: item.code || '',
            libelle: item.libelle || '',
            ecole_id: item.ecole_id || null,
            etat: item.etat || 'actif',
            niveau_qualification: item.niveau_qualification || '',
            charge_horaire_min: item.charge_horaire_min || null,
            charge_horaire_max: item.charge_horaire_max || null,
            taux_horaire_base: item.taux_horaire_base || null,
            peut_etre_titulaire: item.peut_etre_titulaire || false,
            anciennete_requise: item.anciennete_requise || null,
        });
    }
});
const submitForm = () => {
    showUpdateLoader();
    form.put(route('parametrage.categories_enseignant.update', page.props.categorieEnseignant?.id), {
        onError: (errors) => {
            console.error('Form validation errors:', errors);
        },
        onSuccess: () => {
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
                                <CategoriesEnseignantForm :form="form" :ecoles="page.props.ecoles" :pays="page.props.pays" mode="edit" />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.categories_enseignant.index')" class="btn btn-danger">
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
