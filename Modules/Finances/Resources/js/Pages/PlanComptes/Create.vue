<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import PlanCompteForm from './PlanCompteForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const props = defineProps({ title: String, groupes: Array, comptesParents: Array });
const form = useForm({ numero_compte: '', libelle_compte: '', libelle_court: '', groupe_comptes_id: null, compte_parent_id: null, etat: 'actif' });
const submit = () => form.post(route('finances.plan-comptes.store'));
</script>
<template>
    <Head title="Nouveau compte" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper"><h4 class="title">{{ title || 'Nouveau compte' }}</h4></div>
        <AlertMessage />
        <form @submit.prevent="submit">
            <PlanCompteForm :form="form" :groupes="groupes" :comptes-parents="comptesParents" />
            <div class="text-end mt-3">
                <button type="button" class="btn btn-danger" @click="$inertia.visit(route('finances.plan-comptes.index'))">{{ t('actions.back') }}</button>
                <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('actions.validate') }}</button>
            </div>
        </form>
    </div>
</template>
