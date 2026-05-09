<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import CoursForm from './CoursForm.vue';
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
    matieres: Array,
    classes: Array,
    enseignants: Array,
});
onMounted(() => {
    console.log('🔍 Create.vue mounted');
    console.log('✅ Props reçus:', props);
    console.log('📚 Matieres:', props.matieres?.length);
    console.log('🏫 Classes:', props.classes?.length);
    console.log('👨‍🏫 Enseignants:', props.enseignants?.length);
    console.log('🌍 Traduction cours.create:', t('modules.academique.cours.create'));
});
const form = useForm({
    code: '',
    titre: '',
    description: '',
    matiere_id: null,
    classe_id: null,
    enseignant_id: null,
    date_debut: '',
    date_fin: '',
    statut: 'actif',
});
const submitForm = () => {
    console.log('🚀 Soumission du formulaire...');
    console.log('📋 Données du formulaire:', {
        code: form.code,
        titre: form.titre,
        description: form.description,
        matiere_id: form.matiere_id,
        classe_id: form.classe_id,
        enseignant_id: form.enseignant_id,
        date_debut: form.date_debut,
        date_fin: form.date_fin,
        statut: form.statut,
    });
    showStoreLoader();
    form.post(route('academique.cours.store'), {
        onSuccess: () => {
            console.log('✅ Soumission réussie!');
            // Attendre que la redirection et la nouvelle page se chargent complètement
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: (errors) => {
            console.log('❌ Erreurs de validation:', errors);
            console.log('📊 État du formulaire après erreur:', form.errors);
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
                                <h5 class="title mb-0">{{ t('modules.academique.cours.create') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <CoursForm
                                    :form="form"
                                    mode="create"
                                    :matieres="matieres"
                                    :classes="classes"
                                    :enseignants="enseignants"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.cours.index')" class="btn btn-danger">
                                                {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                {{ t('actions.validate') }}
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
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
