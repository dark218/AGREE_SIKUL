<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import BulletinForm from './BulletinForm.vue';
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
    apprenants: Array,
    classes: Array,
    anneesScolaires: Array,
    periodes: Array,
    decisionConseilOptions: Array,
});
const form = useForm({
    apprenant_id: null,
    classe_id: null,
    annee_scolaire_id: null,
    rang: '',
    periode: '',
    decision_conseil: '',
});
const submitForm = () => {
    // Validar antes de enviar
    if (!form.apprenant_id) {
        alert(t('common.apprenant') + ' est requis');
        return;
    }
    if (!form.classe_id) {
        alert(t('common.classe') + ' est requis');
        return;
    }
    if (!form.annee_scolaire_id) {
        alert(t('common.annee_scolaire') + ' est requis');
        return;
    }
    if (!form.moyenne_generale) {
        alert(t('fields.moyenne_generale') + ' est requis');
        return;
    }
    showStoreLoader();
    form.post(route('academique.bulletins.store'), {
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: (errors) => {
            console.error('Erreurs du formulaire:', form.errors);
            hideLoader();
            // Afficher les erreurs
            if (form.errors && Object.keys(form.errors).length > 0) {
                const errorMessages = Object.values(form.errors).join('\n');
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
                                <BulletinForm
                                    :form="form"
                                    mode="create"
                                    :apprenants="apprenants"
                                    :classes="classes"
                                    :annees-scolaires="anneesScolaires"
                                    :periodes="periodes"
                                    :decision-conseil-options="decisionConseilOptions"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.bulletins.index')" class="btn btn-danger">
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
