<script setup>
import { ref } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import EcolageForm from './EcolageForm.vue';

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
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    niveaux: {
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
    sections: {
        type: Array,
        default: () => [],
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    postesRecettes: {
        type: Array,
        default: () => [],
    },
    comptes: {
        type: Array,
        default: () => [],
    },
});

const form = {
    annee_scolaire_id: props.item.annee_scolaire_id || null,
    niveau_id: props.item.niveau_id || null,
    ecole_id: props.item.ecole_id || null,
    campus_id: props.item.campus_id || null,
    section_id: props.item.section_id || null,
    cycle_id: props.item.cycle_id || null,
    frais: (props.item.frais || []).map(f => ({
        poste_recette_id: f.poste_recette_id || null,
        plan_compte_id: f.plan_compte_id || null,
        libelle: f.libelle || '',
        montant: f.montant != null ? Number(f.montant) : null,
        date_limite: f.date_limite ? String(f.date_limite).substring(0, 10) : '',
    })),
    etat: props.item.etat || 'actif',
    errors: {},
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
                            <EcolageForm
                                :form="form"
                                :annees-scolaires="props.anneesScolaires"
                                :niveaux="props.niveaux"
                                :ecoles="props.ecoles"
                                :campuses="props.campuses"
                                :sections="props.sections"
                                :cycles="props.cycles"
                                :postes-recettes="props.postesRecettes"
                                :comptes="props.comptes"
                                mode="show"
                            />

                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('finances.ecolage.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <Link :href="route('finances.ecolage.edit', item.id)" class="btn btn-primary">
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
