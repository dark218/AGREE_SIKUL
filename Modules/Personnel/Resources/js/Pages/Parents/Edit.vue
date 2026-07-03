<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ParentForm from './ParentForm.vue';
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
    parent: {
        type: Object,
        required: true,
    },
    apprenants: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    institutions: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    apprenant_id: props.parent?.apprenant_id || '',
    apprenant_ids: props.parent?.apprenant_ids || [],
    lien_parente: (props.parent?.apprenant_pivots || []).map(p => p.lien_parente || null),
    classe_id: props.parent?.classe_id || '',
    ecole_id: props.parent?.ecole_id || '',
    institution_id: props.parent?.institution_id || '',
    campus_id: props.parent?.campus_id || '',
    pere_nom: props.parent?.pere_nom || '',
    pere_prenoms: props.parent?.pere_prenoms || '',
    pere_nom_complet: props.parent?.pere_nom_complet || '',
    pere_profession: props.parent?.pere_profession || '',
    pere_organisation_travail: props.parent?.pere_organisation_travail || '',
    pere_ville_travail: props.parent?.pere_ville_travail || '',
    pere_pays_travail: props.parent?.pere_pays_travail || '',
    pere_adresse_residence: props.parent?.pere_adresse_residence || '',
    pere_quartier: props.parent?.pere_quartier || '',
    pere_commune: props.parent?.pere_commune || '',
    pere_departement: props.parent?.pere_departement || '',
    pere_region: props.parent?.pere_region || '',
    pere_code_postal: props.parent?.pere_code_postal || '',
    pere_boite_postal: props.parent?.pere_boite_postal || '',
    pere_telephone_1: props.parent?.pere_telephone_1 || '',
    pere_telephone_2: props.parent?.pere_telephone_2 || '',
    pere_whatsapp_1: props.parent?.pere_whatsapp_1 || '',
    pere_whatsapp_2: props.parent?.pere_whatsapp_2 || '',
    pere_email_1: props.parent?.pere_email_1 || '',
    pere_email_2: props.parent?.pere_email_2 || '',
    mere_nom: props.parent?.mere_nom || '',
    mere_prenoms: props.parent?.mere_prenoms || '',
    mere_nom_complet: props.parent?.mere_nom_complet || '',
    mere_profession: props.parent?.mere_profession || '',
    mere_organisation_travail: props.parent?.mere_organisation_travail || '',
    mere_ville_travail: props.parent?.mere_ville_travail || '',
    mere_pays_travail: props.parent?.mere_pays_travail || '',
    mere_adresse_residence: props.parent?.mere_adresse_residence || '',
    mere_quartier: props.parent?.mere_quartier || '',
    mere_commune: props.parent?.mere_commune || '',
    mere_departement: props.parent?.mere_departement || '',
    mere_region: props.parent?.mere_region || '',
    mere_code_postal: props.parent?.mere_code_postal || '',
    mere_boite_postal: props.parent?.mere_boite_postal || '',
    mere_telephone_1: props.parent?.mere_telephone_1 || '',
    mere_telephone_2: props.parent?.mere_telephone_2 || '',
    mere_whatsapp_1: props.parent?.mere_whatsapp_1 || '',
    mere_whatsapp_2: props.parent?.mere_whatsapp_2 || '',
    mere_email_1: props.parent?.mere_email_1 || '',
    mere_email_2: props.parent?.mere_email_2 || '',
    tuteur1_nom: props.parent?.tuteur1_nom || '',
    tuteur1_prenoms: props.parent?.tuteur1_prenoms || '',
    tuteur1_nom_complet: props.parent?.tuteur1_nom_complet || '',
    tuteur1_profession: props.parent?.tuteur1_profession || '',
    tuteur1_organisation_travail: props.parent?.tuteur1_organisation_travail || '',
    tuteur1_ville_travail: props.parent?.tuteur1_ville_travail || '',
    tuteur1_pays_travail: props.parent?.tuteur1_pays_travail || '',
    tuteur1_adresse_residence: props.parent?.tuteur1_adresse_residence || '',
    tuteur1_quartier: props.parent?.tuteur1_quartier || '',
    tuteur1_commune: props.parent?.tuteur1_commune || '',
    tuteur1_arrondissement: props.parent?.tuteur1_arrondissement || '',
    tuteur1_ville: props.parent?.tuteur1_ville || '',
    tuteur1_departement: props.parent?.tuteur1_departement || '',
    tuteur1_region: props.parent?.tuteur1_region || '',
    tuteur1_pays: props.parent?.tuteur1_pays || '',
    tuteur1_code_postal: props.parent?.tuteur1_code_postal || '',
    tuteur1_boite_postal: props.parent?.tuteur1_boite_postal || '',
    tuteur1_telephone_1: props.parent?.tuteur1_telephone_1 || '',
    tuteur1_telephone_2: props.parent?.tuteur1_telephone_2 || '',
    tuteur1_email: props.parent?.tuteur1_email || '',
    tuteur1_whatsapp_1: props.parent?.tuteur1_whatsapp_1 || '',
    tuteur1_whatsapp_2: props.parent?.tuteur1_whatsapp_2 || '',
    tuteur2_nom: props.parent?.tuteur2_nom || '',
    tuteur2_prenoms: props.parent?.tuteur2_prenoms || '',
    tuteur2_nom_complet: props.parent?.tuteur2_nom_complet || '',
    tuteur2_profession: props.parent?.tuteur2_profession || '',
    tuteur2_organisation_travail: props.parent?.tuteur2_organisation_travail || '',
    tuteur2_ville_travail: props.parent?.tuteur2_ville_travail || '',
    tuteur2_pays_travail: props.parent?.tuteur2_pays_travail || '',
    tuteur2_adresse_residence: props.parent?.tuteur2_adresse_residence || '',
    tuteur2_quartier: props.parent?.tuteur2_quartier || '',
    tuteur2_commune: props.parent?.tuteur2_commune || '',
    tuteur2_arrondissement: props.parent?.tuteur2_arrondissement || '',
    tuteur2_ville: props.parent?.tuteur2_ville || '',
    tuteur2_departement: props.parent?.tuteur2_departement || '',
    tuteur2_region: props.parent?.tuteur2_region || '',
    tuteur2_pays: props.parent?.tuteur2_pays || '',
    tuteur2_code_postal: props.parent?.tuteur2_code_postal || '',
    tuteur2_boite_postal: props.parent?.tuteur2_boite_postal || '',
    tuteur2_telephone_1: props.parent?.tuteur2_telephone_1 || '',
    tuteur2_telephone_2: props.parent?.tuteur2_telephone_2 || '',
    tuteur2_email: props.parent?.tuteur2_email || '',
    tuteur2_whatsapp_1: props.parent?.tuteur2_whatsapp_1 || '',
    tuteur2_whatsapp_2: props.parent?.tuteur2_whatsapp_2 || '',
    etat: props.parent?.etat || 'actif',
});

onMounted(() => {
    if (props.parent) {
        Object.keys(props.parent).forEach(key => {
            if (form.hasOwnProperty(key)) {
                form[key] = props.parent[key] || '';
            }
        });
    }
});

watch(() => props.parent, (newParent) => {
    if (newParent) {
        Object.keys(newParent).forEach(key => {
            if (form.hasOwnProperty(key)) {
                form[key] = newParent[key] || '';
            }
        });
    }
}, { deep: true });

const submitForm = () => {
    showUpdateLoader();
    form.put(route('parents.update', props.parent.id), {
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
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.personnel.parents.edit') || 'Modifier un parent' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <ParentForm
                                    :form="form"
                                    :apprenants="apprenants"
                                    :classes="classes"
                                    :ecoles="ecoles"
                                    :institutions="institutions"
                                    :campuses="campuses"
                                    mode="edit"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parents.index')" class="btn btn-danger">
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
