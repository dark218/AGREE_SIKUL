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
const { isLoading, showUpdateLoader, hideLoader } = useLoader();
const page = usePage();
const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toISOString().split('T')[0];
};
const enseignant = page.props.enseignant;
const form = useForm({
    num_enseignant: enseignant?.num_enseignant || '',
    nom: enseignant?.nom || '',
    prenoms: enseignant?.prenoms || '',
    email: enseignant?.email || '',
    telephone: enseignant?.telephone || '',
    civility_title_id: enseignant?.civility_title_id || null,
    gender: enseignant?.gender || null,
    marital_status: enseignant?.marital_status || '',
    date_of_birth: formatDate(enseignant?.date_of_birth),
    place_of_birth: enseignant?.place_of_birth || '',
    commune_id: enseignant?.commune_id || null,
    department_id: enseignant?.department_id || null,
    region_id: enseignant?.region_id || null,
    country_id: enseignant?.country_id || null,
    photo: enseignant?.photo || '',
    highest_diploma: enseignant?.highest_diploma || '',
    speciality: enseignant?.speciality || '',
    year_obtained: enseignant?.year_obtained || null,
    languages: enseignant?.languages || '',
    teacher_category: enseignant?.teacher_category || '',
    teaching_speciality: enseignant?.teaching_speciality || '',
    diplome: enseignant?.diplome || '',
    date_embauche: formatDate(enseignant?.date_embauche),
    cycles_ids: enseignant?.cycles?.map(c => c.id) || [],
    niveaux_ids: enseignant?.niveaux?.map(n => n.id) || [],
    classes_ids: enseignant?.classes?.map(c => c.id) || [],
    matieres_data: enseignant?.matieres?.map(m => m.id).concat(Array(7).fill(null)).slice(0, 7) || [null, null, null, null, null, null, null],
    statut: enseignant?.statut || 'actif',
});
const submitForm = () => {
    showUpdateLoader();
    form.put(route('parametrage.enseignants.update', enseignant?.id), {
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
                        <div class="dash-payment-title-area"><h5 class="title mb-0">Modifier: {{ enseignant?.nom }}</h5></div>
                        <div class="dash-payment-body">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <EnseignantForm :form="form" mode="edit" :civility-titles="page.props.civilityTitles" :communes="page.props.communes" :departments="page.props.departments" :regions="page.props.regions" :pays="page.props.pays" :cycles="page.props.cycles" :niveaux="page.props.niveaux" :classes="page.props.classes" :matieres="page.props.matieres" />
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
