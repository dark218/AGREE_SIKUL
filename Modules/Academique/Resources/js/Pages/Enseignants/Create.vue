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
    fonctions: { type: Array, default: () => [] },
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
    fonction_id: '',
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
    // Multi-select n-n (Phase 3.1 : pivots BelongsToMany)
    matieres_ids: [],
    cycles_ids: [],
    niveaux_ids: [],
    classes_ids: [],
});

const submitForm = () => {
    showStoreLoader(t('common.saving'));
    form.post(route('academique.enseignants.store'), {
        forceFormData: true,
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
                            <!-- Bouton "Valider" géré par le FormStepper (dernière étape). -->
                            <div>
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
                                    :fonctions="fonctions"
                                    mode="create"
                                    @submit="submitForm"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-start">
                                            <Link :href="route('academique.enseignants.index')" class="btn btn-outline-secondary">
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
    </div>
    <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
</template>
