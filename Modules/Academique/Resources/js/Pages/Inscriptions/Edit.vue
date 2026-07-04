<script setup>
import { ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import InscriptionForm from './InscriptionForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    inscription: Object,
    apprenants: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    institutions: {
        type: Array,
        default: () => [],
    },
    typesInscriptions: { type: Array, default: () => [] },
});
// Helper function to format date for input type="date"
const formatDateForInput = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toISOString().split('T')[0]; // Returns yyyy-MM-dd
};

// DEBUG: Log inscription data
console.log('🔍 InscriptionEdit - Props received:', {
    inscription_id: props.inscription?.id,
    apprenant_id: props.inscription?.apprenant_id,
    date_inscription_raw: props.inscription?.date_inscription,
    date_inscription_type: typeof props.inscription?.date_inscription,
    date_inscription_formatted: formatDateForInput(props.inscription?.date_inscription),
    full_inscription: props.inscription, // Full object for inspection
});

const form = useForm({
    apprenant_id: props.inscription?.apprenant_id || null,
    classe_id: props.inscription?.classe_id || null,
    annee_scolaire_id: props.inscription?.annee_scolaire_id || null,
    ecole_id: props.inscription?.ecole_id || null,
    campus_id: props.inscription?.campus_id || null,
    institution_id: props.inscription?.institution_id || null,
    numero_inscription: props.inscription?.numero_inscription || '',
    date_inscription: formatDateForInput(props.inscription?.date_inscription) || '',
    type_inscription: props.inscription?.type_inscription || 'nouveau',
    statut: props.inscription?.statut || 'en_attente',
    premiere_inscription: props.inscription?.premiere_inscription || false,
    frais_dossier: props.inscription?.frais_dossier || 0,
    frais_inscription: props.inscription?.frais_inscription || 0,
    frais_scolarite: props.inscription?.frais_scolarite || 0,
    frais_dossier_paye: props.inscription?.frais_dossier_paye || 0,
    frais_inscription_paye: props.inscription?.frais_inscription_paye || 0,
    frais_scolarite_paye: props.inscription?.frais_scolarite_paye || 0,
    dossier_complet: props.inscription?.dossier_complet || false,
    fiche_inscription: props.inscription?.fiche_inscription || null,
    carnet_vaccination: props.inscription?.carnet_vaccination || null,
    photos_4x4: props.inscription?.photos_4x4 || null,
    copie_acte_naissance: props.inscription?.copie_acte_naissance || null,
    piece1: props.inscription?.piece1 || null,
    piece2: props.inscription?.piece2 || null,
    piece3: props.inscription?.piece3 || null,
    piece4: props.inscription?.piece4 || null,
});

// Ensure apprenant_id is always synced and sent
watch(() => props.inscription?.apprenant_id, (newVal) => {
    console.log('👁️ WATCH apprenant_id - newVal:', newVal, 'type:', typeof newVal);
    if (newVal) {
        form.apprenant_id = newVal;
        console.log('✅ WATCH: apprenant_id synced to:', form.apprenant_id);
    }
}, { immediate: true });

// Watch form changes
watch(() => form.apprenant_id, (newVal) => {
    console.log('👀 form.apprenant_id changed to:', newVal, 'type:', typeof newVal);
}, { deep: true });

const submitForm = () => {
    showUpdateLoader();

    const url = route('academique.inscriptions.update', props.inscription.id);

    console.log('🚀 Submitting with apprenant_id:', props.inscription?.apprenant_id);

    form
        .transform((data) => ({
            ...data,
            apprenant_id: props.inscription?.apprenant_id, // FORCE apprenant_id into request
        }))
        .put(url, {
            multipart: false,
            onError: (errors) => {
                console.error('❌ Server error:', errors);
            },
            onSuccess: () => {
                console.log('✅ Update successful!');
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
                                <InscriptionForm
                                    :form="form"
                                    :apprenants="apprenants"
                                    :classes="classes"
                                    :anneesScolaires="anneesScolaires"
                                    :ecoles="ecoles"
                                    :campuses="campuses"
                                    :institutions="institutions"
                                    :typesInscriptions="typesInscriptions"
                                    mode="edit"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.inscriptions.index')" class="btn btn-danger">
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
