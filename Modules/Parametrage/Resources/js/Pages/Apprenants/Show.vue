<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ApprenantForm from './ApprenantForm.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const showToggleModal = ref(false);
const itemToToggle = ref(null);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toISOString().split('T')[0];
};
const props = defineProps({
    apprenant: Object,
    classes: { type: Array, default: () => [] },
});
const apprenant = page.props.apprenant;
const form = useForm({
    matricule: page.props.apprenant?.matricule || '',
    nom: page.props.apprenant?.nom || '',
    prenoms: page.props.apprenant?.prenoms || '',
    sexe: page.props.apprenant?.sexe || null,
    email: page.props.apprenant?.email || '',
    telephone: page.props.apprenant?.telephone || '',
    adresse: page.props.apprenant?.adresse || '',
    classe_id: page.props.apprenant?.classe_id || null,
    numero_inscription: page.props.apprenant?.numero_inscription || '',
    date_naissance: formatDate(page.props.apprenant?.date_naissance),
    statut: page.props.apprenant?.statut || 'actif',
});
const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    showDeleteModal.value = false;
    showUpdateLoader();
    router.visit(route('parametrage.apprenants.destroy', itemToDelete.value?.id), {
        method: 'delete',
        onFinish: () => {
            hideLoader();
        }
    });
};
const confirmToggleStatut = (item) => {
    itemToToggle.value = item;
    showToggleModal.value = true;
};
const toggleStatut = () => {
    showToggleModal.value = false;
    const newStatut = page.props.apprenant?.statut === 'actif' ? 'inactif' : 'actif';
    showUpdateLoader();
    const loaderMsg = newStatut === 'actif' ? t('messages.activating') || 'Activation en cours...' : t('messages.deactivating') || 'Désactivation en cours...';
    loaderMessage.value = loaderMsg;
    router.visit(route('parametrage.apprenants.statut', itemToToggle.value?.id), {
        method: 'put',
        onFinish: () => {
            hideLoader();
        }
    });
};
const closeModal = () => {
    showDeleteModal.value = false;
};
const closeToggleModal = () => {
    showToggleModal.value = false;
};
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-eye"></i>
                                </span>
                                <h5 class="title mb-0">{{ apprenant?.nom }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <ApprenantForm
                                :form="form"
                                :classes="classes"
                                mode="show"
                            />
                            <!-- Action Buttons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.apprenants.edit', apprenant?.id)" class="btn btn-warning me-2">
                                            <i class="fa fa-edit me-1"></i><i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </Link>
                                        <button
                                            type="button"
                                            class="btn btn-info me-2"
                                            @click="confirmToggleStatut(apprenant)"
                                        >
                                            <i :class="apprenant?.statut === 'actif' ? 'fa fa-ban' : 'fa fa-check'" class="me-1"></i>
                                            {{ apprenant?.statut === 'actif' ? t('actions.deactivate') : t('actions.activate') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-danger"
                                            @click="confirmDelete(apprenant)"
                                        >
                                            <i class="fa fa-trash me-1"></i><i class="fa fa-trash"></i> {{ t('actions.delete') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Supprimer l'apprenant"
            :message="`Êtes-vous sûr de vouloir supprimer cet apprenant ?`"
            danger
            @confirm="deleteItem"
            @update:show="closeModal"
        />
        <!-- Toggle Status Confirmation Modal -->
        <ConfirmModal
            :show="showToggleModal"
            :title="`${apprenant?.statut === 'actif' ? t('messages.confirm.deactivate.title') : t('messages.confirm.activate.title')}`"
            :message="`${apprenant?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')}`"
            info
            @confirm="toggleStatut"
            @update:show="closeToggleModal"
        />
        <!-- Full Page Loader -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
