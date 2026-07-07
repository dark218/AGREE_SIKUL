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
    montant_total: 0,
    mode_paiement: '',
    paiements: [], // array dynamique — mappé vers slots 1..6 au submit
    total_paye: 0,
    restant_a_payer: 0,
    etat: 'actif',
});

/**
 * Map la liste dynamique `paiements[]` vers les 12 slots hardcodés
 * `date_paiement_1..6` + `montant_paiement_1..6` attendus par le controller.
 */
const paiementsToSlots = (paiements) => {
    const slots = {};
    for (let i = 1; i <= 6; i++) {
        const p = paiements[i - 1] || {};
        slots[`date_paiement_${i}`]    = p.date    || null;
        slots[`montant_paiement_${i}`] = p.montant || null;
    }
    return slots;
};

const submitForm = () => {
    showStoreLoader();
    form
        .transform((data) => ({
            ...data,
            ...paiementsToSlots(data.paiements || []),
        }))
        .post(route('finances.achats-depenses.store'), {
            onSuccess: () => setTimeout(() => hideLoader(), 500),
            onError: () => hideLoader(),
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
                            <!-- Bouton "Valider" géré par le FormStepper (dernière étape). -->
                            <div>
                                <AchatDepenseForm
                                    :form="form"
                                    :annees-scolaires="anneesScolaires"
                                    :sections="sections"
                                    :ecoles="ecoles"
                                    mode="create"
                                    @submit="submitForm"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-start">
                                            <Link :href="route('finances.achats-depenses.index')" class="btn btn-outline-secondary">
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
        </div>
        <!-- Full Page Loader -->
        <FullPageLoader :show="isLoading" :message="loaderMessage" />
    </div>
</template>
