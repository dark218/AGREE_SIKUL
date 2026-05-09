<script setup>
import { ref, computed, watch, onMounted } from 'vue';
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
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

const props = defineProps({
    title: String,
    classes: { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    campuses: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
    enseignants: { type: Array, default: () => [] },
    enseignantMatieres: { type: Array, default: () => [] },
});

// Filtrer les enseignants par matière sélectionnée
const getEnseignantsForMatiere = (matiereId) => {
    if (!matiereId || !props.enseignantMatieres?.length) return props.enseignants;
    const allowedIds = props.enseignantMatieres
        .filter(em => String(em.matiere_id) === String(matiereId))
        .map(em => em.enseignant_id);
    if (allowedIds.length === 0) return props.enseignants; // fallback si pas d'affectation
    return props.enseignants.filter(e => allowedIds.includes(e.id));
};

const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
const joursLibelle = {
    lundi: 'Lundi', mardi: 'Mardi', mercredi: 'Mercredi',
    jeudi: 'Jeudi', vendredi: 'Vendredi', samedi: 'Samedi'
};

const form = useForm({
    week_name: '',
    week_start_date: '',
    week_end_date: '',
    annee_scolaire_id: null,
    classe_id: null,
    section_id: null,
    cycle_id: null,
    ecole_id: null,
    campus_id: null,
    statut: 'brouillon',
    cours: {
        lundi: [],
        mardi: [],
        mercredi: [],
        jeudi: [],
        vendredi: [],
        samedi: []
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

// Fonction pour auto-remplir les champs quand la classe change
const autoFillFromClasse = async (classeId) => {
    if (!classeId) return;

    try {
        const response = await fetch(`/api/classes/${classeId}`);
        if (!response.ok) return;

        const data = await response.json();

        // Auto-remplir les champs
        form.ecole_id = data.ecole_id || null;
        form.campus_id = data.campus_id || null;
        form.section_id = data.section_id || null;
        form.cycle_id = data.cycle_id || null;
        form.annee_scolaire_id = data.annee_scolaire_id || null;
    } catch (error) {
        console.error('Erreur auto-fill:', error.message);
    }
};

// Watch sur classe_id pour appeler autoFillFromClasse
watch(
    () => form.classe_id,
    (newValue) => {
        autoFillFromClasse(newValue);
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
    return Math.round((diffMins / 60) * 4) / 4; // Round to 0.25
};

const formatDurationHM = (decimalHours) => {
    if (!decimalHours || decimalHours <= 0) return '0h0';
    const hours = Math.floor(decimalHours);
    const minutes = Math.round((decimalHours - hours) * 60);
    return `${hours}h${minutes}`;
};

const addCours = (jour) => {
    form.cours[jour].push({
        matiere_id: null,
        enseignant_id: null,
        heure_debut: '',
        heure_fin: '',
        duree: null
    });
};

const removeCours = (jour, index) => {
    form.cours[jour].splice(index, 1);
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
    // Validate no overlapping courses — avertissement, pas blocage
    const overlap = hasOverlappingCourses();
    if (overlap.detected) {
        if (!confirm(`⚠️ Chevauchement détecté le ${overlap.jour}: ${overlap.time1} chevauche ${overlap.time2}.\n\nVoulez-vous quand même enregistrer ?`)) {
            return;
        }
    }

    showStoreLoader();
    form.post(route('academique.emplois_du_temps.store_week'), {
        preserveScroll: true,
        onFinish: () => hideLoader(),
    });
};

const statutOptions = [
    { id: 'brouillon', libelle: t('common.brouillon') || 'Brouillon' },
    { id: 'valide', libelle: t('common.valide') || 'Validé' },
    { id: 'publie', libelle: t('common.publie') || 'Publié' },
    { id: 'archive', libelle: t('common.archive') || 'Archivé' },
];

// Labels auto-remplis pour affichage grisé
const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '—';
};
const sectionLabel = computed(() => autoLabel(props.sections, form.section_id));
const cycleLabel = computed(() => autoLabel(props.cycles, form.cycle_id));
const ecoleLabel = computed(() => autoLabel(props.ecoles, form.ecole_id));
const campusLabel = computed(() => autoLabel(props.campuses, form.campus_id));
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
                                <h5 class="title mb-0">{{ t('actions.create') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>

                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm" class="row g-3 custom-input">

                                <!-- ==================== SECTION 1: DÉFINITION DE LA SEMAINE ==================== -->
                                <div class="col-12">
                                    <h5 class="section-title mb-3">{{ t('fields.week_definition') || 'Définition de la Semaine' }}</h5>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.week_name') || 'Nom de la Semaine' }} <span class="text-danger">*</span></label>
                                        <input
                                            v-model="form.week_name"
                                            type="text"
                                            class="form-control"
                                            placeholder="Ex: Semaine 1, Semaine 2, Semaine du 1-7 janvier"
                                        />
                                        <span v-if="form.errors?.week_name" class="text-danger">
                                            <strong>{{ form.errors.week_name }}</strong>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.week_start_date') || 'Début de semaine (Lundi)' }} <span class="text-danger">*</span></label>
                                        <input
                                            v-model="form.week_start_date"
                                            type="date"
                                            class="form-control"
                                        />
                                        <span v-if="form.errors?.week_start_date" class="text-danger">
                                            <strong>{{ form.errors.week_start_date }}</strong>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.week_end_date') || 'Fin de semaine (Samedi)' }} (auto-calculée)</label>
                                        <input
                                            v-model="form.week_end_date"
                                            type="date"
                                            class="form-control"
                                            disabled
                                        />
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.week_info') || 'Informations Semaine' }}</label>
                                        <div class="alert alert-info mb-0">
                                            <strong v-if="form.week_name && weekInfo.month">
                                                {{ form.week_name }} (Semaine {{ weekInfo.month }} / {{ weekInfo.year }})
                                            </strong>
                                            <span v-else-if="form.week_name" class="text-muted">
                                                {{ form.week_name }}
                                            </span>
                                            <span v-else class="text-muted">Entrez le nom de la semaine et sélectionnez une date</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- ==================== SECTION 2: AFFECTATION SCOLAIRE ==================== -->
                                <div class="col-12 mt-4">
                                    <h5 class="section-title mb-3">{{ t('fields.school_assignment') || 'Affectation Scolaire' }}</h5>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.annee_scolaire') || 'Année Scolaire' }} <span class="text-danger">*</span></label>
                                        <SearchableSelect
                                            v-model="form.annee_scolaire_id"
                                            :options="anneesScolaires"
                                            optionValue="id"
                                            optionLabel="libelle"
                                            :placeholder="t('actions.select') || '-- Sélectionner --'"
                                        />
                                        <span v-if="form.errors?.annee_scolaire_id" class="text-danger">
                                            <strong>{{ form.errors.annee_scolaire_id }}</strong>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label>{{ t('common.classe') || 'Classe' }} <span class="text-danger">*</span></label>
                                        <SearchableSelect
                                            v-model="form.classe_id"
                                            :options="classes"
                                            optionValue="id"
                                            optionLabel="nom"
                                            :placeholder="t('actions.select') || '-- Sélectionner --'"
                                        />
                                        <span v-if="form.errors?.classe_id" class="text-danger">
                                            <strong>{{ form.errors.classe_id }}</strong>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.section') || 'Section' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                                        <input type="text" class="form-control" :value="sectionLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.cycle') || 'Cycle' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                                        <input type="text" class="form-control" :value="cycleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.ecole') || 'École' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                                        <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.campus') || 'Campus' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                                        <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.status') || 'Statut' }} <span class="text-danger">*</span></label>
                                        <SearchableSelect
                                            v-model="form.statut"
                                            :options="statutOptions"
                                            optionValue="id"
                                            optionLabel="libelle"
                                        />
                                        <span v-if="form.errors?.statut" class="text-danger">
                                            <strong>{{ form.errors.statut }}</strong>
                                        </span>
                                    </div>
                                </div>

                                <!-- ==================== SECTIONS PAR JOUR ==================== -->
                                <template v-for="jour in jours" :key="jour">
                                    <div class="col-12 mt-4">
                                        <h5 class="section-title mb-3" style="text-transform: uppercase;">{{ joursLibelle[jour] }}</h5>
                                    </div>

                                    <div class="col-12">
                                        <div v-if="form.cours[jour].length === 0" class="alert alert-secondary mb-3">
                                            Aucun cours défini pour ce jour
                                        </div>

                                        <div v-for="(cours, index) in form.cours[jour]" :key="index" class="border p-3 mb-3 rounded">
                                            <div class="row g-2">
                                                <div class="col-sm-6">
                                                    <label class="form-label">{{ t('fields.matiere') || 'Matière' }}</label>
                                                    <SearchableSelect
                                                        v-model="cours.matiere_id"
                                                        :options="matieres"
                                                        optionValue="id"
                                                        optionLabel="libelle"
                                                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                                                    />
                                                </div>

                                                <div class="col-sm-6">
                                                    <label class="form-label">{{ t('fields.enseignant') || 'Enseignant' }}</label>
                                                    <SearchableSelect
                                                        v-model="cours.enseignant_id"
                                                        :options="getEnseignantsForMatiere(cours.matiere_id)"
                                                        optionValue="id"
                                                        optionLabel="libelle"
                                                        :placeholder="cours.matiere_id ? 'Enseignant de cette matière' : 'Sélectionner une matière d\'abord'"
                                                    />
                                                </div>

                                                <div class="col-sm-4">
                                                    <label class="form-label">{{ t('fields.heure_debut') || 'Heure début' }}</label>
                                                    <input
                                                        v-model="cours.heure_debut"
                                                        type="time"
                                                        class="form-control"
                                                        @change="() => { cours.duree = calculateDuree(cours.heure_debut, cours.heure_fin); }"
                                                    />
                                                </div>

                                                <div class="col-sm-4">
                                                    <label class="form-label">{{ t('fields.heure_fin') || 'Heure fin' }}</label>
                                                    <input
                                                        v-model="cours.heure_fin"
                                                        type="time"
                                                        class="form-control"
                                                        @change="() => { cours.duree = calculateDuree(cours.heure_debut, cours.heure_fin); }"
                                                    />
                                                </div>

                                                <div class="col-sm-3">
                                                    <label class="form-label">{{ t('fields.duree') || 'Durée' }} (h)</label>
                                                    <input
                                                        :value="formatDurationHM(cours.duree)"
                                                        type="text"
                                                        class="form-control"
                                                        disabled
                                                    />
                                                </div>

                                                <div class="col-sm-1 d-flex align-items-end">
                                                    <button type="button" class="btn btn-danger btn-sm w-100" @click="removeCours(jour, index)">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-secondary btn-sm" @click="addCours(jour)">
                                            <i class="fa fa-plus"></i> {{ t('actions.add') || 'Ajouter un cours' }}
                                        </button>
                                    </div>
                                </template>

                                <!-- ==================== BOUTONS ==================== -->
                                <div class="col-12 mt-4">
                                    <div class="text-end">
                                        <Link :href="route('academique.emplois_du_temps.index')" class="btn btn-secondary me-2">
                                            {{ t('actions.cancel') || 'Annuler' }}
                                        </Link>
                                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                            {{ t('actions.save') || 'Sauvegarder la semaine' }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>

<style scoped>
.section-title {
    font-size: 1rem;
    font-weight: 600;
    border-bottom: 2px solid #ddd;
    padding-bottom: 0.5rem;
}
</style>
