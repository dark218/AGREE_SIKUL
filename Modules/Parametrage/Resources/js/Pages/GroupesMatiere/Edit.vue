<script setup>
import { ref } from 'vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import GroupesMatiereForm from './GroupesMatiereForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    niveaux: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    institutions: { type: Array, default: () => [] },
});
const i = page.props.item || {};
const form = useForm({
    code: i.code || '',
    libelle: i.libelle || '',
    ecole_id: i.ecole_id || null,
    institution_id: i.institution_id || null,
    niveau_id: i.niveau_id || null,
    section_id: i.section_id || null,
    cycle_id: i.cycle_id || null,
    // matieres[] : ids MatiereUnite sync sur la pivot groupe_matiere_items.
    // Remplace les 10 slots matiere1_id..matiere10_id.
    matieres: (i.matieres ?? []).map((m) => m.id ?? m),
    etat: i.etat || 'actif',
});
const submitForm = () => {
    showUpdateLoader();
    form.put(route('parametrage.groupes_matiere.update', page.props.item?.id), {
        onError: (errors) => {
            console.error('Form validation errors:', errors);
        },
        onSuccess: () => {
        },
        onFinish: () => {
            hideLoader();
        }
    });
};
</script>
<template>
    <Head :title="t('actions.edit')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0"><i class="fa fa-pencil"></i> {{ t('actions.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <GroupesMatiereForm
                                    :form="form"
                                    :niveaux="niveaux"
                                    :sections="sections"
                                    :cycles="cycles"
                                    :matieres="matieres"
                                    :ecoles="ecoles"
                                    :institutions="institutions"
                                    mode="edit"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.groupes_matiere.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i> {{ t('actions.validate') }}
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
