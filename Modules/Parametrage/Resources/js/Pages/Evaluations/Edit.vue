<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import EvaluationsForm from './EvaluationsForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({ evaluation: Object, classes: Array, matieres: Array });
const form = useForm({ code: props.evaluation?.code || '', titre: props.evaluation?.titre || '', type: props.evaluation?.type || 'controle', classe_id: props.evaluation?.classe_id || null, matiere_id: props.evaluation?.matiere_id || null, date: props.evaluation?.date || '', coefficient: props.evaluation?.coefficient || 1, sur: props.evaluation?.sur || 20, statut: props.evaluation?.statut || 'actif' });
const submit = () => { showStoreLoader(); setTimeout(() => { form.put(route('parametrage.evaluations.update', page.props.evaluation?.id), { onSuccess: () => hideLoader(), onError: () => hideLoader(), onFinish: () => hideLoader() }); }, 500); };
</script>
<template>
    <Head :title="t('entities.edit_evaluations')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper"><h4 class="title">{{ t('entities.edit_evaluations') }}</h4></div>
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
