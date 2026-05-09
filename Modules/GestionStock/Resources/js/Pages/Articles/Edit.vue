<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ArticleForm from './ArticleForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const props = defineProps({
    article: Object,
    pointsVente: Array,
    devise: Array,
});
const normalizePrix = (prix) => {
    if (!prix) return 0;
    // Convertir en string et remplacer virgule par point, puis enlever les espaces
    const normalized = String(prix).replace(/,/g, '.').replace(/\s/g, '');
    return parseFloat(normalized) || 0;
};
const form = useForm({
    points_vente_id: props.article.points_vente_id || '',
    sku: props.article.sku || '',
    nom: props.article.nom || '',
    marque: props.article.marque || '',
    description: props.article.description || '',
    prix_cents: normalizePrix(props.article.prix_display),
    devise: props.article.devise || 'XOF',
    quantite_stock: props.article.quantite_stock || 0,
    seuil_alert_stock: props.article.seuil_alert_stock || 0,
    taxes_json: props.article.taxes_json || null,
});
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const submitForm = () => {
    showUpdateLoader();
    form.post(route('article.update', props.article.id), {
        _method: 'put',
        onFinish: () => {
            hideLoader();
        },
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
                                <h5 class="title mb-0">{{ t('modules.business.articles.edit') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <ArticleForm
                                    :form="form"
                                    :pointsVente="pointsVente"
                                    :devise="devise"
                                    mode="edit"
                                />
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('article.index')" class="btn btn-danger">
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
