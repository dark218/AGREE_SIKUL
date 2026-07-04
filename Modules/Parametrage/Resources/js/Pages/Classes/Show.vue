<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ClasseForm from './ClasseForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    classe: Object,
    ecoles: { type: Array, default: () => [] },
    niveaux: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    enseignants: { type: Array, default: () => [] },
    campuses: { type: Array, default: () => [] },
});
const form = useForm({
    nom: page.props.classe?.nom || '',
    code_salle: page.props.classe?.code_salle || '',
    libelle_affichage: page.props.classe?.libelle_affichage || '',
    ecole_id: page.props.classe?.ecole_id || null,
    niveau_id: page.props.classe?.niveau_id || null,
    campus_id: page.props.classe?.campus_id || null,
    section_id: page.props.classe?.section_id || null,
    cycle_id: page.props.classe?.cycle_id || null,
    enseignant_titulaire_id: page.props.classe?.enseignant_titulaire_id || null,
    salle: page.props.classe?.salle || '',
    capacite_max: page.props.classe?.capacite_max || null,
    statut: page.props.classe?.statut || 'actif',
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
                                <h5 class="title mb-0">Classe / Salle de cours - {{ page.props.classe?.nom }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <ClasseForm
                                :form="form"
                                :ecoles="ecoles"
                                :niveaux="niveaux"
                                :sections="sections"
                                :cycles="cycles"
                                :enseignants="enseignants"
                                :campuses="campuses"
                                mode="show"
                            />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.classes.index')" class="btn btn-danger">
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
