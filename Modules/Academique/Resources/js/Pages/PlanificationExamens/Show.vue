<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PlanificationExamenForm from './PlanificationExamenForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    item: Object,
    title: String,
    natures: Array,
    types: Array,
    classes: Array,
    matieres: Array,
    enseignants: Array,
});

const form = useForm({
    nature_examen_id: page.props.item?.nature_examen_id || null,
    type_examen_id: page.props.item?.type_examen_id || null,
    classe_id: page.props.item?.classe_id || null,
    matiere_id: page.props.item?.matiere_id || null,
    enseignant_id: page.props.item?.enseignant_id || null,
    jour: page.props.item?.jour || '',
    date: page.props.item?.date || '',
    heure_debut: page.props.item?.heure_debut || '',
    heure_fin: page.props.item?.heure_fin || '',
    duree: page.props.item?.duree || null,
    etat: page.props.item?.etat || 'actif',
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
                                <span class="dash-payment-badge">
                                    <i class="fa fa-eye"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('actions.view') || 'Voir' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <PlanificationExamenForm
                                :form="form"
                                :natures="natures"
                                :types="types"
                                :classes="classes"
                                :matieres="matieres"
                                :enseignants="enseignants"
                                mode="show"
                            />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.planification-examens.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
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
