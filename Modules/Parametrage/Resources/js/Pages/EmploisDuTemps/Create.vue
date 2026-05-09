<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import EmploisDuTempsForm from './EmploisDuTempsForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({ classes: Array, matieres: Array, enseignants: Array });
const form = useForm({ classe_id: null, matiere_id: null, enseignant_id: null, jour_semaine: '', heure_debut: '', heure_fin: '', salle: '', statut: 'actif' });
const submit = () => { showStoreLoader(); setTimeout(() => { form.post(route('parametrage.emplois-du-temps.store'), { onSuccess: () => hideLoader(), onError: () => hideLoader(), onFinish: () => hideLoader() }); }, 500); };
</script>
<template>
    <Head :title="t('entities.create_emplois_du_temps')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper"><h4 class="title">{{ t('entities.create_emplois_du_temps') }}</h4></div>
        <AlertMessage />
        <form @submit.prevent="submit">
            <EmploisDuTempsForm :form="form" :classes="props.classes" :matieres="props.matieres" :enseignants="props.enseignants" :error="form.errors" />
            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('actions.save') }}</button>
                <Link :href="route('parametrage.emplois-du-temps.index')" class="btn btn-secondary" style="margin-left: 0.5rem;">{{ t('actions.cancel') }}</Link>
            </div>
        </form>
        <FullPageLoader :show="isLoading" />
    </div>
</template>
