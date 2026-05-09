<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import SeancesForm from './SeancesForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({ classes: Array, matieres: Array, enseignants: Array });
const form = useForm({
    code: '', titre: '', date: '', heure_debut: '', heure_fin: '', classe_id: null, matiere_id: null, enseignant_id: null, salle: '', statut: 'actif',
});
const submit = () => { showStoreLoader(); setTimeout(() => { form.post(route('parametrage.seances.store'), { onSuccess: () => hideLoader(), onError: () => hideLoader(), onFinish: () => hideLoader() }); }, 500); };
</script>
<template>
    <Head :title="t('entities.create_seances') || 'Créer une séance'" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper"><h4 class="title">{{ t('entities.create_seances') }}</h4></div>
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
