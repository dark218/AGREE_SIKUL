<script setup>
import { ref, computed, watch } from 'vue';
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
    caisses: Object,
    types: Array,
    statuts: Array,
    pointsVente: Array,
    filters: Object,
});
// Computed pour traduire les labels des types
const translatedTypes = computed(() => {
    return props.types.map(type => ({
        ...type,
        label: t(`modules.business.caisses.types.${type.value}`)
    }));
});
// Computed pour traduire les labels des statuts
const translatedStatuts = computed(() => {
    return props.statuts.map(statut => ({
        ...statut,
        label: t(`modules.business.caisses.statuts.${statut.value}`)
    }));
});
// Fonction pour traduire le type dans la table
const getTranslatedTypeLabel = (type) => {
    return t(`modules.business.caisses.types.${type}`);
};
// Fonction pour traduire le statut dans la table
const getTranslatedStatutLabel = (statut) => {
    return t(`modules.business.caisses.statuts.${statut}`);
};
const searchFilters = ref({
    point_vente_id: props.filters?.point_vente_id || '',
    nom: props.filters?.nom || '',
    code: props.filters?.code || '',
    type: props.filters?.type || '',
    statut: props.filters?.statut || '',
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
const caisseToDelete = ref(null);
const search = () => {
    router.get(route('caisse.index'), searchFilters.value, {
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
  router.get(route('caisse.index'));
};
const confirmDelete = (caisse) => {
    caisseToDelete.value = caisse;
    showDeleteModal.value = true;
};
const deleteCaisse = () => {
    if (caisseToDelete.value) {
        showDeleteLoader();
        router.put(route('caisse.statut', caisseToDelete.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                caisseToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const getStatutBadgeClass = (statut) => {
    const classes = {
        'ouverte': 'bg-success',
        'fermee': 'bg-secondary',
        'bloquee': 'bg-danger',
    };
    return classes[statut] || 'bg-secondary';
};
const getTypeBadgeClass = (type) => {
    const classes = {
        'principale': 'bg-primary',
        'secondaire': 'bg-info',
        'mobile': 'bg-warning',
    };
    return classes[type] || 'bg-secondary';
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
    <Head :title="t('modules.business.caisses.title')" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.business.caisses.title') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('caisse-create')">
                        <Link :href="route('caisse.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.point_vente_id" :options="pointsVente" option-value="value"
                            option-label="label" :placeholder="t('fields.pointOfSale')" />
                    </div>
                    <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.code" class="form-control"
                            :placeholder="t('fields.code')" />
                    </div>
                    <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.nom" class="form-control"
                            :placeholder="t('fields.name')" />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.type" :options="translatedTypes" option-value="value"
                            option-label="label" :placeholder="t('fields.type')" />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.statut" :options="translatedStatuts" option-value="value"
                            option-label="label" :placeholder="t('common.status')" />
                    </div>
                    <div class="col-2 p-1">
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
                                    <th>{{ t('fields.code') }}</th>
                                    <th>{{ t('fields.name') }}</th>
                                    <th>{{ t('fields.type') }}</th>
                                    <th>{{ t('fields.pointOfSale') }}</th>
                                    <th>{{ t('common.status') }}</th>
                                    <th>{{ t('fields.createdAt') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="caisses.data && caisses.data.length > 0">
                                    <tr v-for="caisse in caisses.data" :key="caisse.id">
                                        <td>{{ caisse.code }}</td>
                                        <td>{{ caisse.nom }}</td>
                                        <td>
                                            <span :class="['badge text-dark rounded-pill', getTypeBadgeClass(caisse.type)]">
                                                {{ getTranslatedTypeLabel(caisse.type) }}
                                            </span>
                                        </td>
                                        <td>{{ caisse.point_vente }}</td>
                                        <td>
                                            <span :class="['badge text-dark rounded-pill', getStatutBadgeClass(caisse.statut)]">
                                                {{ getTranslatedStatutLabel(caisse.statut) }}
                                            </span>
                                        </td>
                                        <td>{{ caisse.created_at }}</td>
                                        <td class="fit">
                                            <div class="action-buttons">
                                                <Link :href="route('caisse.show', caisse.id)"
                                                    class="btn btn-secondary btn-sm" :title="t('actions.view')">
                                                    <i class="fa fa-eye"></i>
                                                </Link>
                                                <Link v-if="can('caisse-edit')"
                                                    :href="route('caisse.edit', caisse.id)"
                                                    class="btn btn-primary btn-sm" :title="t('actions.edit')">
                                                    <i class="fa fa-edit"></i>
                                                </Link>
                                                <button v-if="can('caisse-statut')" type="button"
                                                    class="btn btn-danger btn-sm" @click="confirmDelete(caisse)"
                                                    :title="t('actions.delete')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="7" class="text-center">{{ t('common.emptyList') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="caisses" />
                </div>
            </div>
        </div>
        <ConfirmModal :show="showDeleteModal" :title="t('messages.confirm.delete.title')"
            :message="caisseToDelete?.etat === 'actif' || caisseToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')" :sub-message="t('messages.confirm.delete.warning')"
            @close="showDeleteModal = false" @confirm="deleteCaisse" confirm-text="Supprimer"
            confirm-class="btn-danger" />
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
