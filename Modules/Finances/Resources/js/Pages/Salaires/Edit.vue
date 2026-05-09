<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import SalaireForm from './SalaireForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, showUpdateLoader, hideLoader } = useLoader();

const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    item: Object,
    anneesScolaires: Array,
    ecoles: Array,
    campuses: Array,
});

const form = useForm({
    annee_scolaire_id: props.item?.annee_scolaire_id || '',
    ecole_id: props.item?.ecole_id || '',
    campus_id: props.item?.campus_id || '',
    nom: props.item?.nom || '',
    institution: props.item?.institution || '',
    mois_paie: props.item?.mois_paie || '',
    intitule: props.item?.intitule || '',
    noms: props.item?.noms || '',
    prenoms: props.item?.prenoms || '',
    nom_restituer: props.item?.nom_restituer || '',
    matricule_interne: props.item?.matricule_interne || '',
    matricule_cnps: props.item?.matricule_cnps || '',
    numero_identifiant: props.item?.numero_identifiant || '',
    salaire_base: props.item?.salaire_base || null,
    primes: props.item?.primes || null,
    indemnites: props.item?.indemnites || null,
    salaire_brut: props.item?.salaire_brut || null,
    retenues_fiscales: props.item?.retenues_fiscales || null,
    retenues_sociales: props.item?.retenues_sociales || null,
    autres_retenues: props.item?.autres_retenues || null,
    saisies_oppositions: props.item?.saisies_oppositions || null,
    salaire_net: props.item?.salaire_net || null,
    paiement_integral: props.item?.paiement_integral || null,
    date_paiement_integral: props.item?.date_paiement_integral || '',
    avance1: props.item?.avance1 || null,
    date_avance1: props.item?.date_avance1 || '',
    avance2: props.item?.avance2 || null,
    date_avance2: props.item?.date_avance2 || '',
    avance3: props.item?.avance3 || null,
    date_avance3: props.item?.date_avance3 || '',
    avance4: props.item?.avance4 || null,
    date_avance4: props.item?.date_avance4 || '',
    total_paye: props.item?.total_paye || null,
    restant_a_payer: props.item?.restant_a_payer || null,
    etat: props.item?.etat || 'actif',
});

const submitForm = () => {
    showUpdateLoader();
    form.put(route('finances.salaires.update', props.item.id), {
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
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-edit"></i></span>
                                <h5 class="title mb-0">{{ t('modules.finances.salaires.edit') || 'Modifier le Salaire' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <SalaireForm
                                    :form="form"
                                    :annees-scolaires="anneesScolaires"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    mode="edit"
                                />
                                <!-- Buttons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('finances.salaires.index')" class="btn btn-danger">
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
