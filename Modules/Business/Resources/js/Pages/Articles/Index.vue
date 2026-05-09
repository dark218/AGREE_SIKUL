<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    articles: Object,
    pointsVente: Array,
    filters: Object,
});
const searchFilters = ref({
    nom: props.filters?.nom || '',
    sku: props.filters?.sku || '',
    marque: props.filters?.marque || '',
    point_vente_id: props.filters?.point_vente_id || '',
});
// Debounce timer for real-time search
let searchTimeout;
// Real-time search with debounce
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};
const showDeleteModal = ref(false);
const articleToDelete = ref(null);
const search = () => {
    router.get(route('article.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};
const resetFilters = () => {
  const emptyFilters = {};
  Object.keys(searchFilters.value).forEach(key => {
    emptyFilters[key] = '';
  });
  searchFilters.value = emptyFilters;
  router.get(route('article.index'));
};
const confirmDelete = (article) => {
    articleToDelete.value = article;
    showDeleteModal.value = true;
};
const deleteArticle = () => {
    if (articleToDelete.value) {
        showDeleteLoader();
        router.put(route('article.statut', articleToDelete.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                articleToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const formatPrice = (prixCents, devise) => {
    if (!prixCents) return '0 ' + (devise || 'XOF');
    return new Intl.NumberFormat('en-US').format(prixCents) + ' ' + (devise || 'XOF');
};
const getStockBadgeClass = (article) => {
    if (article.quantite_stock <= 0) {
        return 'bg-danger';
    } else if (article.quantite_stock <= article.seuil_alert_stock) {
        return 'bg-warning';
    }
    return 'bg-success';
};
const getStockLabel = (article) => {
    if (article.quantite_stock <= 0) {
        return t('modules.business.articles.stockEmpty');
    } else if (article.quantite_stock <= article.seuil_alert_stock) {
        return t('modules.business.articles.stockLow');
    }
    return t('modules.business.articles.stockOk');
};
// Real-time search with debounce
watch(
  () => searchFilters.value,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      search();
    }, 500); // 500ms debounce
  },
  { deep: true }
);
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.business.articles.title') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('article-create')">
                        <Link :href="route('article.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.nom" class="form-control"
                            :placeholder="t('fields.name')" />
                    </div>
                    <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.sku" class="form-control"
                            :placeholder="t('modules.business.articles.sku')" />
                    </div>
                    <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.marque" class="form-control"
                            :placeholder="t('modules.business.articles.brand')" />
                    </div>
                    <div class="col-3 p-1">
                        <StylishSelect v-model="searchFilters.point_vente_id" :options="pointsVente" option-value="value"
                            option-label="label" :placeholder="t('modules.business.pointsvente.singular')" />
                    </div>
                    <div class="col-3 p-1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>{{ t('modules.business.articles.sku') }}</th>
                                    <th>{{ t('fields.name') }}</th>
                                    <th>{{ t('modules.business.articles.brand') }}</th>
                                    <th>{{ t('modules.business.pointsvente.singular') }}</th>
                                    <th>{{ t('modules.business.articles.price') }}</th>
                                    <th>{{ t('modules.business.articles.stock') }}</th>
                                    <th>{{ t('modules.business.articles.stockStatus') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="articles.data && articles.data.length > 0">
                                    <tr v-for="article in articles.data" :key="article.id">
                                        <td>{{ article.sku }}</td>
                                        <td>{{ article.nom }}</td>
                                        <td>{{ article.marque || '-' }}</td>
                                        <td>{{ article.point_vente?.nom || '-' }}</td>
                                        <td>{{ article.prix_display }} {{ article.devise }}</td>
                                        <td>{{ article.quantite_stock }}</td>
                                        <td>
                                            <span
                                                :class="['badge text-dark rounded-pill', getStockBadgeClass(article)]">
                                                {{ getStockLabel(article) }}
                                            </span>
                                        </td>
                                        <td class="fit">
                                            <div class="action-buttons">
                                                <Link :href="route('article.show', article.id)"
                                                    class="btn btn-secondary btn-sm" :title="t('actions.view')">
                                                    <i class="fa fa-eye"></i>
                                                </Link>
                                                <Link v-if="can('article-edit')"
                                                    :href="route('article.edit', article.id)"
                                                    class="btn btn-primary btn-sm" :title="t('actions.edit')">
                                                    <i class="fa fa-edit"></i>
                                                </Link>
                                                <button v-if="can('article-statut')" type="button"
                                                    class="btn btn-danger btn-sm" @click="confirmDelete(article)"
                                                    :title="t('actions.delete')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="8" class="text-center">{{ t('common.emptyList') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="articles" />
                </div>
            </div>
        </div>
        <ConfirmModal :show="showDeleteModal" :title="t('messages.confirm.delete.title')"
            :message="articleToDelete?.etat === 'actif' || articleToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')" :sub-message="t('messages.confirm.delete.warning')"
            @close="showDeleteModal = false" @confirm="deleteArticle" confirm-text="Supprimer"
            confirm-class="btn-danger" />
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
