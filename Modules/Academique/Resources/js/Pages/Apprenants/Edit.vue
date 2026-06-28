<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ApprenantForm from './ApprenantForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
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
const form = useForm({
    nom: props.apprenant?.nom || '', prenoms: props.apprenant?.prenoms || '', matricule: props.apprenant?.matricule || '', numero_inscription: props.apprenant?.numero_inscription || '', date_naissance: formatDateForInput(props.apprenant?.date_naissance) || '', lieu_naissance: props.apprenant?.lieu_naissance || '', commune_naissance_id: props.apprenant?.commune_naissance_id || '', departement_naissance_id: props.apprenant?.departement_naissance_id || '', region_naissance_id: props.apprenant?.region_naissance_id || '', pays_naissance_id: props.apprenant?.pays_naissance_id || '', nationalite: props.apprenant?.nationalite || '', sexe: props.apprenant?.sexe || '', groupe_sanguin: props.apprenant?.groupe_sanguin || '', photo: props.apprenant?.photo || null, classe_id: props.apprenant?.classe_id || '', section_id: props.apprenant?.section_id || '', cycle_id: props.apprenant?.cycle_id || '', ecole_id: props.apprenant?.ecole_id || '', campus_id: props.apprenant?.campus_id || '', annee_scolaire_id: props.apprenant?.annee_scolaire_id || '', type_apprenant_id: props.apprenant?.type_apprenant_id || '', categorie_apprenant_id: props.apprenant?.categorie_apprenant_id || '', ecole_precedente: props.apprenant?.ecole_precedente || '', classe_precedente: props.apprenant?.classe_precedente || '', est_interne: props.apprenant?.est_interne || false, batiment: props.apprenant?.batiment || '', etage: props.apprenant?.etage || '', chambre: props.apprenant?.chambre || '', numero_lit: props.apprenant?.numero_lit || '', nom_pere: props.apprenant?.nom_pere || '', nom_mere: props.apprenant?.nom_mere || '', nom_tuteur: props.apprenant?.nom_tuteur || '', nom_responsable_legal: props.apprenant?.nom_responsable_legal || '', adresse: props.apprenant?.adresse || '', quartier_id: props.apprenant?.quartier_id || '', commune_residence_id: props.apprenant?.commune_residence_id || '', departement_residence_id: props.apprenant?.departement_residence_id || '', region_residence_id: props.apprenant?.region_residence_id || '', pays_residence_id: props.apprenant?.pays_residence_id || '', arrondissement: props.apprenant?.arrondissement || '', ville: props.apprenant?.ville || '', code_postal: props.apprenant?.code_postal || '', boite_postal: props.apprenant?.boite_postal || '', telephone: props.apprenant?.telephone || '', telephone2: props.apprenant?.telephone2 || '', email: props.apprenant?.email || '', whatsapp1: props.apprenant?.whatsapp1 || '', whatsapp2: props.apprenant?.whatsapp2 || '', date_entree_ecole: formatDateForInput(props.apprenant?.date_entree_ecole) || '', date_depart_ecole: formatDateForInput(props.apprenant?.date_depart_ecole) || '', motif_depart_ecole: props.apprenant?.motif_depart_ecole || '', statut: props.apprenant?.statut || 'actif',
});
const submitForm = () => {
    showUpdateLoader();
    // Quand on uploade un nouveau fichier (form.photo est un File), Laravel/Inertia
    // ne supporte pas PUT avec multipart → on passe par POST + _method spoofing.
    const hasNewPhotoFile = props.apprenant?.photo !== form.photo && form.photo instanceof File;
    if (hasNewPhotoFile) {
        form._method = 'put';
        form.post(route('academique.apprenants.update', props.apprenant.id), {
            forceFormData: true,
            onError: (errors) => { console.error('Form validation errors:', errors); },
            onFinish: () => { hideLoader(); }
        });
    } else {
        form.put(route('academique.apprenants.update', props.apprenant.id), {
            onError: (errors) => { console.error('Form validation errors:', errors); },
            onFinish: () => { hideLoader(); }
        });
    }
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
                                <h5 class="title mb-0">{{ t('common.edit_apprenant') || 'Modifier lapprenant' }} - {{ apprenant.nom }} {{ apprenant.prenoms }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <ApprenantForm :form="form" :classes="classes" :sections="sections" :cycles="cycles" :ecoles="ecoles" :campuses="campuses" :communes="communes" :departements="departements" :regions="regions" :pays="pays" :quartiers="quartiers" :anneesScolaires="anneesScolaires" :typesApprenant="typesApprenant" :categoriesApprenant="categoriesApprenant" mode="edit" />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.apprenants.index')" class="btn btn-danger"><i class="fa fa-arrow-left"></i> {{ t('actions.back') }}</Link>
                                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
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
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
