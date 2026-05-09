<script setup>
import { ref } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import EnseignantForm from './EnseignantForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, showStoreLoader, hideLoader } = useLoader();
const page = usePage();
const isCollapsed = ref(false);
const props = defineProps({
    title: String,
    civilityTitles: {
        type: Array,
        default: () => [],
    },
    communes: {
        type: Array,
        default: () => [],
    },
    departments: {
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
    cycles: {
        type: Array,
        default: () => [],
    },
    niveaux: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    matieres: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    num_enseignant: '',
    nom: '',
    prenoms: '',
    email: '',
    telephone: '',
    civility_title_id: null,
    gender: null,
    marital_status: '',
    date_of_birth: '',
    place_of_birth: '',
    commune_id: null,
    department_id: null,
    region_id: null,
    country_id: null,
    photo: '',
    highest_diploma: '',
    speciality: '',
    year_obtained: null,
    languages: '',
    teacher_category: '',
    teaching_speciality: '',
    diplome: '',
    date_embauche: '',
    cycles_ids: [],
    niveaux_ids: [],
    classes_ids: [],
    matieres_data: [null, null, null, null, null, null, null],
    statut: 'actif',
});
const submitForm = () => {
    showStoreLoader();
    form.post(route('parametrage.enseignants.store'), {
        onSuccess: () => { setTimeout(() => hideLoader(), 500); },
        onError: () => { hideLoader(); }
    });
};
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="isCollapsed = !isCollapsed">
                            <h5 class="title mb-0">{{ props.title || t('common.new_enseignant') }}</h5>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <EnseignantForm :form="form" mode="create" :civility-titles="page.props.civilityTitles" :communes="page.props.communes" :departments="page.props.departments" :regions="page.props.regions" :pays="page.props.pays" :cycles="page.props.cycles" :niveaux="page.props.niveaux" :classes="page.props.classes" :matieres="page.props.matieres" />
                                <div class="row mt-3">
                                    <div class="col text-end">
                                        <Link :href="route('parametrage.enseignants.index')" class="btn btn-danger">Retour</Link>
                                        <button type="submit" class="btn btn-primary" :disabled="form.processing">Valider</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" />
    </div>
</template>
