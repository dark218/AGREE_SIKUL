<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import SeancesForm from './SeancesForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({ seance: Object, classes: Array, matieres: Array, enseignants: Array });
const form = useForm({
    code: props.seance?.code || '', titre: props.seance?.titre || '', date: props.seance?.date || '', heure_debut: props.seance?.heure_debut || '', heure_fin: props.seance?.heure_fin || '', classe_id: props.seance?.classe_id || null, matiere_id: props.seance?.matiere_id || null, enseignant_id: props.seance?.enseignant_id || null, salle: props.seance?.salle || '', statut: props.seance?.statut || 'actif',
});
const submit = () => { showStoreLoader(); setTimeout(() => { form.put(route('parametrage.seances.update', page.props.seance?.id), { onSuccess: () => hideLoader(), onError: () => hideLoader(), onFinish: () => hideLoader() }); }, 500); };
</script>
<template>
    <Head :title="t('entities.edit_seances') || 'Éditer la séance'" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper"><h4 class="title">{{ t('entities.edit_seances') }}</h4></div>
        <AlertMessage />
        <form @submit.prevent="submit">
            <SeancesForm :form="form" :classes="props.classes" :matieres="props.matieres" :enseignants="props.enseignants" :error="form.errors" />
            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('actions.save') }}</button>
                <Link :href="route('parametrage.seances.index')" class="btn btn-secondary" style="margin-left: 0.5rem;">{{ t('actions.cancel') }}</Link>
            </div>
        </form>
        <FullPageLoader :show="isLoading" />
    </div>
</template>
