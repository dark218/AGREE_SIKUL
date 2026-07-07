<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import VersementForm from './VersementForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };

const props = defineProps({
    apprenants:      { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    niveaux:         { type: Array, default: () => [] },
    classes:         { type: Array, default: () => [] },
    ecoles:          { type: Array, default: () => [] },
    campuses:        { type: Array, default: () => [] },
});

const form = useForm({
    annee_scolaire_id: null,
    apprenant_id: null,
    niveau_id: null,
    classe_id: null,
    ecole_id: null,
    campus_id: null,
    frais_dossier: 0,
    frais_inscription: 0,
    frais_scolarite: 0,
    total_paye: 0,
    restant_a_payer: 0,
    versements: [], // array dynamique — mappé vers slots 1..12 au submit
    etat: 'actif',
});

/**
 * Map la liste dynamique `versements[]` vers les 12 slots hardcodés
 * `nature_versement_1..12` + `montant_versement_1..12` attendus par le controller.
 * Approche transitoire : le refactor Paiements polymorphes (§10.4 Phase 3.3)
 * remplacera ce mapping par une table paiements dédiée.
 */
const versementsToSlots = (versements) => {
    const slots = {};
    for (let i = 1; i <= 12; i++) {
        const v = versements[i - 1] || {};
        slots[`nature_versement_${i}`]  = v.nature  || null;
        slots[`montant_versement_${i}`] = v.montant || null;
    }
    return slots;
};

const submitForm = () => {
    showStoreLoader();
    form
        .transform((data) => ({
            ...data,
            ...versementsToSlots(data.versements || []),
        }))
        .post(route('finances.versements.store'), {
            onFinish: () => hideLoader(),
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
                                <span class="dash-payment-badge"><i class="fa fa-plus"></i></span>
                                <h5 class="title mb-0">{{ t('actions.create') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <!-- Bouton "Valider" géré par le FormStepper (dernière étape). -->
                            <div>
                                <VersementForm
                                    :form="form"
                                    :apprenants="props.apprenants"
                                    :annees-scolaires="props.anneesScolaires"
                                    :niveaux="props.niveaux"
                                    :classes="props.classes"
                                    :ecoles="props.ecoles"
                                    :campuses="props.campuses"
                                    mode="create"
                                    @submit="submitForm"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-start">
                                            <Link :href="route('finances.versements.index')" class="btn btn-outline-secondary">
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
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
