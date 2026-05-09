<script setup>
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
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

const handleDelete = () => {
    if (confirm(t('messages.confirm_delete') || 'Êtes-vous sûr de vouloir supprimer?')) {
        router.delete(route('academique.classes_apprenants.destroy', props.apprenant.id), {
            onSuccess: () => {
                router.visit(route('academique.classes_apprenants.index'));
            },
        });
    }
};
</script>

<template>
    <Head :title="`${apprenant.prenoms} ${apprenant.nom}`" />
    <div class="body-wrapper">
        <div class="form-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ apprenant.prenoms }} {{ apprenant.nom }}</h4>
            </div>

            <!-- Alert Message -->
            <AlertMessage />

            <!-- Form -->
            <div class="dash-payment-item-wrapper">
                <ClassesApprenantsForm :form="{ apprenant_id: apprenant.id, classe_id: apprenant.classe_id, annee_academique_courante: '', annees_academiques_anterieures: '', errors: {} }" :apprenants="apprenants" :classes="classes" :anneesScolaires="anneesScolaires" mode="show" />

                <!-- Action Buttons (Bottom) -->
                <div class="form-actions" style="margin-top: 30px; justify-content: flex-end;">
                    <a :href="route('academique.classes_apprenants.index')" class="btn btn-secondary">
                        <span class="fa fa-times"></span>
                        {{ t('actions.cancel') || 'Annuler' }}
                    </a>
                    <a :href="route('academique.classes_apprenants.edit', apprenant.id)" class="btn btn-primary">
                        <span class="fa fa-edit"></span>
                        {{ t('actions.edit') || 'Modifier' }}
                    </a>
                </div>
            </div>
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

.header-actions {
    display: flex;
    gap: 10px;
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

.btn-secondary {
    background-color: #6c757d;
    color: white;
    text-decoration: none;
}

.btn-secondary:hover {
    background-color: #5c636a;
}

.btn-primary {
    background-color: #0d6efd;
    color: white;
    text-decoration: none;
}

.btn-primary:hover {
    background-color: #0b5ed7;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}
</style>
