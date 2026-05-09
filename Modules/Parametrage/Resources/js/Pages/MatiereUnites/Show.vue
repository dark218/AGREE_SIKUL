<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import MatiereUnitesForm from './MatiereUnitesForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import { reactive } from 'vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant } = useLoader();
const isCollapsed = ref(false);
const niveaux = ref([]);
const sections = ref([]);
const cycles = ref([]);
const ecoles = ref([]);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const form = reactive({
    code: page.props.matiereUnite?.code || '',
    libelle: page.props.matiereUnite?.libelle || '',
    ecole_id: page.props.matiereUnite?.ecole_id || null,
    niveau_id: page.props.matiereUnite?.niveau_id || null,
    section_id: page.props.matiereUnite?.section_id || null,
    cycle_id: page.props.matiereUnite?.cycle_id || null,
    note_max: page.props.matiereUnite?.note_max || 20,
    coefficient: page.props.matiereUnite?.coefficient || 1,
    type_matiere: page.props.matiereUnite?.type_matiere || null,
    volume_horaire: page.props.matiereUnite?.volume_horaire || null,
    est_obligatoire: page.props.matiereUnite?.est_obligatoire || false,
    etat: page.props.matiereUnite?.etat || 'actif',
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
</script>
<template>
    <Head :title="t('actions.view')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">?</span>
                                <h5 class="title mb-0"><i class="fa fa-eye"></i> {{ t('actions.view') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <MatiereUnitesForm
                                :form="form"
                                :niveaux="niveaux"
                                :sections="sections"
                                :cycles="cycles"
                                :ecoles="ecoles"
                                mode="show"
                            />
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.matiere_unites.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <Link v-if="page.props.matiereUnite?.id" :href="route('parametrage.matiere_unites.edit', page.props.matiereUnite?.id)" class="btn btn-primary ms-2">
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </Link>
                                        <button v-else class="btn btn-primary ms-2" disabled>
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
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
