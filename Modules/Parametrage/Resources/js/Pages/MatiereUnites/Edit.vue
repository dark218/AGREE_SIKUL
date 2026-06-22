<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MatiereUnitesForm from './MatiereUnitesForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const niveaux = ref([]);
const sections = ref([]);
const cycles = ref([]);
const ecoles = ref([]);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const form = useForm({
    code: page.props.item?.code || '',
    libelle: page.props.item?.libelle || '',
    ecole_id: page.props.item?.ecole_id || null,
    institution_id: page.props.item?.institution_id || null,
    niveau_id: page.props.item?.niveau_id || null,
    section_id: page.props.item?.section_id || null,
    cycle_id: page.props.item?.cycle_id || null,
    note_max: page.props.item?.note_max || 20,
    coefficient: page.props.item?.coefficient || 1,
    type_matiere: page.props.item?.type_matiere || null,
    volume_horaire: page.props.item?.volume_horaire || null,
    est_obligatoire: page.props.item?.est_obligatoire || false,
    etat: page.props.item?.etat || 'actif',
});
// Load dropdown data
onMounted(async () => {
    try {
        if (page.props.niveaux && page.props.niveaux.length > 0) {
            niveaux.value = page.props.niveaux;
            sections.value = page.props.sections || [];
            cycles.value = page.props.cycles || [];
            ecoles.value = page.props.ecoles || [];
            return;
        }
        // Fallback: Load from API
        const response = await fetch('/api/parametrage/form-data');
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        const data = await response.json();
        niveaux.value = data.niveaux || [];
        sections.value = data.sections || [];
        cycles.value = data.cycles || [];
        ecoles.value = data.ecoles || [];
    } catch (error) {
        console.error('Error loading form data:', error);
    }
});
const submitForm = () => {
    showUpdateLoader();
    form.put(route('parametrage.matiere_unites.update', page.props.item?.id), {
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
    <Head :title="t('actions.edit')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0"><i class="fa fa-pencil"></i> {{ t('actions.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <MatiereUnitesForm
                                    :form="form"
                                    :niveaux="niveaux"
                                    :sections="sections"
                                    :cycles="cycles"
                                    :ecoles="ecoles"
                                    :institutions="page.props.institutions"
                                    mode="edit"
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
