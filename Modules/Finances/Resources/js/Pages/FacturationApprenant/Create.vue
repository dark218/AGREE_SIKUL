<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import FacturationApprenantForm from './FacturationApprenantForm.vue';

defineOptions({
    layout: DashboardLayout,
});

const { t } = useI18n();
const { isLoading, loaderMessage, showStoreLoader, hideLoader } = useLoader();

const props = defineProps({
    anneesScolaires: Array,
    sections: Array,
    ecoles: Array,
    campuses: Array,
    cycles: Array,
    niveaux: Array,
});

const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const form = useForm({
    annee_scolaire_id: '',
    section_id: '',
    ecole_id: '',
    campus_id: '',
    cycle_id: '',
    niveau_id: '',
    code: '',
    libelle: '',
    ligne_recette: '',
    unite_facturation: '',
    quantite: '',
    montant: '',
    date_debut_exigibilite: '',
    date_fin_exigibilite: '',
    compte_comptable: '',
    etat: 'actif',
});

const submitForm = () => {
    showStoreLoader();
    form.post(route('finances.facturation-apprenants.store'), {
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: () => {
            hideLoader();
        }
    });
};
</script>

<template>
    <Head :title="t('modules.finances.facturation_apprenant.create')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-plus"></i></span>
                                <h5 class="title mb-0">{{ t('modules.finances.facturation_apprenant.create') || 'Créer une Facturation Apprenant' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <FacturationApprenantForm
                                    :form="form"
                                    :annees-scolaires="anneesScolaires"
                                    :sections="sections"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    :cycles="cycles"
                                    :niveaux="niveaux"
                                    mode="create"
                                />
                                <!-- Buttons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('finances.facturation-apprenants.index')" class="btn btn-danger">
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
        <!-- Full Page Loader -->
        <FullPageLoader :show="isLoading" :message="loaderMessage" />
    </div>
</template>
