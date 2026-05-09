<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import EnseignantForm from './EnseignantForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const props = defineProps({
    title: String,
});
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const enseignant = page.props.enseignant;
const form = useForm({
    num_enseignant: page.props.enseignant?.num_enseignant || '',
    nom: page.props.enseignant?.nom || '',
    prenoms: page.props.enseignant?.prenoms || '',
    email: page.props.enseignant?.email || '',
    telephone: page.props.enseignant?.telephone || '',
    civility_title_id: page.props.enseignant?.civility_title_id || null,
    gender: page.props.enseignant?.gender || null,
    marital_status: page.props.enseignant?.marital_status || '',
    date_of_birth: page.props.enseignant?.date_of_birth || '',
    place_of_birth: page.props.enseignant?.place_of_birth || '',
    commune_id: page.props.enseignant?.commune_id || null,
    department_id: page.props.enseignant?.department_id || null,
    region_id: page.props.enseignant?.region_id || null,
    country_id: page.props.enseignant?.country_id || null,
    photo: page.props.enseignant?.photo || '',
    highest_diploma: page.props.enseignant?.highest_diploma || '',
    speciality: page.props.enseignant?.speciality || '',
    year_obtained: page.props.enseignant?.year_obtained || null,
    languages: page.props.enseignant?.languages || '',
    teacher_category: page.props.enseignant?.teacher_category || '',
    teaching_speciality: page.props.enseignant?.teaching_speciality || '',
    diplome: page.props.enseignant?.diplome || '',
    date_embauche: page.props.enseignant?.date_embauche || '',
    cycles_ids: page.props.enseignant?.cycles?.map(c => c.id) || [],
    niveaux_ids: page.props.enseignant?.niveaux?.map(n => n.id) || [],
    classes_ids: page.props.enseignant?.classes?.map(c => c.id) || [],
    matieres_data: page.props.enseignant?.matieres?.map(m => m.id).concat(Array(7).fill(null)).slice(0, 7) || [null, null, null, null, null, null, null],
    statut: page.props.enseignant?.statut || 'actif',
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
                                <h5 class="title mb-0">{{ props.title || enseignant?.nom }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <EnseignantForm :form="form" mode="show" :civility-titles="page.props.civilityTitles" :communes="page.props.communes" :departments="page.props.departments" :regions="page.props.regions" :pays="page.props.pays" :cycles="page.props.cycles" :niveaux="page.props.niveaux" :classes="page.props.classes" :matieres="page.props.matieres" />
                            <!-- Boutons Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.enseignants.index')" class="btn btn-danger">
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
