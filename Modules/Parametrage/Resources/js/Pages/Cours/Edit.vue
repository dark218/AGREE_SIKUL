<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import CoursForm from './CoursForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    cours: Object,
    matieres: Array,
    enseignants: Array,
    classes: Array,
});
const form = useForm({
    code: props.cours?.code || '',
    titre: props.cours?.titre || '',
    description: props.cours?.description || '',
    matiere_id: props.cours?.matiere_id || null,
    enseignant_id: props.cours?.enseignant_id || null,
    classe_id: props.cours?.classe_id || null,
    date_debut: props.cours?.date_debut || '',
    date_fin: props.cours?.date_fin || '',
    statut: props.cours?.statut || 'actif',
});
const submit = () => {
    showStoreLoader();
    setTimeout(() => {
        form.put(route('parametrage.cours.update', page.props.cours?.id), {
            onSuccess: () => hideLoader(),
            onError: () => hideLoader(),
            onFinish: () => hideLoader(),
        });
    }, 500);
};
</script>
<template>
    <Head :title="t('entities.edit_cours') || 'Éditer le cours'" />
    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('entities.edit_cours') || 'Éditer le cours' }}</h4>
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
