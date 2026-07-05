<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import BibliothequeStructureForm from './BibliothequeStructureForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();

const props = defineProps({
    structure: { type: Object, required: true },
    campuses: { type: Array, default: () => [] },
});

const form = useForm({
    code: props.structure?.code || '',
    libelle: props.structure?.libelle || '',
    localisation: props.structure?.localisation || '',
    campus_id: props.structure?.campus_id || null,
    responsable: props.structure?.responsable || '',
    statut_disponibilite: props.structure?.statut_disponibilite || 'disponible',
    etat: props.structure?.etat || 'actif',
});

const submit = () => {
    showUpdateLoader();
    form.put(route('academique.bibliotheque-structures.update', props.structure.id), {
        onFinish: () => hideLoader(),
    });
};
</script>

<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex align-items-center">
                            <span class="dash-payment-badge"><i class="fa fa-edit"></i></span>
                            <h5 class="title mb-0">{{ t('actions.edit') || 'Modifier' }} — {{ structure?.libelle }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <AlertMessage />
                            <form @submit.prevent="submit">
                                <BibliothequeStructureForm :form="form" :campuses="campuses" mode="edit" />
                                <div class="text-end mt-4">
                                    <Link :href="route('academique.bibliotheque-structures.index')" class="btn btn-danger">
                                        <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                    </Link>
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                        <i class="fa fa-save"></i> {{ t('actions.validate') || 'Enregistrer' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
