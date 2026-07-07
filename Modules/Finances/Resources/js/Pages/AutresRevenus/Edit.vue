<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import AutreRevenuForm from './AutreRevenuForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();

const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    autreRevenu: Object,
    anneesScolaires: Array,
    niveauxEtudes: Array,
    ecoles: Array,
    campuses: Array,
});

const slotsToRevenus = (item) => {
    const list = [];
    const nommes = ['uniforme', 'tenue_mercredi', 'tenue_sport'];
    for (const nature of nommes) {
        if (item?.[nature]) {
            list.push({ nature, montant: Number(item[nature]) || 0, date: '', reference: '' });
        }
    }
    for (let i = 1; i <= 6; i++) {
        const montant = item?.[`autre${i}`];
        if (montant) {
            list.push({ nature: 'autre', montant: Number(montant) || 0, date: '', reference: '' });
        }
    }
    return list;
};

const revenusToSlots = (revenus) => {
    const slots = {
        uniforme: null, tenue_mercredi: null, tenue_sport: null,
        autre1: null, autre2: null, autre3: null, autre4: null, autre5: null, autre6: null,
    };
    let autreIdx = 1;
    for (const r of revenus) {
        const nature = r.nature || '';
        if (nature === 'uniforme')            slots.uniforme       = r.montant;
        else if (nature === 'tenue_mercredi') slots.tenue_mercredi = r.montant;
        else if (nature === 'tenue_sport')    slots.tenue_sport    = r.montant;
        else if (autreIdx <= 6) {
            slots[`autre${autreIdx}`] = r.montant;
            autreIdx++;
        }
    }
    return slots;
};

const form = useForm({
    annee_scolaire_id: props.autreRevenu?.annee_scolaire_id || null,
    niveau_id:         props.autreRevenu?.niveau_id         || null,
    ecole_id:          props.autreRevenu?.ecole_id          || null,
    campus_id:         props.autreRevenu?.campus_id         || null,
    revenus:           slotsToRevenus(props.autreRevenu || {}),
    etat:              props.autreRevenu?.etat || 'actif',
});

const submitForm = () => {
    showUpdateLoader();
    form
        .transform((data) => ({
            ...data,
            ...revenusToSlots(data.revenus || []),
        }))
        .put(route('finances.autres-revenus.update', props.autreRevenu.id), {
            onSuccess: () => setTimeout(() => hideLoader(), 500),
            onError: () => hideLoader(),
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
                                <h5 class="title mb-0">{{ t('modules.finances.autres_revenus.edit') || 'Modifier un autre revenu' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <!-- Bouton "Valider" géré par le FormStepper (dernière étape). -->
                            <div>
                                <AutreRevenuForm
                                    :form="form"
                                    :annees-scolaires="anneesScolaires"
                                    :niveaux-etudes="niveauxEtudes"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    mode="edit"
                                    @submit="submitForm"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-start">
                                            <Link :href="route('finances.autres-revenus.index')" class="btn btn-outline-secondary">
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
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
