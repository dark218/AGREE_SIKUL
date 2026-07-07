<script setup>
import { ref, watch, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AffectationEnseignantForm from './AffectationEnseignantForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    affectation: {
        type: Object,
        required: true,
    },
    enseignants: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
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
    institutions: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    matieres: {
        type: Array,
        default: () => [],
    },
});

const form = {
    annee_scolaire_id: props.affectation?.annee_scolaire_id || '',
    enseignant_id: props.affectation?.enseignant_id || '',
    classe_id: props.affectation?.classe_id || '',
    ecole_id: props.affectation?.ecole_id || '',
    institution_id: props.affectation?.institution_id || '',
    campus_id: props.affectation?.campus_id || '',
    // matieres[] : array d'ids MatiereUnite (pivot affectation_matieres).

    // Remplace les 21 slots hardcodés matiere_1_id..matiere_21_id.

    matieres: props.affectation?.matieres?.map((m) => m.id ?? m) || [],
    etat: props.affectation?.etat || 'actif',
    errors: {},
};

// Initialize form with affectation data when component mounts
onMounted(() => {
    if (props.affectation) {
        form.annee_scolaire_id = props.affectation.annee_scolaire_id || '';
        form.enseignant_id = props.affectation.enseignant_id || '';
        form.classe_id = props.affectation.classe_id || '';
        form.ecole_id = props.affectation.ecole_id || '';
        form.institution_id = props.affectation.institution_id || '';
        form.campus_id = props.affectation.campus_id || '';
        for (let i = 1; i <= 21; i++) {
            const field = `matiere_${i}_id`;
            form[field] = props.affectation[field] || '';
        }
        form.etat = props.affectation.etat || 'actif';
    }
});

// Watch for changes to props.affectation and update form
watch(() => props.affectation, (newAffectation) => {
    if (newAffectation) {
        form.annee_scolaire_id = newAffectation.annee_scolaire_id || '';
        form.enseignant_id = newAffectation.enseignant_id || '';
        form.classe_id = newAffectation.classe_id || '';
        form.ecole_id = newAffectation.ecole_id || '';
        form.institution_id = newAffectation.institution_id || '';
        form.campus_id = newAffectation.campus_id || '';
        for (let i = 1; i <= 21; i++) {
            const field = `matiere_${i}_id`;
            form[field] = newAffectation[field] || '';
        }
        form.etat = newAffectation.etat || 'actif';
    }
}, { deep: true });
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
                                <h5 class="title mb-0">{{ t('modules.academique.affectations_enseignants.show') || 'Voir une affectation' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AffectationEnseignantForm
                                :form="form"
                                :enseignants="enseignants"
                                :annees-scolaires="anneesScolaires"
                                :classes="classes"
                                :ecoles="ecoles"
                                :institutions="institutions"
                                :campuses="campuses"
                                :matieres="matieres"
                                mode="show"
                            />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.affectations_enseignants.index')" class="btn btn-danger">
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
</template>
