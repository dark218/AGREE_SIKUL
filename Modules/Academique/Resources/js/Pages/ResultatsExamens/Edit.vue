<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ResultatExamenForm from './ResultatExamenForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

// Helper to format dates for date input (YYYY-MM-DD)
const formatDate = (dateString) => {
    if (!dateString) return '';
    // Handle ISO format (2026-03-19T00:00:00.000000Z) and regular format (2026-03-19 00:00:00)
    if (dateString.includes('T')) {
        // ISO format: extract before the T
        return dateString.split('T')[0];
    } else {
        // Regular format: extract before the space
        return dateString.split(' ')[0];
    }
};

const props = defineProps({
    item: Object,
    title: String,
    matieres: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    apprenants: {
        type: Array,
        default: () => [],
    },
});

// DEBUG: Log received data
console.log('[ResultatExamen Edit] Item reçu du serveur:', props.item);
console.log('[ResultatExamen Edit] date_debut brut:', props.item?.date_debut);
console.log('[ResultatExamen Edit] date_fin brut:', props.item?.date_fin);

const dateDebutFormatee = formatDate(props.item.date_debut);
const dateFinFormatee = formatDate(props.item.date_fin);
console.log('[ResultatExamen Edit] date_debut formatée:', dateDebutFormatee);
console.log('[ResultatExamen Edit] date_fin formatée:', dateFinFormatee);

const form = useForm({
    matiere_id: props.item.matiere_id || null,
    classe_id: props.item.classe_id || null,
    apprenant_id: props.item.apprenant_id || null,
    date_debut: dateDebutFormatee || '',
    date_fin: dateFinFormatee || '',
    note_maximale: props.item.note_maximale || null,
    nombre_questions: props.item.nombre_questions || null,
    duree: props.item.duree || null,
    points: props.item.points || null,
    temps_effectue: props.item.temps_effectue || null,
    reponses_correctes: props.item.reponses_correctes || null,
    reponses_fausses: props.item.reponses_fausses || null,
    non_repondues: props.item.non_repondues || null,
    reponses_douteuses: props.item.reponses_douteuses || null,
    etat: props.item.etat || 'actif',
});

const submitForm = () => {
    showUpdateLoader();
    form.put(route('academique.resultats-examens.update', props.item.id), {
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: () => {
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
                                <h5 class="title mb-0">{{ t('actions.edit') || 'Modifier' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <ResultatExamenForm
                                    :form="form"
                                    :matieres="matieres"
                                    :classes="classes"
                                    :apprenants="apprenants"
                                    mode="edit"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.resultats-examens.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i> {{ t('actions.validate') || 'Valider' }}
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
