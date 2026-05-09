<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import { useLoader } from '@/composables/useLoader';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();

const props = defineProps({
    title: String,
    emploi_temps_id: Number,
    week_name: String,
    week_start_date: String,
    week_end_date: String,
    annee_scolaire_id: Number,
    classe_id: Number,
    section_id: Number,
    cycle_id: Number,
    ecole_id: Number,
    campus_id: Number,
    statut: String,
    coursesForForm: Object,
    classes: { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    campuses: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
    enseignants: { type: Array, default: () => [] },
});

const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
const joursLibelle = {
    lundi: 'Lundi', mardi: 'Mardi', mercredi: 'Mercredi',
    jeudi: 'Jeudi', vendredi: 'Vendredi', samedi: 'Samedi'
};

// Format date for date input (YYYY-MM-DD)
const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    return dateString.split('T')[0]; // Extract YYYY-MM-DD from ISO datetime
};

const form = useForm({
    week_name: props.week_name || '',
    week_start_date: formatDateForInput(props.week_start_date) || '',
    week_end_date: formatDateForInput(props.week_end_date) || '',
    annee_scolaire_id: props.annee_scolaire_id || null,
    classe_id: props.classe_id || null,
    section_id: props.section_id || null,
    cycle_id: props.cycle_id || null,
    ecole_id: props.ecole_id || null,
    campus_id: props.campus_id || null,
    statut: props.statut || 'brouillon',
    cours: {
        lundi: props.coursesForForm?.lundi || [],
        mardi: props.coursesForForm?.mardi || [],
        mercredi: props.coursesForForm?.mercredi || [],
        jeudi: props.coursesForForm?.jeudi || [],
        vendredi: props.coursesForForm?.vendredi || [],
        samedi: props.coursesForForm?.samedi || []
    }
});

// Calculate week end date and month from week_start_date
const weekInfo = computed(() => {
    if (!form.week_start_date) return { week_end_date: '', month: '', year: '' };

    try {
        const startDate = new Date(form.week_start_date);
        const endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + 5);
        const weekEndDate = endDate.toISOString().split('T')[0];
        const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        const month = months[startDate.getMonth()];
        const year = startDate.getFullYear();

        return { week_end_date: weekEndDate, month: month, year: year };
    } catch (e) {
        return { week_end_date: '', month: '', year: '' };
    }
});

// Auto-populate week_end_date when week_start_date changes
watch(
    () => form.week_start_date,
    () => {
        if (form.week_start_date) {
            const startDate = new Date(form.week_start_date);
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 5);
            form.week_end_date = endDate.toISOString().split('T')[0];
        }
    }
);

// Calculate duree from heure_debut and heure_fin
const calculateDuree = (heureDebut, heureFin) => {
    if (!heureDebut || !heureFin) return null;
    const [hdH, hdM] = heureDebut.split(':').map(Number);
    const [hfH, hfM] = heureFin.split(':').map(Number);
    const startMins = hdH * 60 + hdM;
    const endMins = hfH * 60 + hfM;
    const diffMins = Math.max(0, endMins - startMins);
    return Math.round((diffMins / 60) * 4) / 4;
};

// Add course to a day
const addCours = (jour) => {
    form.cours[jour].push({
        matiere_id: null,
        enseignant_id: null,
        heure_debut: '',
        heure_fin: '',
        duree: null
    });
};

// Remove course from a day
const removeCours = (jour, index) => {
    form.cours[jour].splice(index, 1);
};

// Update duree when times change
const updateDuree = (jour, index) => {
    const cours = form.cours[jour][index];
    if (cours.heure_debut && cours.heure_fin) {
        cours.duree = calculateDuree(cours.heure_debut, cours.heure_fin);
    }
};

// Check for overlapping courses (true overlap detection, not just exact matches)
const hasOverlappingCourses = () => {
    for (const jour of jours) {
        const cours = form.cours[jour].filter(c => c.matiere_id && c.heure_debut && c.heure_fin);

        // Check all pairs for overlaps
        for (let i = 0; i < cours.length; i++) {
            for (let j = i + 1; j < cours.length; j++) {
                const a = cours[i];
                const b = cours[j];

                // Overlap check: startA < endB AND startB < endA
                if (a.heure_debut < b.heure_fin && b.heure_debut < a.heure_fin) {
                    return {
                        detected: true,
                        jour: jour,
                        time1: `${a.heure_debut}-${a.heure_fin}`,
                        time2: `${b.heure_debut}-${b.heure_fin}`
                    };
                }
            }
        }
    }
    return { detected: false };
};

const submitForm = () => {
    // Validate no overlapping courses
    const overlap = hasOverlappingCourses();
    if (overlap.detected) {
        alert(`❌ Chevauchement détecté le ${overlap.jour}: ${overlap.time1} chevauche ${overlap.time2}!`);
        hideLoader();
        return;
    }

    showUpdateLoader();
    const id = props.emploi_temps_id;

    console.log('[EditWeek] Submitting update for ID:', id);

    form.put(route('academique.emplois_du_temps.update_week', id), {
        onError: (errors) => {
            console.error('Form validation errors:', errors);
            hideLoader();
        },
        onSuccess: () => {
            console.log('[EditWeek] Update successful');
        },
        onFinish: () => {
            hideLoader();
        }
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
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('actions.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <!-- Définition de la Semaine -->
                                <div class="row g-3 custom-input mb-4">
                                    <h6 class="fw-bold">Définition de la Semaine</h6>
                                    <div class="col-sm-6">
                                        <label class="form-label">Nom de la Semaine *</label>
                                        <input v-model="form.week_name" type="text" class="form-control" placeholder="Ex: Semaine 1" required />
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Début de semaine (Lundi) *</label>
                                        <input v-model="form.week_start_date" type="date" class="form-control" required />
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Fin de semaine (Samedi) (auto-calculée)</label>
                                        <input v-model="form.week_end_date" type="date" class="form-control" disabled />
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Informations Semaine</label>
                                        <div class="alert alert-info mb-0" v-if="weekInfo.month">
                                            {{ form.week_name }} (Semaine {{ weekInfo.month }} / {{ weekInfo.year }})
                                        </div>
                                    </div>
                                </div>

                                <!-- Affectation Scolaire -->
                                <div class="row g-3 custom-input mb-4">
                                    <h6 class="fw-bold">Affectation Scolaire</h6>
                                    <div class="col-sm-6">
                                        <label class="form-label">Année scolaire *</label>
                                        <SearchableSelect
                                            v-model="form.annee_scolaire_id"
                                            :options="anneesScolaires"
                                            optionValue="id"
                                            optionLabel="libelle"
                                            placeholder="Sélectionner"
                                            :required="true"
                                        />
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Classe *</label>
                                        <SearchableSelect
                                            v-model="form.classe_id"
                                            :options="classes"
                                            optionValue="id"
                                            optionLabel="nom"
                                            placeholder="Sélectionner"
                                            :required="true"
                                        />
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Section</label>
                                        <SearchableSelect
                                            v-model="form.section_id"
                                            :options="sections"
                                            optionValue="id"
                                            optionLabel="libelle"
                                            placeholder="Sélectionner"
                                        />
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Cycle</label>
                                        <SearchableSelect
                                            v-model="form.cycle_id"
                                            :options="cycles"
                                            optionValue="id"
                                            optionLabel="libelle"
                                            placeholder="Sélectionner"
                                        />
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">École</label>
                                        <SearchableSelect
                                            v-model="form.ecole_id"
                                            :options="ecoles"
                                            optionValue="id"
                                            optionLabel="nom"
                                            placeholder="Sélectionner"
                                        />
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Campus</label>
                                        <SearchableSelect
                                            v-model="form.campus_id"
                                            :options="campuses"
                                            optionValue="id"
                                            optionLabel="nom"
                                            placeholder="Sélectionner"
                                        />
                                    </div>
                                </div>

                                <!-- Cours par Jour -->
                                <template v-for="jour in jours" :key="jour">
                                    <div class="row g-3 custom-input mb-4">
                                        <h6 class="fw-bold">{{ joursLibelle[jour] }}</h6>
                                        <template v-for="(cours, index) in form.cours[jour]" :key="index">
                                            <div class="col-sm-6">
                                                <label class="form-label">Matière</label>
                                                <SearchableSelect
                                                    v-model="form.cours[jour][index].matiere_id"
                                                    :options="matieres"
                                                    optionValue="id"
                                                    optionLabel="libelle"
                                                    placeholder="Sélectionner"
                                                />
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label">Enseignant</label>
                                                <SearchableSelect
                                                    v-model="form.cours[jour][index].enseignant_id"
                                                    :options="enseignants"
                                                    optionValue="id"
                                                    optionLabel="libelle"
                                                    placeholder="Sélectionner"
                                                />
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label">Heure début</label>
                                                <input
                                                    v-model="form.cours[jour][index].heure_debut"
                                                    type="time"
                                                    class="form-control"
                                                    @change="updateDuree(jour, index)"
                                                />
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label">Heure fin</label>
                                                <input
                                                    v-model="form.cours[jour][index].heure_fin"
                                                    type="time"
                                                    class="form-control"
                                                    @change="updateDuree(jour, index)"
                                                />
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label">Durée (auto-calculée)</label>
                                                <input v-model="form.cours[jour][index].duree" type="text" class="form-control" disabled />
                                            </div>
                                            <div class="col-sm-6">
                                                <button type="button" @click="removeCours(jour, index)" class="btn btn-danger mt-4">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </template>
                                        <div class="col-sm-12">
                                            <button type="button" @click="addCours(jour)" class="btn btn-secondary btn-sm">
                                                + Ajouter un cours
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <!-- Statut -->
                                <div class="row g-3 custom-input mb-4">
                                    <h6 class="fw-bold">État</h6>
                                    <div class="col-sm-6">
                                        <label class="form-label">Statut *</label>
                                        <SearchableSelect
                                            v-model="form.statut"
                                            :options="[
                                                { id: 'brouillon', libelle: 'Brouillon' },
                                                { id: 'valide', libelle: 'Validé' },
                                                { id: 'publie', libelle: 'Publié' },
                                                { id: 'archive', libelle: 'Archivé' }
                                            ]"
                                            optionValue="id"
                                            optionLabel="libelle"
                                            placeholder="Sélectionner"
                                            :required="true"
                                        />
                                    </div>
                                </div>

                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.emplois_du_temps.index')" class="btn btn-danger">
                                                {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                {{ t('actions.validate') }}
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
