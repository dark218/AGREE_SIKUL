<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import CoursForm from './CoursForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    matieres: Array,
    enseignants: Array,
    classes: Array,
});
const form = useForm({
    code: '',
    titre: '',
    description: '',
    matiere_id: null,
    enseignant_id: null,
    classe_id: null,
    date_debut: '',
    date_fin: '',
    statut: 'actif',
});
const submit = () => {
    showStoreLoader();
    setTimeout(() => {
        form.post(route('parametrage.cours.store'), {
            onSuccess: () => hideLoader(),
            onError: () => hideLoader(),
            onFinish: () => hideLoader(),
        });
    }, 500);
};
</script>
<template>
    <Head :title="t('entities.create_cours') || 'Créer un cours'" />
    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('entities.create_cours') || 'Créer un cours' }}</h4>
            </div>
            <AlertMessage />
            <form @submit.prevent="submit">
                <CoursForm
                    :form="form"
                    :matieres="props.matieres"
                    :enseignants="props.enseignants"
                    :classes="props.classes"
                    :error="form.errors"
                />
                <div class="form-group" style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        {{ form.processing ? t('messages.saving') : t('actions.save') }}
                    </button>
                    <Link :href="route('parametrage.cours.index')" class="btn btn-secondary" style="margin-left: 0.5rem;">
                        {{ t('actions.cancel') || 'Annuler' }}
                    </Link>
                </div>
            </form>
        </div>
        <FullPageLoader :show="isLoading" />
    </div>
</template>
