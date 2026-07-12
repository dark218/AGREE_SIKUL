<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import PlanCompteForm from './PlanCompteForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const props = defineProps({ title: String, compte: Object, groupes: Array, comptesParents: Array });
const c = props.compte;
const form = useForm({
    _method: 'put',
    numero_compte: c.numero_compte ?? '', libelle_compte: c.libelle_compte ?? '',
    libelle_court: c.libelle_court ?? '', groupe_comptes_id: c.groupe_comptes_id ?? null,
    compte_parent_id: c.compte_parent_id ?? null, etat: c.etat ?? 'actif',
});
const submit = () => form.post(route('finances.plan-comptes.update', c.id));
</script>
<template>
    <Head title="Modifier le compte" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper"><h4 class="title">{{ title || 'Modifier le compte' }}</h4></div>
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
