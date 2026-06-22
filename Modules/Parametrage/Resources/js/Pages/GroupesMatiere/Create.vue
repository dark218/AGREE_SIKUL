<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import GroupesMatiereForm from './GroupesMatiereForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    niveaux: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    pays: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    institutions: { type: Array, default: () => [] },
});
const form = useForm({
    code: '',
    libelle: '',
    ecole_id: null,
    institution_id: null,
    niveau_id: null,
    section_id: null,
    cycle_id: null,
    matiere1_id: null,
    matiere2_id: null,
    matiere3_id: null,
    matiere4_id: null,
    matiere5_id: null,
    matiere6_id: null,
    matiere7_id: null,
    matiere8_id: null,
    matiere9_id: null,
    matiere10_id: null,
    annee_scolaire_id: null,
    pays_id: null,
    etat: 'actif',
});
const submitForm = () => {
    console.log('📝 Form submitForm called');
    console.log('Form data:', form.data());
    console.log('Form errors:', form.errors);
    showStoreLoader();
    form.post(route('parametrage.groupes_matiere.store'), {
        onError: (errors) => {
            console.error('❌ Form submission error:', errors);
        },
        onSuccess: () => {
            console.log('✅ Form submitted successfully');
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
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('actions.create') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <GroupesMatiereForm
                                    :form="form"
                                    :niveaux="niveaux"
                                    :sections="sections"
                                    :cycles="cycles"
                                    :matieres="matieres"
                                    :ecoles="ecoles"
                                    :institutions="institutions"
                                    :annees-scolaires="anneesScolaires"
                                    :pays="pays"
                                    mode="create"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.groupes_matiere.index')" class="btn btn-danger">
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
