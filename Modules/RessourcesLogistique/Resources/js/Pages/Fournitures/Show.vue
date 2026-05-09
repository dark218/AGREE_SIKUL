<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FournitureForm from './FournitureForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const { item, categories } = defineProps({
    item: Object,
    categories: Array,
});

const formatDateForInput = (dateValue) => {
    if (!dateValue) return '';
    if (typeof dateValue === 'string') {
        // Handle ISO format (2026-03-22T00:00:00.000000Z) or standard format (2026-03-22 00:00:00)
        return dateValue.includes('T') ? dateValue.split('T')[0] : dateValue.split(' ')[0];
    }
    return dateValue;
};

const form = useForm({
    categorie_fourniture_id: item?.categorie_fourniture_id || '',
    libelle: item?.libelle || '',
    code: item?.code || '',
    quantite: item?.quantite || 0,
    prix_unitaire_cents: item?.prix_unitaire_cents || 0,
    fournisseur: item?.fournisseur || '',
    date_acquisition: formatDateForInput(item?.date_acquisition),
    statut: item?.statut || 'disponible',
    localisation: item?.localisation || '',
});
</script>

<template>
    <Head :title="item?.libelle || t('common.fourniture')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('actions.view') }} {{ item?.libelle || t('common.fourniture') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <FournitureForm :form="form" mode="show" :categories="categories" />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('fournitures.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <Link :href="route('fournitures.edit', item.id)" class="btn btn-primary">
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
</style>
