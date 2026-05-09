<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import FournisseurPaiementForm from './FournisseurPaiementForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    fournisseur: Object,
    paysdevises: Array,
    types: Object,
});
const form = useForm({
    pays_devise_id: page.props.fournisseur?.pays_devise_id || '',
    libelle: page.props.fournisseur?.nom || '',
    code: page.props.fournisseur?.code || '',
    type: page.props.fournisseur?.type || '',
    statut: page.props.fournisseur?.statut || 'actif',
    config: page.props.fournisseur?.config || '',
    metadata: page.props.fournisseur?.metadata || '',
});
const logoPreview = computed(() => page.props.fournisseur?.logo || 'https://caer.univ-amu.fr/wp-content/uploads/default-placeholder.png');
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
                                <h5 class="title mb-0">{{ t('modules.parametrage.paymentProviders.show') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <FournisseurPaiementForm
                                :form="form"
                                :paysdevises="paysdevises"
                                :types="types"
                                :logoPreview="logoPreview"
                                mode="show"
                            />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.fournisseurs_paiement.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
