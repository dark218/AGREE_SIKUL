<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ClassesApprenantsForm from './ClassesApprenantsForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    apprenants: Array,
    classes: Array,
    anneesScolaires: Array,
});

const form = useForm({
    apprenant_id: '',
    classe_id: '',
    annee_academique_courante: '',
    annees_academiques_anterieures: '',
});

const submitForm = () => {
    showStoreLoader();
    form.post(route('academique.classes_apprenants.store'), {
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: () => {
            hideLoader();
        }
    });
};
</script>

<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <!-- Header avec collapse toggle -->
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.academique.classes_apprenants.create') || 'Affecter un apprenant à une classe' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>

                        <!-- Body du formulaire -->
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />

                            <form @submit.prevent="submitForm">
                                <ClassesApprenantsForm
                                    :form="form"
                                    :apprenants="apprenants"
                                    :classes="classes"
                                    :anneesScolaires="anneesScolaires"
                                    mode="create"
                                />

                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('academique.classes_apprenants.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="!form.apprenant_id || !form.classe_id || form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i> {{ t('actions.validate') || 'Valider' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>

<style scoped>
.body-wrapper {
    padding: 20px;
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin-left: -15px;
    margin-right: -15px;
}

.col-xl-12 {
    flex: 0 0 100%;
    padding-left: 15px;
    padding-right: 15px;
}

.mb-30 {
    margin-bottom: 30px;
}

.mb-30-none {
    margin-bottom: 0;
}

.justify-content-center {
    justify-content: center;
}

.dash-payment-item-wrapper {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.dash-payment-item {
    border: 1px solid #eee;
}

.dash-payment-item.active {
    border-color: #0d6efd;
}

.dash-payment-title-area {
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    user-select: none;
}

.dash-payment-title-area:hover {
    background: #f0f0f0;
}

.dash-payment-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #0d6efd;
    color: white;
    font-weight: bold;
    margin-right: 15px;
    font-size: 16px;
}

.title {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.collapse-toggle {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    color: #666;
    transition: transform 0.3s ease;
    padding: 5px;
}

.collapse-toggle.collapsed {
    transform: rotate(180deg);
}

.dash-payment-body {
    padding: 30px;
    transition: max-height 0.3s ease, opacity 0.3s ease;
    max-height: 2000px;
    opacity: 1;
    overflow: hidden;
}

.dash-payment-body.collapsed {
    max-height: 0;
    opacity: 0;
    padding: 0;
}

form {
    display: block;
}

.text-end {
    text-align: right;
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
    text-decoration: none;
    margin-left: 10px;
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

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

.d-flex {
    display: flex;
}

.align-items-center {
    align-items: center;
}

.me-2 {
    margin-right: 8px;
}

.spinner-border {
    display: inline-block;
    width: 1em;
    height: 1em;
    vertical-align: text-bottom;
    border: 0.25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border 0.75s linear infinite;
}

.spinner-border-sm {
    width: 0.875rem;
    height: 0.875rem;
    border-width: 0.2em;
}

@keyframes spinner-border {
    to {
        transform: rotate(360deg);
    }
}

.mt-3 {
    margin-top: 30px;
}
</style>
