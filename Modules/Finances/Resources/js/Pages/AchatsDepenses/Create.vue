<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import AchatDepenseForm from './AchatDepenseForm.vue';

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
    date_depense: '',
    nature_depense: '',
    tiers_fournisseur: '',
    numero_identifiant: '',
    type_piece: '',
    reference_piece: '',
    intitule_operation: '',
    montant: '',
    mode_paiement: '',
    date_paiement_1: '',
    montant_paiement_1: '',
    date_paiement_2: '',
    montant_paiement_2: '',
    date_paiement_3: '',
    montant_paiement_3: '',
    date_paiement_4: '',
    montant_paiement_4: '',
    date_paiement_5: '',
    montant_paiement_5: '',
    date_paiement_6: '',
    montant_paiement_6: '',
    montant_total_paye: '',
    restant_a_payer: '',
    etat: 'actif',
});

const submitForm = () => {
    showStoreLoader();
    form.post(route('finances.achats-depenses.store'), {
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
    <Head :title="t('modules.finances.achats_depenses.create')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-plus"></i></span>
                                <h5 class="title mb-0">{{ t('modules.finances.achats_depenses.create') || 'Ajouter un Achat / Dépense' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <AchatDepenseForm
                                    :form="form"
                                    :annees-scolaires="anneesScolaires"
                                    :sections="sections"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    mode="create"
                                />
                                <!-- Buttons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('finances.achats-depenses.index')" class="btn btn-danger">
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
