<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import FacturationApprenantForm from './FacturationApprenantForm.vue';

defineOptions({
    layout: DashboardLayout,
});

const { t } = useI18n();

const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    item: Object,
    anneesScolaires: Array,
    sections: Array,
    ecoles: Array,
    campuses: Array,
    cycles: Array,
    niveaux: Array,
});

const form = ref({
    annee_scolaire_id: props.item?.annee_scolaire_id || '',
    section_id: props.item?.section_id || '',
    ecole_id: props.item?.ecole_id || '',
    campus_id: props.item?.campus_id || '',
    cycle_id: props.item?.cycle_id || '',
    niveau_id: props.item?.niveau_id || '',
    code: props.item?.code || '',
    libelle: props.item?.libelle || '',
    ligne_recette: props.item?.ligne_recette || '',
    unite_facturation: props.item?.unite_facturation || '',
    quantite: props.item?.quantite || '',
    montant: props.item?.montant || '',
    date_debut_exigibilite: props.item?.date_debut_exigibilite || '',
    date_fin_exigibilite: props.item?.date_fin_exigibilite || '',
    compte_comptable: props.item?.compte_comptable || '',
    etat: props.item?.etat || 'actif',
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
                                <h5 class="title mb-0">{{ t('modules.finances.facturation_apprenant.show') || 'Voir la Facturation Apprenant' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <form>
                                <FacturationApprenantForm
                                    :form="form"
                                    :annees-scolaires="anneesScolaires"
                                    :sections="sections"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    :cycles="cycles"
                                    :niveaux="niveaux"
                                    mode="show"
                                />
                                <!-- Buttons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('finances.facturation-apprenants.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <Link :href="route('finances.facturation-apprenants.edit', item.id)" class="btn btn-primary">
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
