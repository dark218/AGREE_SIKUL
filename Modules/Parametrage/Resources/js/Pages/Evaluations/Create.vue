<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import EvaluationsForm from './EvaluationsForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({ classes: Array, matieres: Array });
const form = useForm({ code: '', titre: '', type: 'controle', classe_id: null, matiere_id: null, date: '', coefficient: 1, sur: 20, statut: 'actif' });
const submit = () => { showStoreLoader(); setTimeout(() => { form.post(route('parametrage.evaluations.store'), { onSuccess: () => hideLoader(), onError: () => hideLoader(), onFinish: () => hideLoader() }); }, 500); };
</script>
<template>
    <Head :title="t('entities.create_evaluations')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper"><h4 class="title">{{ t('entities.create_evaluations') }}</h4></div>
        <AlertMessage />
        <form @submit.prevent="submit">
            <EvaluationsForm :form="form" :classes="props.classes" :matieres="props.matieres" :error="form.errors" />
            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('actions.save') }}</button>
                <Link :href="route('parametrage.evaluations.index')" class="btn btn-secondary" style="margin-left: 0.5rem;">{{ t('actions.cancel') }}</Link>
            </div>
        </form>
        <FullPageLoader :show="isLoading" />
    </div>
</template>
