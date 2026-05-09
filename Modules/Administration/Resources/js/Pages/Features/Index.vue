<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
const page = usePage();
const { can } = usePermissions();
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();
defineOptions({
    layout: DashboardLayout
});
const props = defineProps({
    title: String,
    features: Object,
    modules: Array,
    filters: Object,
});
// Filtres
const searchFilters = ref({
    module_id: props.filters?.module_id || '',
    libelle: props.filters?.libelle || '',
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
// Modal de suppression
const showDeleteModal = ref(false);
const featureToDelete = ref(null);
// Recherche
function search() {
    router.get(route('administration.features.index'), {
        module_id: searchFilters.value.module_id || undefined,
        libelle: searchFilters.value.libelle || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}
// Confirmation de suppression
function confirmDelete(feature) {
    featureToDelete.value = feature;
    showDeleteModal.value = true;
}
// Supprimer la feature
function deleteFeature() {
    if (featureToDelete.value) {
        showDeleteLoader();
        router.put(route('administration.features.statut', featureToDelete.value.id), {}, {
            onSuccess: () => {
                showDeleteModal.value = false;
                featureToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            }
    });
    }
}
function getMenuLabel(menu) {
    if (currentLocale.value === 'en' && menu.libelle_en) {
        return menu.libelle_en;
    }
    return menu.libelle;
}
function closeDeleteModal() {
    showDeleteModal.value = false;
    featureToDelete.value = null;
}
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
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div v-if="can('feature-create')" class="dashboard-btn">
                        <Link :href="route('administration.features.create')" class="btn btn-primary">{{ t('common.add') }}</Link>
                    </div>
                </div>
            </div>
            <!-- Alert Messages -->
            <AlertMessage />
            <!-- Filtres -->
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <div class="col-4 p-1">
                        <StylishSelect
                            v-model="searchFilters.module_id"
                            :options="modules"
                            option-value="id"
                            option-label="libelle"
                            :placeholder="t('modules.administration.permissions.fields.module')"
                        />
                    </div>
                    <div class="col-4 p-1">
                        <input v-model="searchFilters.libelle" type="text" class="form-control search-slt" :placeholder="t('common.name')">
                    </div>
                    <div class="col-2 p-1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i>
                        </button>
                    <button type="button" @click="resetFilters" class="btn btn-secondary wrn-btn radius-0">
                        <i class="fa fa-redo"></i> <i class="fa fa-sync"></i> {{ t('actions.reset') }}
                    </button>
                    </div>
                </form>
            </div>
            <!-- Tableau -->
            <div class="card-body">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>{{ t('modules.administration.permissions.fields.module') }}</th>
                                    <th>{{ t('common.name') }}</th>
                                    <th>{{ t('modules.administration.features.fields.icon') }}</th>
                                    <th>{{ t('common.name') }}</th>
                                    <th>{{ t('modules.administration.modules.fields.order') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="features.data && features.data.length > 0" v-for="feature in features.data" :key="feature.id">
                                    <td>{{ feature.module }}</td>
                                    <td>{{ feature.libelle }}</td>
                                    <td>{{ feature.icone }}</td>
                                    <td>{{ feature.menu_url }}</td>
                                    <td>{{ feature.ordre }}</td>
                                    <td class="fit">
                                        <div class="action-buttons">
                                            <Link :href="route('administration.features.show', feature.id)" class="btn btn-secondary btn-sm" :title="t('common.show')">
                                                <i class="fa fa-eye"></i>
                                            </Link>
                                            <Link :href="route('administration.features.edit', feature.id)" class="btn btn-primary btn-sm" :title="t('common.edit')">
                                                <i class="fa fa-edit"></i>
                                            </Link>
                                            <button v-if="can('feature-statut')" @click="confirmDelete(feature)" class="btn btn-danger btn-sm" :title="t('common.delete')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else>
                                    <td colspan="6" class="text-center">{{ t('common.noData') }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Pagination -->
                        <Pagination :data="features" :preserve-scroll="true" :preserve-state="true" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation -->
         <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="featureToDelete?.etat === 'actif' || featureToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            confirm-text="t('actions.delete')"
            confirm-class="btn-danger"
            @confirm="deleteFeature"
            @cancel="closeDeleteModal"
        />
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
