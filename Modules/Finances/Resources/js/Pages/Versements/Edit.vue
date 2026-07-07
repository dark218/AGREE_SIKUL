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
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();

const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };

const props = defineProps({
    item:            { type: Object, required: true },
    apprenants:      { type: Array,  default: () => [] },
    anneesScolaires: { type: Array,  default: () => [] },
    niveaux:         { type: Array,  default: () => [] },
    classes:         { type: Array,  default: () => [] },
    ecoles:          { type: Array,  default: () => [] },
    campuses:        { type: Array,  default: () => [] },
});

/**
 * Reconstruit un tableau `versements[]` depuis les slots hardcodés 1..12
 * pour l'édition — cohérent avec le mapping inverse au submit.
 */
const slotsToVersements = (item) => {
    const list = [];
    for (let i = 1; i <= 12; i++) {
        const nature  = item[`nature_versement_${i}`];
        const montant = item[`montant_versement_${i}`];
        if (nature || montant) {
            list.push({ nature: nature || '', montant: Number(montant) || 0, date: '', reference: '' });
        }
    }
    return list;
};

const versementsToSlots = (versements) => {
    const slots = {};
    for (let i = 1; i <= 12; i++) {
        const v = versements[i - 1] || {};
        slots[`nature_versement_${i}`]  = v.nature  || null;
        slots[`montant_versement_${i}`] = v.montant || null;
    }
    return slots;
};

const form = useForm({
    annee_scolaire_id: props.item.annee_scolaire_id || null,
    apprenant_id:      props.item.apprenant_id      || null,
    niveau_id:         props.item.niveau_id         || null,
    classe_id:         props.item.classe_id         || null,
    ecole_id:          props.item.ecole_id          || null,
    campus_id:         props.item.campus_id         || null,
    frais_dossier:     props.item.frais_dossier     || 0,
    frais_inscription: props.item.frais_inscription || 0,
    frais_scolarite:   props.item.frais_scolarite   || 0,
    total_paye:        props.item.total_paye        || 0,
    restant_a_payer:   props.item.restant_a_payer   || 0,
    versements:        slotsToVersements(props.item),
    etat:              props.item.etat || 'actif',
});

const submitForm = () => {
    showUpdateLoader();
    form
        .transform((data) => ({
            ...data,
            ...versementsToSlots(data.versements || []),
        }))
        .put(route('finances.versements.update', props.item.id), {
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
                                <span class="dash-payment-badge"><i class="fa fa-edit"></i></span>
                                <h5 class="title mb-0">{{ t('actions.edit') }}</h5>
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
                                    mode="edit"
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
