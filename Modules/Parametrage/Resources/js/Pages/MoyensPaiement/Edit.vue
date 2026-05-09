<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MoyensPaiementForm from './MoyensPaiementForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const form = useForm({
    id: null,
    code: '',
    libelle: '',
    type_mode: '',
    necessite_reference: false,
    delai_compensation_jours: null,
    frais_pourcentage: null,
    frais_fixe: null,
    montant_min: null,
    montant_max: null,
    fournisseur_paiement_id: null,
    etat: 'actif',
    });
// Populate form when component mounts and data is available
onMounted(() => {
    const item = page.props.item;
    if (item) {
        Object.assign(form, {
            id: item.id,
            code: item.code || '',
            libelle: item.libelle || '',
            type_mode: item.type_mode || '',
            necessite_reference: item.necessite_reference || false,
            delai_compensation_jours: item.delai_compensation_jours || null,
            frais_pourcentage: item.frais_pourcentage || null,
            frais_fixe: item.frais_fixe || null,
            montant_min: item.montant_min || null,
            montant_max: item.montant_max || null,
            fournisseur_paiement_id: item.fournisseur_paiement_id || null,
            etat: item.etat || 'actif',
            });
    }
});
const submitForm = () => {
    if (!page.props.item?.id) return;
    showUpdateLoader();
    form.put(route('parametrage.moyens_paiement.update', page.props.item?.id), {
        onError: (errors) => {
            console.error('Form validation errors:', errors);
            hideLoader();
        },
        onSuccess: () => {
            console.log('Form updated successfully');
        },
        onFinish: () => {
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
                                <MoyensPaiementForm :form="form" :fournisseurs="page.props.fournisseurs" mode="edit" />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('parametrage.moyens_paiement.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                v-if="page.props.item?.id"
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
