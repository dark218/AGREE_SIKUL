<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import OuvrageForm from './OuvrageForm.vue';
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
    ouvrage: {
        type: Object,
        required: true,
    },
    bibliotheques: {
        type: Array,
        default: () => [],
    },
});

// Convert year to date (YYYY-01-01)
const yearToDate = (year) => {
    return year ? `${year}-01-01` : '';
};

const form = useForm({
    bibliotheque_id: props.ouvrage?.bibliotheque_id || null,
    titre: props.ouvrage?.titre || '',
    auteur: props.ouvrage?.auteur || '',
    isbn: props.ouvrage?.isbn || '',
    editeur: props.ouvrage?.editeur || '',
    date_publication: yearToDate(props.ouvrage?.annee_publication) || '',
    categorie: props.ouvrage?.categorie || '',
    description: props.ouvrage?.description || '',
    nombre_exemplaires: props.ouvrage?.nombre_exemplaires || 1,
    statut: props.ouvrage?.statut || 'actif',
});

const submitForm = () => {
    showUpdateLoader();

    // Convert date to year (annee_publication)
    const formData = {
        ...form.data(),
        annee_publication: form.date_publication ? new Date(form.date_publication).getFullYear() : null,
    };
    delete formData.date_publication;

    router.put(route('ouvrages.update', props.ouvrage?.id), formData, {
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
                                <h5 class="title mb-0">{{ t('modules.ressources_logistique.ouvrages.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <OuvrageForm :form="form" :bibliotheques="props.bibliotheques" mode="edit" />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('ouvrages.index')" class="btn btn-danger">
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
