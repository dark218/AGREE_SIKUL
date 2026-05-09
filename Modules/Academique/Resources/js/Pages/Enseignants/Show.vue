<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import EnseignantForm from './EnseignantForm.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';

defineOptions({
    layout: DashboardLayout,
});

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();
const page = usePage();

const props = defineProps({
    enseignant: Object,
    communes: Array,
    departements: Array,
    regions: Array,
    pays: Array,
    categoriesEnseignant: Array,
    matieres: Array,
    cycles: Array,
    niveaux: Array,
    classes: Array,
});

const showDeleteModal = ref(false);
const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const confirmDelete = () => {
    showDeleteModal.value = true;
};

const deleteItem = () => {
    showDeleteLoader(t('common.deleting'));
    router.delete(route('academique.enseignants.destroy', props.enseignant.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
        onFinish: () => hideLoader(),
    });
};

const closeModal = () => {
    showDeleteModal.value = false;
};
</script>

<template>
    <Head :title="enseignant?.nom + ' ' + enseignant?.prenoms" />
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
                                <h5 class="title mb-0">{{ t('common.view_enseignant') || 'Détails de l\'enseignant' }} - {{ enseignant?.nom }} {{ enseignant?.prenoms }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <EnseignantForm
                                :form="enseignant"
                                :communes="communes"
                                :departements="departements"
                                :regions="regions"
                                :pays="pays"
                                :categoriesEnseignant="categoriesEnseignant"
                                :matieres="matieres"
                                :cycles="cycles"
                                :niveaux="niveaux"
                                :classes="classes"
                                mode="show"
                            />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.enseignants.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <Link :href="route('academique.enseignants.edit', enseignant?.id)" class="btn btn-primary ms-2">
                                            <i class="fa fa-edit"></i> {{ t('actions.edit') }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('common.confirm_delete')"
            :message="t('messages.confirm.delete')"
            @confirm="deleteItem"
            @update:show="closeModal"
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
