<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AutreRevenuForm from './AutreRevenuForm.vue';

defineOptions({
    layout: DashboardLayout,
});

const { t } = useI18n();

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

const form = ref({
    annee_scolaire_id: props.autreRevenu?.annee_scolaire_id || '',
    niveau_id: props.autreRevenu?.niveau_id || '',
    ecole_id: props.autreRevenu?.ecole_id || '',
    campus_id: props.autreRevenu?.campus_id || '',
    uniforme: props.autreRevenu?.uniforme || null,
    tenue_mercredi: props.autreRevenu?.tenue_mercredi || null,
    tenue_sport: props.autreRevenu?.tenue_sport || null,
    autre1: props.autreRevenu?.autre1 || null,
    autre2: props.autreRevenu?.autre2 || null,
    autre3: props.autreRevenu?.autre3 || null,
    autre4: props.autreRevenu?.autre4 || null,
    autre5: props.autreRevenu?.autre5 || null,
    autre6: props.autreRevenu?.autre6 || null,
    etat: props.autreRevenu?.etat || 'actif',
});
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
                                <h5 class="title mb-0">{{ t('modules.finances.autres_revenus.show') || 'Voir un autre revenu' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <form>
                                <AutreRevenuForm
                                    :form="form"
                                    :annees-scolaires="anneesScolaires"
                                    :niveaux-etudes="niveauxEtudes"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    mode="show"
                                />
                                <!-- Buttons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('finances.autres-revenus.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <Link :href="route('finances.autres-revenus.edit', autreRevenu.id)" class="btn btn-primary">
                                                <i class="fa fa-edit"></i> {{ t('actions.edit') }}
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
