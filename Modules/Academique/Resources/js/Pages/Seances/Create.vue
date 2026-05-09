<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import SeanceForm from './SeanceForm.vue';
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
    cours: {
        type: Array,
        default: () => [],
    },
    salles: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    cours_id: '',
    salle_id: '',
    titre: '',
    sujet: '',
    date: '',
    heure_debut: '',
    heure_fin: '',
    duree: '',
    statut: '',
});
const submitForm = () => {
    console.log('🖱️ submitForm clicked!');
    console.log('📋 Form data:', form.data());
    console.log('⚙️ Route academique.seances.store:', route('academique.seances.store'));

    showStoreLoader();
    form.post(route('academique.seances.store'), {
        onSuccess: () => {
            console.log('✅ Form submitted successfully!');
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: (errors) => {
            console.error('❌ Form submission error!');
            console.error('Erreurs du formulaire:', form.errors);
            console.error('Erreurs complètes:', errors);
            hideLoader();
            if (form.errors && Object.keys(form.errors).length > 0) {
                const errorMessages = Object.values(form.errors).map(e => Array.isArray(e) ? e.join(', ') : e).join('\n');
                alert('Erreurs de validation:\n\n' + errorMessages);
            }
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
                                <h5 class="title mb-0">{{ title }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <SeanceForm
                                    :form="form"
                                    mode="create"
                                    :cours="cours"
                                    :salles="salles"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.seances.index')" class="btn btn-danger">
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
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
