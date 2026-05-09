<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import CoursForm from './CoursForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const page = usePage();
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
onMounted(() => {
    console.log('✏️ Edit.vue mounted');
    console.log('✅ page.props.cours:', page.props.cours);
    console.log('📚 page.props.matieres:', page.props.matieres?.length);
    console.log('🏫 page.props.classes:', page.props.classes?.length);
    console.log('👨‍🏫 page.props.enseignants:', page.props.enseignants?.length);
});
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
const form = useForm({
    code: page.props.cours?.code || '',
    titre: page.props.cours?.titre || '',
    description: page.props.cours?.description || '',
    matiere_id: page.props.cours?.matiere_id || null,
    classe_id: page.props.cours?.classe_id || null,
    enseignant_id: page.props.cours?.enseignant_id || null,
    date_debut: page.props.cours?.date_debut || '',
    date_fin: page.props.cours?.date_fin || '',
    statut: page.props.cours?.statut || 'actif',
});
const submitForm = () => {
    showUpdateLoader();
    form.put(route('academique.cours.update', page.props.cours?.id), {
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
                                <h5 class="title mb-0">{{ t('modules.academique.cours.edit') }}</h5>
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
                                    mode="edit"
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
