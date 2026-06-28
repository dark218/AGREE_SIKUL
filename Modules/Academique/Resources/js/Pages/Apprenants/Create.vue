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
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    title: String,
    classes: {
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
    ecoles: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    communes: {
        type: Array,
        default: () => [],
    },
    departements: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Array,
        default: () => [],
    },
    pays: {
        type: Array,
        default: () => [],
    },
    quartiers: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    typesApprenant: {
        type: Array,
        default: () => [],
    },
    categoriesApprenant: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    // Section 1: Identité
    nom: '',
    prenoms: '',
    matricule: '',
    numero_inscription: '',
    date_naissance: '',
    lieu_naissance: '',
    commune_naissance_id: '',
    departement_naissance_id: '',
    region_naissance_id: '',
    pays_naissance_id: '',
    nationalite: '',
    sexe: '',
    groupe_sanguin: '',
    // Section 2: Scolarité
    classe_id: '',
    section_id: '',
    cycle_id: '',
    ecole_id: '',
    campus_id: '',
    annee_scolaire_id: '',
    type_apprenant_id: '',
    categorie_apprenant_id: '',
    ecole_precedente: '',
    classe_precedente: '',
    // Section 3: Hébergement
    est_interne: false,
    batiment: '',
    etage: '',
    chambre: '',
    numero_lit: '',
    // Section 4: Famille
    nom_pere: '',
    nom_mere: '',
    nom_tuteur: '',
    nom_responsable_legal: '',
    // Section 5: Adresse de résidence
    adresse: '',
    quartier_id: '',
    commune_residence_id: '',
    departement_residence_id: '',
    region_residence_id: '',
    pays_residence_id: '',
    arrondissement: '',
    ville: '',
    code_postal: '',
    boite_postal: '',
    // Section 6: Contacts
    telephone: '',
    telephone2: '',
    email: '',
    whatsapp1: '',
    whatsapp2: '',
    // Section 7: Entrée/Sortie
    date_entree_ecole: '',
    date_depart_ecole: '',
    motif_depart_ecole: '',
    // Section 8: Statut
    statut: 'actif',
    // Action après save : null | 'inscription' | 'dossier'
    next_action: null,
});

const submitForm = (nextAction = null) => {
    // Validation stricte des champs obligatoires
    if (!form.nom || !form.nom.trim()) {
        alert('❌ Le nom est obligatoire!');
        return;
    }
    if (!form.prenoms || !form.prenoms.trim()) {
        alert('❌ Les prénoms sont obligatoires!');
        return;
    }
    if (!form.matricule || !form.matricule.trim()) {
        alert('❌ Le matricule est obligatoire!');
        return;
    }

    form.next_action = nextAction;

    showStoreLoader();
    form.post(route('academique.apprenants.store'), {
        onSuccess: () => {
            setTimeout(() => hideLoader(), 500);
        },
        onError: () => {
            hideLoader();
        },
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
                                <span class="dash-payment-badge">
                                    <i class="fa fa-plus"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('common.add_new_apprenant') || 'Créer un apprenant' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm()">
                                <ApprenantForm
                                    :form="form"
                                    :classes="classes"
                                    :sections="sections"
                                    :cycles="cycles"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    :communes="communes"
                                    :departements="departements"
                                    :regions="regions"
                                    :pays="pays"
                                    :quartiers="quartiers"
                                    :anneesScolaires="anneesScolaires"
                                    :typesApprenant="typesApprenant"
                                    :categoriesApprenant="categoriesApprenant"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end d-flex flex-wrap gap-2 justify-content-end">
                                            <Link :href="route('academique.apprenants.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                                title="Enregistrer et retourner à la liste"
                                            >
                                                <span v-if="form.processing && form.next_action === null" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i> {{ t('actions.validate') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-success"
                                                :disabled="form.processing"
                                                title="Enregistrer puis passer directement à l'inscription"
                                                @click="submitForm('inscription')"
                                            >
                                                <span v-if="form.processing && form.next_action === 'inscription'" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-user-check"></i> Enregistrer et inscrire
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-info text-white"
                                                :disabled="form.processing"
                                                title="Enregistrer puis remplir le dossier de l'apprenant"
                                                @click="submitForm('dossier')"
                                            >
                                                <span v-if="form.processing && form.next_action === 'dossier'" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-folder-open"></i> Enregistrer et dossier
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
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
