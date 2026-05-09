<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import VersementForm from './VersementForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();

const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    apprenants: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    niveaux: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
});

const form = {
    annee_scolaire_id: props.item.annee_scolaire_id || null,
    apprenant_id: props.item.apprenant_id || null,
    niveau_id: props.item.niveau_id || null,
    classe_id: props.item.classe_id || null,
    ecole_id: props.item.ecole_id || null,
    campus_id: props.item.campus_id || null,
    frais_dossier: props.item.frais_dossier || null,
    frais_inscription: props.item.frais_inscription || null,
    frais_scolarite: props.item.frais_scolarite || null,
    total_paye: props.item.total_paye || null,
    restant_a_payer: props.item.restant_a_payer || null,
    nature_versement_1: props.item.nature_versement_1 || null,
    montant_versement_1: props.item.montant_versement_1 || null,
    nature_versement_2: props.item.nature_versement_2 || null,
    montant_versement_2: props.item.montant_versement_2 || null,
    nature_versement_3: props.item.nature_versement_3 || null,
    montant_versement_3: props.item.montant_versement_3 || null,
    nature_versement_4: props.item.nature_versement_4 || null,
    montant_versement_4: props.item.montant_versement_4 || null,
    nature_versement_5: props.item.nature_versement_5 || null,
    montant_versement_5: props.item.montant_versement_5 || null,
    nature_versement_6: props.item.nature_versement_6 || null,
    montant_versement_6: props.item.montant_versement_6 || null,
    nature_versement_7: props.item.nature_versement_7 || null,
    montant_versement_7: props.item.montant_versement_7 || null,
    nature_versement_8: props.item.nature_versement_8 || null,
    montant_versement_8: props.item.montant_versement_8 || null,
    nature_versement_9: props.item.nature_versement_9 || null,
    montant_versement_9: props.item.montant_versement_9 || null,
    nature_versement_10: props.item.nature_versement_10 || null,
    montant_versement_10: props.item.montant_versement_10 || null,
    nature_versement_11: props.item.nature_versement_11 || null,
    montant_versement_11: props.item.montant_versement_11 || null,
    nature_versement_12: props.item.nature_versement_12 || null,
    montant_versement_12: props.item.montant_versement_12 || null,
    etat: props.item.etat || 'actif',
    apprenant: props.item.apprenant,
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
                                <span class="dash-payment-badge"><i class="fa fa-eye"></i></span>
                                <h5 class="title mb-0">{{ t('actions.view') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <VersementForm
                                :form="form"
                                :apprenants="props.apprenants"
                                :annees-scolaires="props.anneesScolaires"
                                :niveaux="props.niveaux"
                                :classes="props.classes"
                                :ecoles="props.ecoles"
                                :campuses="props.campuses"
                                mode="show"
                            />

                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('finances.versements.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <Link :href="route('finances.versements.edit', item.id)" class="btn btn-primary">
                                            <i class="fa fa-edit"></i> {{ t('actions.edit') }}
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
</template>
