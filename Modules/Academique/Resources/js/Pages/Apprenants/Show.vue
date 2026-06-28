<script setup>
import { ref, reactive } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ApprenantForm from './ApprenantForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };

// Format date from "2010-03-07T00:00:00.000000Z" or "2010-03-07 00:00:00" to "2010-03-07"
const formatDateForInput = (dateString) => {
    console.log('[formatDateForInput] Input:', dateString);
    if (!dateString) {
        console.log('[formatDateForInput] Empty/null, returning empty string');
        return '';
    }
    // Handle both ISO format (2010-03-07T00:00:00.000000Z) and MySQL format (2010-03-07 00:00:00)
    const formatted = dateString.split('T')[0].split(' ')[0];
    console.log('[formatDateForInput] Formatted:', formatted);
    return formatted;
};

const props = defineProps({
    title: String,
    apprenant: Object,
    classes: { type: Array, default: () => [], },
    sections: { type: Array, default: () => [], },
    cycles: { type: Array, default: () => [], },
    ecoles: { type: Array, default: () => [], },
    campuses: { type: Array, default: () => [], },
    communes: { type: Array, default: () => [], },
    departements: { type: Array, default: () => [], },
    regions: { type: Array, default: () => [], },
    pays: { type: Array, default: () => [], },
    quartiers: { type: Array, default: () => [], },
    anneesScolaires: { type: Array, default: () => [], },
    typesApprenant: { type: Array, default: () => [], },
    categoriesApprenant: { type: Array, default: () => [], },
});
const form = reactive({
    nom: page.props.apprenant?.nom || '',
    prenoms: page.props.apprenant?.prenoms || '',
    matricule: page.props.apprenant?.matricule || '',
    numero_inscription: page.props.apprenant?.numero_inscription || '',
    date_naissance: formatDateForInput(page.props.apprenant?.date_naissance) || '',
    lieu_naissance: page.props.apprenant?.lieu_naissance || '',
    commune_naissance_id: page.props.apprenant?.commune_naissance_id || '',
    departement_naissance_id: page.props.apprenant?.departement_naissance_id || '',
    region_naissance_id: page.props.apprenant?.region_naissance_id || '',
    pays_naissance_id: page.props.apprenant?.pays_naissance_id || '',
    nationalite: page.props.apprenant?.nationalite || '',
    sexe: page.props.apprenant?.sexe || '',
    groupe_sanguin: page.props.apprenant?.groupe_sanguin || '',
    photo: page.props.apprenant?.photo || null,
    classe_id: page.props.apprenant?.classe_id || '',
    section_id: page.props.apprenant?.section_id || '',
    cycle_id: page.props.apprenant?.cycle_id || '',
    ecole_id: page.props.apprenant?.ecole_id || '',
    campus_id: page.props.apprenant?.campus_id || '',
    annee_scolaire_id: page.props.apprenant?.annee_scolaire_id || '',
    type_apprenant_id: page.props.apprenant?.type_apprenant_id || '',
    categorie_apprenant_id: page.props.apprenant?.categorie_apprenant_id || '',
    ecole_precedente: page.props.apprenant?.ecole_precedente || '',
    classe_precedente: page.props.apprenant?.classe_precedente || '',
    est_interne: page.props.apprenant?.est_interne || false,
    batiment: page.props.apprenant?.batiment || '',
    etage: page.props.apprenant?.etage || '',
    chambre: page.props.apprenant?.chambre || '',
    numero_lit: page.props.apprenant?.numero_lit || '',
    nom_pere: page.props.apprenant?.nom_pere || '',
    nom_mere: page.props.apprenant?.nom_mere || '',
    nom_tuteur: page.props.apprenant?.nom_tuteur || '',
    nom_responsable_legal: page.props.apprenant?.nom_responsable_legal || '',
    adresse: page.props.apprenant?.adresse || '',
    quartier_id: page.props.apprenant?.quartier_id || '',
    commune_residence_id: page.props.apprenant?.commune_residence_id || '',
    departement_residence_id: page.props.apprenant?.departement_residence_id || '',
    region_residence_id: page.props.apprenant?.region_residence_id || '',
    pays_residence_id: page.props.apprenant?.pays_residence_id || '',
    arrondissement: page.props.apprenant?.arrondissement || '',
    ville: page.props.apprenant?.ville || '',
    code_postal: page.props.apprenant?.code_postal || '',
    boite_postal: page.props.apprenant?.boite_postal || '',
    telephone: page.props.apprenant?.telephone || '',
    telephone2: page.props.apprenant?.telephone2 || '',
    email: page.props.apprenant?.email || '',
    whatsapp1: page.props.apprenant?.whatsapp1 || '',
    whatsapp2: page.props.apprenant?.whatsapp2 || '',
    date_entree_ecole: formatDateForInput(page.props.apprenant?.date_entree_ecole) || '',
    date_depart_ecole: formatDateForInput(page.props.apprenant?.date_depart_ecole) || '',
    motif_depart_ecole: page.props.apprenant?.motif_depart_ecole || '',
    statut: page.props.apprenant?.statut || 'actif',
    errors: {},
});

// DEBUG: Log all date values
console.log('=== SHOW PAGE DEBUG ===');
console.log('[APPRENANT DATA FROM SERVER]:', page.props.apprenant);
console.log('[FORM REACTIVE OBJECT]:', form);
console.log('[Form date_naissance]:', form.date_naissance);
console.log('[Form date_entree_ecole]:', form.date_entree_ecole);
console.log('[Form date_depart_ecole]:', form.date_depart_ecole);
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
                                <h5 class="title mb-0">{{ t('common.view_apprenant') || 'Voir lapprenant' }} - {{ apprenant.nom }} {{ apprenant.prenoms }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <ApprenantForm :form="form" :classes="classes" :sections="sections" :cycles="cycles" :ecoles="ecoles" :campuses="campuses" :communes="communes" :departements="departements" :regions="regions" :pays="pays" :quartiers="quartiers" :anneesScolaires="anneesScolaires" :typesApprenant="typesApprenant" :categoriesApprenant="categoriesApprenant" mode="show" />
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.apprenants.index')" class="btn btn-danger"><i class="fa fa-arrow-left"></i> {{ t('actions.back') }}</Link>
                                        <Link :href="route('academique.apprenants.edit', apprenant.id)" class="btn btn-warning ms-2"><i class="fa fa-pencil"></i> {{ t('actions.edit') }}</Link>
                                        <a :href="route('academique.pdf.fiche-apprenant', props.apprenant?.id || apprenant?.id)" class="btn btn-danger ms-2" target="_blank"><i class="fa fa-file-pdf me-1"></i> Fiche PDF</a>
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
