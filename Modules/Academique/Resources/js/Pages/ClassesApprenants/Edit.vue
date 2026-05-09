<script setup>
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ClassesApprenantsForm from './ClassesApprenantsForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const props = defineProps({
    apprenant: Object,
    apprenants: Array,
    classes: Array,
    anneesScolaires: Array,
});

const form = useForm({
    apprenant_id: props.apprenant.id,
    classe_id: props.apprenant.classe_id,
    annee_academique_courante: '',
    annees_academiques_anterieures: '',
});

const submit = () => {
    form.put(route('academique.classes_apprenants.update', props.apprenant.id));
};
</script>

<template>
    <Head :title="`${t('actions.edit')} - ${apprenant.prenoms} ${apprenant.nom}`" />
    <div class="body-wrapper">
        <div class="form-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('actions.edit') || 'Modifier' }} - {{ apprenant.prenoms }} {{ apprenant.nom }}</h4>
            </div>

            <!-- Alert Message -->
            <AlertMessage />

            <!-- Form -->
            <form @submit.prevent="submit" class="dash-payment-item-wrapper">
                <ClassesApprenantsForm :form="form" :apprenants="apprenants" :classes="classes" :anneesScolaires="anneesScolaires" mode="edit" />

                <!-- Buttons -->
                <div class="form-actions" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" :disabled="!form.classe_id || form.processing">
                        <span v-if="!form.processing" class="fa fa-save"></span>
                        <span v-else class="spinner-border spinner-border-sm me-2"></span>
                        {{ t('actions.update') || 'Mettre à jour' }}
                    </button>
                    <a :href="route('academique.classes_apprenants.index')" class="btn btn-secondary">
                        {{ t('actions.cancel') || 'Annuler' }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.body-wrapper {
    padding: 20px;
    background: #f5f5f5;
}

.form-area {
    width: 100%;
}

.dashboard-header-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.title {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
}

.dash-payment-item-wrapper {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-primary {
    background-color: #0d6efd;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: #0b5ed7;
}

.btn-primary:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    text-decoration: none;
}

.btn-secondary:hover {
    background-color: #5c636a;
}
</style>
