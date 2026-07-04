<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import AccompagnateurForm from './AccompagnateurForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();

const props = defineProps({
    accompagnateur: {
        type: Object,
        required: true,
    },
    ecoles: Array,
    institutions: Array,
    campuses: Array,
    apprenants: { type: Array, default: () => [] },
    civilites: { type: Array, default: () => [] },
});

const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const form = useForm({
    // School Information
    ecole_id: props.accompagnateur?.ecole_id || '',
    institution_id: props.accompagnateur?.institution_id || '',
    campus_id: props.accompagnateur?.campus_id || '',
    // Apprenants rattachés (multi)
    apprenant_ids: props.accompagnateur?.apprenant_ids || [],
    // Accompagnant 1
    accompagnant1_civilite: props.accompagnateur?.accompagnant1_civilite || '',
    accompagnant1_nom: props.accompagnateur?.accompagnant1_nom || '',
    accompagnant1_prenoms: props.accompagnateur?.accompagnant1_prenoms || '',
    accompagnant1_nom_complet: props.accompagnateur?.accompagnant1_nom_complet || '',
    accompagnant1_lien: props.accompagnateur?.accompagnant1_lien || '',
    accompagnant1_photo_id: props.accompagnateur?.accompagnant1_photo_id || '',
    accompagnant1_photo: null,
    accompagnant1_photo_url: props.accompagnateur?.accompagnant1_photo_url || null,
    // Accompagnant 2
    accompagnant2_civilite: props.accompagnateur?.accompagnant2_civilite || '',
    accompagnant2_nom: props.accompagnateur?.accompagnant2_nom || '',
    accompagnant2_prenoms: props.accompagnateur?.accompagnant2_prenoms || '',
    accompagnant2_nom_complet: props.accompagnateur?.accompagnant2_nom_complet || '',
    accompagnant2_lien: props.accompagnateur?.accompagnant2_lien || '',
    accompagnant2_photo_id: props.accompagnateur?.accompagnant2_photo_id || '',
    accompagnant2_photo: null,
    accompagnant2_photo_url: props.accompagnateur?.accompagnant2_photo_url || null,
    // Accompagnant 3
    accompagnant3_civilite: props.accompagnateur?.accompagnant3_civilite || '',
    accompagnant3_nom: props.accompagnateur?.accompagnant3_nom || '',
    accompagnant3_prenoms: props.accompagnateur?.accompagnant3_prenoms || '',
    accompagnant3_nom_complet: props.accompagnateur?.accompagnant3_nom_complet || '',
    accompagnant3_lien: props.accompagnateur?.accompagnant3_lien || '',
    accompagnant3_photo_id: props.accompagnateur?.accompagnant3_photo_id || '',
    accompagnant3_photo: null,
    accompagnant3_photo_url: props.accompagnateur?.accompagnant3_photo_url || null,
    // Audit
    etat: props.accompagnateur?.etat || 'actif',
});

// Watchers pour mettre à jour le formulaire quand les props changent
onMounted(() => {
    if (props.accompagnateur) {
        updateFormData();
    }
});

watch(() => props.accompagnateur, (newVal) => {
    if (newVal) {
        updateFormData();
    }
}, { deep: true });

const updateFormData = () => {
    const fields = [
        'ecole_id', 'institution_id', 'campus_id',
        'accompagnant1_civilite', 'accompagnant1_nom', 'accompagnant1_prenoms', 'accompagnant1_nom_complet', 'accompagnant1_lien', 'accompagnant1_photo_id',
        'accompagnant2_civilite', 'accompagnant2_nom', 'accompagnant2_prenoms', 'accompagnant2_nom_complet', 'accompagnant2_lien', 'accompagnant2_photo_id',
        'accompagnant3_civilite', 'accompagnant3_nom', 'accompagnant3_prenoms', 'accompagnant3_nom_complet', 'accompagnant3_lien', 'accompagnant3_photo_id',
        'etat'
    ];
    fields.forEach(field => {
        form[field] = props.accompagnateur[field] || '';
    });
};

const submitForm = () => {
    showUpdateLoader();
    form.post(route('accompagnateurs.update', props.accompagnateur.id), {
        _method: 'put',
        forceFormData: true,
        onError: (errors) => {
            console.error('Form validation errors:', errors);
        },
        onSuccess: () => {
        },
        onFinish: () => {
            hideLoader();
        }
    });
};
</script>

<template>
    <Head :title="t('modules.personnel.accompagnateurs.edit') || 'Modifier l\'accompagnateur'" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.personnel.accompagnateurs.edit') || 'Modifier l\'accompagnateur' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <AccompagnateurForm :form="form" :ecoles="ecoles" :institutions="institutions" :campuses="campuses" :apprenants="apprenants" :civilites="civilites" mode="edit" />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('accompagnateurs.index')" class="btn btn-danger">
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
