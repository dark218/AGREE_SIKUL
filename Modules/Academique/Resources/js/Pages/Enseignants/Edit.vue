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
    enseignant: Object,
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
    user_id: props.enseignant?.user_id || '',
    matricule: props.enseignant?.matricule || '',
    nom: props.enseignant?.nom || '',
    prenoms: props.enseignant?.prenoms || '',
    nom_restituer: props.enseignant?.nom_restituer || '',
    nom_jeune_fille: props.enseignant?.nom_jeune_fille || '',
    gender: props.enseignant?.gender || '',
    genre_id: props.enseignant?.genre_id || '',
    nature_contrat_id: props.enseignant?.nature_contrat_id || '',
    fonction_id: props.enseignant?.fonction_id || '',
    marital_status: props.enseignant?.marital_status || '',
    date_of_birth: props.enseignant?.date_of_birth || '',
    place_of_birth: props.enseignant?.place_of_birth || '',
    commune_id: props.enseignant?.commune_id || '',
    department_id: props.enseignant?.department_id || '',
    region_id: props.enseignant?.region_id || '',
    country_id: props.enseignant?.country_id || '',
    nationalite: props.enseignant?.nationalite || '',
    highest_diploma: props.enseignant?.highest_diploma || '',
    speciality: props.enseignant?.speciality || '',
    year_obtained: props.enseignant?.year_obtained || '',
    languages: props.enseignant?.languages || [],
    teaching_speciality: props.enseignant?.teaching_speciality || '',
    type_contrat: props.enseignant?.type_contrat || '',
    date_embauche: props.enseignant?.date_embauche || '',
    teacher_category: props.enseignant?.teacher_category || '',
    categorie_enseignant_id: props.enseignant?.categorie_enseignant_id || '',
    email: props.enseignant?.email || '',
    telephone: props.enseignant?.telephone || '',
    photo: props.enseignant?.photo || '',
    statut: props.enseignant?.statut || 'actif',
    // Multi-select n-n : les IDs sont fournis en tableaux par le controller
    // (`matieres_ids`, `cycles_ids`, etc. via pluck() sur les relations).
    matieres_ids: props.enseignant?.matieres_ids || [],
    cycles_ids:   props.enseignant?.cycles_ids   || [],
    niveaux_ids:  props.enseignant?.niveaux_ids  || [],
    classes_ids:  props.enseignant?.classes_ids  || [],
});

const submitForm = () => {
    showStoreLoader(t('common.saving'));
    // Si form.photo est une string (URL de la photo existante en base), on la
    // retire du payload — sinon Laravel rejette avec "The photo must be a file".
    // Le backend garde l'ancienne valeur inchangée quand on n'envoie pas le champ.
    if (typeof form.photo === 'string') {
        form.photo = null;
    }
    // Method spoofing : Laravel/Inertia ne supporte pas PUT en multipart →
    // on passe par POST avec _method = 'put' sur le form lui-même.
    form._method = 'put';
    form.post(route('academique.enseignants.update', props.enseignant.id), {
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
                                    <i class="fa fa-edit"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('common.edit_enseignant') || 'Modifier l\'enseignant' }} - {{ enseignant?.nom }}</h5>
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
                                    mode="edit"
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
