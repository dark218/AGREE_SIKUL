<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import EnseignantForm from './EnseignantForm.vue';
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
    users: Array,
    communes: Array,
    departements: Array,
    regions: Array,
    pays: Array,
    categoriesEnseignant: Array,
    matieres: Array,
    cycles: Array,
    niveaux: Array,
    classes: Array,
    genres: { type: Array, default: () => [] },
    naturesContrat: { type: Array, default: () => [] },
    situationsMatrimoniales: { type: Array, default: () => [] },
    langues: { type: Array, default: () => [] },
    statutsEmployes: { type: Array, default: () => [] },
});

const form = useForm({
    user_id: '',
    matricule: '',
    nom: '',
    prenoms: '',
    nom_restituer: '',
    nom_jeune_fille: '',
    gender: '',
    genre_id: '',
    nature_contrat_id: '',
    marital_status: '',
    date_of_birth: '',
    place_of_birth: '',
    commune_id: '',
    department_id: '',
    region_id: '',
    country_id: '',
    nationalite: '',
    highest_diploma: '',
    speciality: '',
    year_obtained: '',
    languages: [],
    teaching_speciality: '',
    type_contrat: '',
    date_embauche: '',
    teacher_category: '',
    categorie_enseignant_id: '',
    email: '',
    telephone: '',
    photo: '',
    statut: 'actif',
    matiere_1_id: '',
    matiere_2_id: '',
    matiere_3_id: '',
    matiere_4_id: '',
    matiere_5_id: '',
    matiere_6_id: '',
    matiere_7_id: '',
    cycle_1_id: '',
    cycle_2_id: '',
    niveau_1_id: '',
    niveau_2_id: '',
    niveau_3_id: '',
    niveau_4_id: '',
    classe_1_id: '',
    classe_2_id: '',
    classe_3_id: '',
    classe_4_id: '',
    classe_5_id: '',
});

const submitForm = () => {
    showStoreLoader(t('common.saving'));
    form.post(route('academique.enseignants.store'), {
        multipart: true,
        onSuccess: () => hideLoader(),
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
                                <span class="dash-payment-badge">
                                    <i class="fa fa-plus"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('common.add_new_enseignant') || 'Créer un enseignant' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <EnseignantForm
                                    :form="form"
                                    :communes="communes"
                                    :departements="departements"
                                    :regions="regions"
                                    :pays="pays"
                                    :categoriesEnseignant="categoriesEnseignant"
                                    :matieres="matieres"
                                    :cycles="cycles"
                                    :niveaux="niveaux"
                                    :classes="classes"
                                    :genres="genres"
                                    :naturesContrat="naturesContrat"
                                    :situationsMatrimoniales="situationsMatrimoniales"
                                    :langues="langues"
                                    :statutsEmployes="statutsEmployes"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.enseignants.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary ms-2"
                                                :disabled="form.processing"
                                            >
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
    </div>
    <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
</template>
