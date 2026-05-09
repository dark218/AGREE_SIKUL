<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MatiereUnitesForm from './MatiereUnitesForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
defineProps({
    niveaux: {
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
});
const niveaux = ref([]);
const sections = ref([]);
const cycles = ref([]);
const ecoles = ref([]);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const form = useForm({
    code: '',
    libelle: '',
    ecole_id: null,
    niveau_id: null,
    section_id: null,
    cycle_id: null,
    note_max: 20,
    coefficient: 1,
    type_matiere: null,
    volume_horaire: null,
    est_obligatoire: false,
    etat: 'actif',
    });
// Load dropdown data with API fallback
const loadDropdownData = async () => {
    try {
        // Try to load from props first
        if (page.props.niveaux && page.props.niveaux.length > 0) {
            niveaux.value = page.props.niveaux;
            sections.value = page.props.sections || [];
            cycles.value = page.props.cycles || [];
            ecoles.value = page.props.ecoles || [];
            return;
        }
        // Fallback to API if props are empty
        const response = await fetch('/api/parametrage/form-data');
        if (!response.ok) throw new Error('API request failed');
        const data = await response.json();
        niveaux.value = data.niveaux || [];
        sections.value = data.sections || [];
        cycles.value = data.cycles || [];
        ecoles.value = data.ecoles || [];
    } catch (error) {
        console.error('Error loading form data:', error);
    }
};
// Load on component mount
onMounted(() => {
    loadDropdownData();
});
const submitForm = () => {
    if (!form.ecole_id) {
        alert('Erreur: Veuillez sélectionner une école');
        return;
    }
    showStoreLoader();
    form.post(route('parametrage.matiere_unites.store'), {
        onError: (errors) => {
            console.error('Erreur de validation:', errors);
            hideLoader();
        },
        onSuccess: () => {
            hideLoader();
        },
        onFinish: () => {
            hideLoader();
        }
    });
};
</script>
<template>
    <Head :title="t('actions.create')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">+</span>
                                <h5 class="title mb-0">{{ t('actions.create') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <!-- Validation Errors Summary -->
                            <div v-if="form.errors && Object.keys(form.errors).length > 0" class="alert alert-danger mb-3">
                                <strong>{{ t('validation.error_title') || 'Erreurs de validation' }}</strong>
                                <ul class="mb-0 mt-2">
                                    <li v-for="(error, field) in form.errors" :key="field">
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                            <form @submit.prevent="submitForm">
                                <MatiereUnitesForm
                                    :form="form"
                                    :niveaux="niveaux"
                                    :sections="sections"
                                    :cycles="cycles"
                                    :ecoles="ecoles"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.matiere_unites.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
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
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
